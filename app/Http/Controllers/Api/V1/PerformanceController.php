<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PerformanceController extends Controller
{
    /**
     * Apply date filter to a query based on the `date_filter` param.
     * Uses `applied_at` for applicant queries; caller passes the column when needed.
     */
    private function applyDateFilter($query, Request $request, string $column = 'applied_at')
    {
        switch ($request->get('date_filter')) {
            case 'today':
                $query->whereDate($column, today());
                break;
            case 'this_week':
                $query->whereBetween($column, [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'this_month':
                $query->whereMonth($column, now()->month)
                      ->whereYear($column,  now()->year);
                break;
            // no filter = all time
        }
        return $query;
    }


    public function taPerformance(Request $request)
    {
        // Only admins can see this page
        if (!auth()->user()->hasRole(['super_admin', 'hr_admin'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // Fetch all TA users
        $taUsers = User::role('talent_acquisition')
    ->with('branches.client')
    ->get(['id', 'name', 'profile_photo']);

        $results = $taUsers->map(function (User $ta) use ($request) {

            // Base query scoped to this TA
            $base = Applicant::where('created_by', $ta->id)
                             ->whereNull('deleted_at');

            // --- in_process & pooling are ALWAYS current (no date filter) ---
            $inProcess = (clone $base)->where('status', 'active')->count();
            $pooling   = (clone $base)->where('status', 'pooling')->count();

            // --- deployed respects the date filter ---
            $deployedQuery = (clone $base)->where('status', 'hired');
            $this->applyDateFilter($deployedQuery, $request, 'applied_at');
            $deployed = $deployedQuery->count();

            $total      = $inProcess + $deployed + $pooling;
            $efficiency = $total > 0 ? round(($deployed / $total) * 100, 1) : 0;

           return [
    'id'         => $ta->id,
    'name'       => $ta->name,
    'avatar'     => $ta->profile_photo,
    'clients' => $ta->branches->pluck('client.name')->unique()->values()->toArray(),
    'in_process' => $inProcess,
    'deployed'   => $deployed,
    'pooling'    => $pooling,
    'total'      => $total,
    'efficiency' => $efficiency,
];
        });

        // Sort: most deployed first, then by efficiency
        $sorted = $results->sortByDesc('deployed')->sortByDesc('efficiency')->values();

        return response()->json([
            'data'        => $sorted,
            'date_filter' => $request->get('date_filter', 'all'),
        ]);
    }

    public function branchPerformance(Request $request)
    {
        if (!auth()->user()->hasRole(['super_admin', 'hr_admin'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $branches = Branch::with('client')
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->get();

        $results = $branches->map(function (Branch $branch) use ($request) {

            // Active employees at this branch
            $employees = Employee::where('branch_id', $branch->id)
                ->whereNull('deleted_at')
                ->whereNull('date_resigned')
                ->whereNull('date_ended')
                ->count();

            // In-process applicants (optionally date-filtered)
            $inProcessQuery = Applicant::where('branch_id', $branch->id)
                ->where('status', 'active')
                ->whereNull('deleted_at');
            $this->applyDateFilter($inProcessQuery, $request, 'applied_at');
            $inProcess = $inProcessQuery->count();

            // Incomplete docs = employees missing complete requirements
            $incompleteDocs = Employee::where('branch_id', $branch->id)
                ->whereNull('deleted_at')
                ->whereNull('date_resigned')
                ->whereNull('date_ended')
                ->where(function ($q) {
                    $q->where('requirements_status', 'incomplete')
                      ->orWhere('requirements_status', 'pending')
                      ->orWhereNull('requirements_status');
                })
                ->count();

            return [
                'id'              => $branch->id,
                'branch_name'     => $branch->branch_name,
                'location'        => $branch->location,
                'client'          => $branch->client?->name ?? '—',
                'employees'       => $employees,
                'in_process'      => $inProcess,
                'incomplete_docs' => $incompleteDocs,
            ];
        });

        // Sort: most incomplete docs first (biggest blockers), then by in-process
        $sorted = $results->sortByDesc('incomplete_docs')->values();

        return response()->json([
            'data'        => $sorted,
            'date_filter' => $request->get('date_filter', 'all'),
        ]);
    }

  public function taApplicants(Request $request, $taId)
{
    if (!auth()->user()->hasRole(['super_admin', 'hr_admin'])) {
        return response()->json(['message' => 'Unauthorized.'], 403);
    }

    $status = $request->get('status', 'in_process');

    // Deployed tab → employees table linked via applicant_id
    if ($status === 'hired') {
        // Get applicant IDs created by this TA
        $applicantIds = Applicant::where('created_by', $taId)
            ->where('status', 'hired')
            ->whereNull('deleted_at')
            ->pluck('id');

        $employees = Employee::whereIn('applicant_id', $applicantIds)
            ->with('branch')
            ->whereNull('deleted_at')
            ->get([
                'id', 'full_name', 'position',
                'branch_id', 'date_hired', 'employment_status',
                'requirements_status',
            ]);

        return response()->json(['data' => $employees, 'type' => 'employees']);
    }

    // Map frontend tab key → actual DB enum value
    $statusMap = [
        'in_process' => 'active',
        'pooling'    => 'pooling',
        'back_out'   => 'backout', // your DB uses 'backout' not 'back_out'
    ];

    $dbStatus = $statusMap[$status] ?? $status;

    $applicants = Applicant::where('created_by', $taId)
        ->where('status', $dbStatus)
        ->whereNull('deleted_at')
         ->with(['branch', 'currentStep'])
        ->get([
            'id', 'full_name', 'branch_id',
            'current_step_id', 'source', 'updated_at',
        ]);

    return response()->json(['data' => $applicants, 'type' => 'applicants']);
}
}