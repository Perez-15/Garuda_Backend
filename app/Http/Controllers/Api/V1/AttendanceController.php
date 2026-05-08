<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;

class AttendanceController extends Controller
{
    /**
     * All internal employee roles allowed to record attendance.
     */
    private const ALLOWED_ROLES = [
        'super_admin',
        'hr_admin',
        'talent_acquisition',
        'accounting',
        'marketing',
    ];

    /**
     * Roles allowed to view team attendance and export reports.
     */
    private const TEAM_ROLES = ['super_admin', 'hr_admin', 'accounting'];

    /**
     * Allowed per_page values — enforced on every paginated endpoint.
     * Prevents a caller from sending per_page=999999 to dump the entire table.
     */
    private const ALLOWED_PER_PAGE = [15, 30, 50];

    /**
     * Office coordinates and allowed radius (meters).
     * Values come from config/attendance.php or .env — never hardcoded here.
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

        $existing = Attendance::forUser(auth()->id())->forDate($today)->first();
        if ($existing && $existing->time_in) {
            return response()->json([
                'message' => 'You have already timed in today.',
            ], 422);
        }

        $status     = Attendance::resolveStatus($now);
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
            return response()->json(['message' => 'You have not timed in today.'], 422);
        }

        if ($attendance->time_out) {
            return response()->json(['message' => 'You have already timed out today.'], 422);
        }

        $attendance->update(['time_out' => $now]);

        return response()->json([
            'message'    => 'Time Out recorded successfully.',
            'attendance' => $attendance->fresh(),
        ]);
    }

    // ── GET /attendance ────────────────────────────────────────────────────────

    /**
     * Returns the authenticated user's own attendance history.
     * Admins may pass ?user_id= to view another user's records.
     */
    public function index(Request $request)
    {
        $this->authorizeAttendance();

        $request->validate([
            'user_id'   => 'nullable|integer|exists:users,id',
            'month'     => 'nullable|date_format:Y-m',
            // FIX: date inputs validated as real dates before any parsing.
            // This prevents Carbon::parse from accepting relative strings
            // like "next year" or "+999 years" that could cause large loops.
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to'   => 'nullable|date_format:Y-m-d|after_or_equal:date_from',
            'per_page'  => 'nullable|integer|in:15,30,50',
        ]);

        $isAdmin = auth()->user()->hasRole(['super_admin', 'hr_admin']);

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

        $perPage = in_array((int) $request->get('per_page'), self::ALLOWED_PER_PAGE)
            ? (int) $request->get('per_page')
            : 15;

        return response()->json($query->paginate($perPage));
    }

    // ── GET /attendance/team ───────────────────────────────────────────────────

    /**
     * Returns attendance records for all eligible employees.
     * Restricted to super_admin, hr_admin, and accounting.
     */
    public function team(Request $request)
    {
        if (!auth()->user()->hasRole(self::TEAM_ROLES)) {
            abort(403, 'Unauthorized.');
        }

        // FIX: Validate all inputs before using them.
        // date_format:Y-m-d rejects relative strings like "next year".
        // after_or_equal ensures date_to is never before date_from.
        // per_page is restricted to known safe values.
        // page is capped at a reasonable maximum to prevent abuse.
        $request->validate([
            'date'      => 'nullable|date_format:Y-m-d',
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to'   => 'nullable|date_format:Y-m-d|after_or_equal:date_from',
            'status'    => 'nullable|string|in:Present,Late,Absent',
            'role'      => 'nullable|string|in:super_admin,hr_admin,talent_acquisition,accounting,marketing',
            'user_id'   => 'nullable|integer|exists:users,id',
            'per_page'  => 'nullable|integer|in:15,30,50',
            'page'      => 'nullable|integer|min:1|max:1000',
        ]);

        $users = User::with(['roles'])
            ->where('is_active', true)
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', self::ALLOWED_ROLES);
            });

        if ($request->filled('user_id')) {
            $users->where('id', (int) $request->user_id);
        }

        if ($request->filled('role')) {
            $users->whereHas('roles', fn($q) => $q->where('name', $request->role));
        }

        $users = $users->get();

        // Now safe to parse — inputs have already been validated as Y-m-d above.
        $start = $request->filled('date_from')
            ? Carbon::parse($request->date_from)
            : ($request->filled('date')
                ? Carbon::parse($request->date)
                : Carbon::now('Asia/Manila'));

        $end = $request->filled('date_to')
            ? Carbon::parse($request->date_to)
            : $start;

        $dates = [];
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $dates[] = $date->toDateString();
        }

        $attendance = Attendance::whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy(fn($item) => $item->user_id . '_' . $item->date);

        $result = collect();
        foreach ($dates as $date) {
            foreach ($users as $user) {
                $key    = $user->id . '_' . $date;
                $record = $attendance[$key][0] ?? null;

                $result->push([
                    'user'     => $user,
                    'date'     => $date,
                    'time_in'  => $record->time_in  ?? null,
                    'time_out' => $record->time_out ?? null,
                    'status'   => $record->status   ?? 'Absent',
                ]);
            }
        }

        if ($request->filled('status')) {
            $result = $result->filter(
                fn($item) => $item['status'] === $request->status
            )->values();
        }

        // FIX: per_page and page are now validated above.
        // Casting to int after validation is safe.
        $perPage = in_array((int) $request->get('per_page'), self::ALLOWED_PER_PAGE)
            ? (int) $request->get('per_page')
            : 15;
        $page    = max(1, (int) $request->get('page', 1));

        $paginated = $result->slice(($page - 1) * $perPage, $perPage)->values();

        return response()->json([
            'data'         => $paginated,
            'total'        => $result->count(),
            'current_page' => $page,
            'per_page'     => $perPage,
        ]);
    }

    // ── GET /attendance/team/export ────────────────────────────────────────────

    /**
     * Exports the team attendance report as a downloadable PDF.
     *
     * SECURITY FIX: Removed the query-param token fallback entirely.
     * The token must only travel in the Authorization header, which Sanctum
     * resolves automatically before this method is called. Accepting tokens
     * in the URL exposes them in browser history, server logs, and Referer
     * headers — all places an attacker could read them from.
     *
     * The frontend uses apiClient (axios) with responseType: 'blob', which
     * sends the Authorization header correctly without needing a URL token.
     */
    public function exportPdf(Request $request)
    {
        // Sanctum has already resolved the Bearer token from the Authorization
        // header by this point. We just check the role normally.
        if (!auth()->check() || !auth()->user()->hasRole(self::TEAM_ROLES)) {
            abort(403, 'Unauthorized.');
        }

        // FIX: Same input validation as team() — dates must be real Y-m-d
        // strings, status and role must be known values.
        $request->validate([
            'date'      => 'nullable|date_format:Y-m-d',
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to'   => 'nullable|date_format:Y-m-d|after_or_equal:date_from',
            'status'    => 'nullable|string|in:Present,Late,Absent',
            'role'      => 'nullable|string|in:super_admin,hr_admin,talent_acquisition,accounting,marketing',
            'user_id'   => 'nullable|integer|exists:users,id',
        ]);

        $users = User::with(['roles'])
            ->where('is_active', true)
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', self::ALLOWED_ROLES);
            });

        if ($request->filled('user_id')) {
            $users->where('id', (int) $request->user_id);
        }

        if ($request->filled('role')) {
            $users->whereHas('roles', fn($q) => $q->where('name', $request->role));
        }

        $users = $users->get();

        // Safe to parse — validated as Y-m-d above.
        $start = $request->filled('date_from')
            ? Carbon::parse($request->date_from)
            : ($request->filled('date')
                ? Carbon::parse($request->date)
                : Carbon::now('Asia/Manila'));

        $end = $request->filled('date_to')
            ? Carbon::parse($request->date_to)
            : $start;

        $dates = [];
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $dates[] = $date->toDateString();
        }

        $attendance = Attendance::whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy(fn($item) => $item->user_id . '_' . $item->date);

        $result = collect();
        foreach ($users as $user) {
            foreach ($dates as $date) {
                $key    = $user->id . '_' . $date;
                $record = $attendance[$key][0] ?? null;

                $result->push([
                    'user'     => $user,
                    'date'     => $date,
                    'time_in'  => $record->time_in  ?? null,
                    'time_out' => $record->time_out ?? null,
                    'status'   => $record->status   ?? 'Absent',
                ]);
            }
        }

        if ($request->filled('status')) {
            $result = $result->filter(
                fn($i) => $i['status'] === $request->status
            )->values();
        }

        $stats = [
            'total'   => $result->count(),
            'present' => $result->where('status', 'Present')->count(),
            'late'    => $result->where('status', 'Late')->count(),
            'absent'  => $result->where('status', 'Absent')->count(),
        ];

        $periodLabel = $start->isSameDay($end)
            ? $start->format('d M Y')
            : $start->format('d M Y') . ' – ' . $end->format('d M Y');

        $pdf = Pdf::loadView('AttendancePDF.attendance-team', [
            'records'     => $result,
            'stats'       => $stats,
            'periodLabel' => $periodLabel,
            'filterLabel' => $request->filled('role') ? $request->role : 'All Departments',
            'generatedAt' => Carbon::now('Asia/Manila')->format('d M Y, h:i A'),
        ])->setPaper('a4', 'landscape');

        $filename = 'attendance_' . $start->format('Ymd') . '_' . $end->format('Ymd') . '.pdf';

        return $pdf->download($filename);
    }

    // ── Private Helpers ────────────────────────────────────────────────────────

    /**
     * Aborts with 403 if the authenticated user cannot use attendance features.
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
     * Aborts with 422 if coordinates are outside the allowed office radius.
     * This is a server-side guard — the frontend check is UX only.
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