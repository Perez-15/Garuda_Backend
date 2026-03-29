<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\User;


class AttendanceController extends Controller
{
    /**
     * All internal employee roles that are allowed to record attendance.
     * Excludes applicant-facing roles only.
     */
    private const ALLOWED_ROLES = ['super_admin', 'hr_admin', 'talent_acquisition', 'accounting', 'marketing'];

    /**
     * Office coordinates & allowed radius (meters).
     * Override via config/attendance.php or .env.
     */
    private function officeConfig(): array
    {
        return [
            'latitude'  => (float) config('attendance.office_latitude',  14.6537),
            'longitude' => (float) config('attendance.office_longitude', 121.0684),
            'radius'    => (int)   config('attendance.office_radius',    150),
        ];
    }

    // ── GET /attendance/today ──────────────────────────────────────────────────

    /**
     * Returns the authenticated user's attendance record for today.
     * Used by the frontend to determine which button to show (Time In / Time Out).
     */
    public function today(Request $request)
    {
        $this->authorizeAttendance();

        $today      = Carbon::now('Asia/Manila')->toDateString();
        $attendance = Attendance::forUser(auth()->id())
                                ->forDate($today)
                                ->first();

        return response()->json([
            'attendance' => $attendance,
            'office'     => $this->officeConfig(),
        ]);
    }

    // ── POST /attendance/time-in ───────────────────────────────────────────────

    /**
     * Records a Time In for today.
     * Validates that the user is within the office radius.
     * A user can only Time In once per day.
     */
    public function timeIn(Request $request)
    {
        $this->authorizeAttendance();

        $validated = $request->validate([
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $this->assertWithinOffice($validated['latitude'], $validated['longitude']);

        $today = Carbon::now('Asia/Manila')->toDateString();
        $now   = Carbon::now('Asia/Manila')->toTimeString();

        // Prevent duplicate time-in on the same day
        $existing = Attendance::forUser(auth()->id())->forDate($today)->first();
        if ($existing && $existing->time_in) {
            return response()->json([
                'message' => 'You have already timed in today.',
            ], 422);
        }

        $status = Attendance::resolveStatus($now);

        $attendance = Attendance::create([
            'user_id'   => auth()->id(),
            'date'      => $today,
            'time_in'   => $now,
            'latitude'  => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'status'    => $status,
        ]);

        return response()->json([
            'message'    => 'Time In recorded successfully.',
            'attendance' => $attendance,
        ], 201);
    }

    // ── PATCH /attendance/time-out ─────────────────────────────────────────────

    /**
     * Records a Time Out by updating today's existing attendance record.
     * Validates that the user is within the office radius.
     */
    public function timeOut(Request $request)
    {
        $this->authorizeAttendance();

        $validated = $request->validate([
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $this->assertWithinOffice($validated['latitude'], $validated['longitude']);

        $today      = Carbon::now('Asia/Manila')->toDateString();
        $now        = Carbon::now('Asia/Manila')->toTimeString();
        $attendance = Attendance::forUser(auth()->id())->forDate($today)->first();

        if (!$attendance || !$attendance->time_in) {
            return response()->json([
                'message' => 'You have not timed in today.',
            ], 422);
        }

        if ($attendance->time_out) {
            return response()->json([
                'message' => 'You have already timed out today.',
            ], 422);
        }

        $attendance->update(['time_out' => $now]);

        return response()->json([
            'message'    => 'Time Out recorded successfully.',
            'attendance' => $attendance->fresh(),
        ]);
    }

    // ── GET /attendance ────────────────────────────────────────────────────────

    /**
     * Returns the authenticated user's attendance history.
     * HR Admin / Super Admin can pass ?user_id= to view any user's records.
     * Supports: month (YYYY-MM), per_page.
     */
    public function index(Request $request)
    {
        $this->authorizeAttendance();

        $request->validate([
            'user_id' => 'nullable|integer|exists:users,id',
            'month'   => 'nullable|date_format:Y-m',
            'per_page'=> 'nullable|integer|in:15,30,50',
        ]);

        $isAdmin = auth()->user()->hasRole(['super_admin', 'hr_admin']);

        // Non-admins can only view their own records
        $targetUserId = ($isAdmin && $request->filled('user_id'))
            ? (int) $request->user_id
            : auth()->id();

        $query = Attendance::with('user:id,name,email')
                           ->forUser($targetUserId)
                           ->orderBy('date', 'desc');

       if ($request->filled('date_from')) {
    $query->whereDate('date', '>=', $request->date_from);
}
if ($request->filled('date_to')) {
    $query->whereDate('date', '<=', $request->date_to);
}

        $perPage = in_array((int) $request->get('per_page'), [15, 30, 50])
            ? (int) $request->get('per_page') : 15;

        return response()->json($query->paginate($perPage));
    }

    // ── GET /attendance/team (HR/Admin only) ───────────────────────────────────

    /**
     * Returns today's attendance summary for all eligible employees.
     * Only accessible by HR Admin and Super Admin.
     */
public function team(Request $request)
{
    if (!auth()->user()->hasRole(['super_admin', 'hr_admin', 'accounting'])) {
        abort(403, 'Unauthorized.');
    }

    // ✅ Get all employees
    $users = User::with(['roles'])
    ->where('is_active', true)
    ->whereHas('roles', function ($q) {
        $q->whereIn('name', self::ALLOWED_ROLES);
    });

// ✅ ADD THIS
if ($request->filled('user_id')) {
    $users->where('id', $request->user_id);
}

$users = $users->get();

    // ✅ Determine date range
    $start = $request->filled('date_from')
        ? Carbon::parse($request->date_from)
        : ($request->filled('date')
            ? Carbon::parse($request->date)
            : Carbon::now('Asia/Manila'));

    $end = $request->filled('date_to')
        ? Carbon::parse($request->date_to)
        : $start;

    // ✅ Get all dates in range
    $dates = [];
    for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
        $dates[] = $date->toDateString();
    }

    // ✅ Get attendance in range
    $attendance = Attendance::whereBetween('date', [$start, $end])
        ->get()
        ->groupBy(function ($item) {
            return $item->user_id . '_' . $item->date;
        });

    // ✅ Build FULL matrix
    $result = collect();

    foreach ($users as $user) {
        foreach ($dates as $date) {

            $key = $user->id . '_' . $date;
            $record = $attendance[$key][0] ?? null;

            $result->push([
                'user' => $user,
                'date' => $date,
                'time_in' => $record->time_in ?? null,
                'time_out' => $record->time_out ?? null,
                'status' => $record->status ?? 'Absent',
            ]);
        }
    }

    // ✅ Filter by status
    if ($request->filled('status')) {
        $result = $result->filter(function ($item) use ($request) {
            return $item['status'] === $request->status;
        })->values();
    }

    // ✅ Pagination
    $page = $request->get('page', 1);
    $perPage = $request->get('per_page', 15);

    $paginated = $result->slice(($page - 1) * $perPage, $perPage)->values();

    return response()->json([
        'data' => $paginated,
        'total' => $result->count(),
        'current_page' => (int) $page,
        'per_page' => (int) $perPage,
    ]);
}
    // ── Private Helpers ────────────────────────────────────────────────────────

    /**
     * Abort with 403 if the authenticated user is not allowed to use attendance.
     */
    private function authorizeAttendance(): void
    {
        if (!auth()->user()->hasRole(self::ALLOWED_ROLES)) {
            abort(403, 'Attendance is only available for internal employees.');
        }

        if (!auth()->user()->is_active) {
            abort(403, 'Your account is inactive.');
        }
    }

    /**
     * Abort with 422 if the provided coordinates are outside the allowed radius.
     * This is a server-side guard — the frontend check is for UX only.
     */
    private function assertWithinOffice(float $lat, float $lon): void
    {
        $office   = $this->officeConfig();
        $distance = Attendance::haversineDistance(
            $lat, $lon,
            $office['latitude'], $office['longitude']
        );

        if ($distance > $office['radius']) {
            abort(422, 'You must be inside the office to record attendance.');
        }
    }
}   