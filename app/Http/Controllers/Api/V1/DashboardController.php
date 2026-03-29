<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\Employee;
use App\Models\Branch;
use App\Models\Workflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // ── Stat Cards ────────────────────────────────────────────────────────

        $hiredEmployees         = Employee::whereNull('employment_status')->count();
        $inProcess              = Applicant::where('status', 'active')->count();
        $totalBranches          = Branch::count();
        $incompleteRequirements = Employee::where('requirements_status', 'incomplete')->count();

        // ── Hired per Month (last 6 months) ───────────────────────────────────

        $hiredPerMonth = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $count = Employee::whereYear('date_hired',  $month->year)
                             ->whereMonth('date_hired', $month->month)
                             ->count();

            $hiredPerMonth[] = [
                'month' => $month->format('M'),
                'year'  => $month->year,
                'count' => $count,
            ];
        }

        // ── Hired per Week (last 8 weeks) ─────────────────────────────────────

        $hiredPerWeek = collect(range(7, 0))->map(function ($weeksAgo) {
            $start = Carbon::now()->startOfWeek()->subWeeks($weeksAgo);
            $end   = (clone $start)->endOfWeek();

            return [
                'week'  => 'W' . $start->format('W'), // or use $start->format('M d')
                'count' => Employee::whereBetween('date_hired', [$start, $end])->count(),
            ];
        })->values();

        // ── Applicants by Source ──────────────────────────────────────────────

        $applicantsBySource = Applicant::select('source', DB::raw('count(*) as count'))
            ->whereNotNull('source')
            ->groupBy('source')
            ->orderByDesc('count')
            ->get();

        // ── Branch Overview ───────────────────────────────────────────────────

        $branchOverview = Branch::with('client')
            ->get()
            ->map(function ($branch) {
                $totalEmployees  = Employee::where('branch_id', $branch->id)
                                           ->whereNull('employment_status')
                                           ->count();

                $inProcess       = Applicant::where('branch_id', $branch->id)
                                            ->where('status', 'active')
                                            ->count();

                $incompleteDocs  = Employee::where('branch_id', $branch->id)
                                           ->where('requirements_status', 'incomplete')
                                           ->count();

                return [
                    'branch_name'     => $branch->branch_name,
                    'client_name'     => $branch->client?->name ?? '—',
                    'total_employees' => $totalEmployees,
                    'in_process'      => $inProcess,
                    'incomplete_docs' => $incompleteDocs,
                ];
            });

        // ── Recent Activity ───────────────────────────────────────────────────

        $recentActivity = DB::table('applicant_activities')
            ->join('applicants', 'applicant_activities.applicant_id', '=', 'applicants.id')
            ->join('users',      'applicant_activities.user_id',      '=', 'users.id')
            ->select(
                'applicant_activities.activity_type as type',
                'applicants.full_name as name',
                'applicant_activities.description as detail',
                'applicant_activities.created_at as time'
            )
            ->orderByDesc('applicant_activities.created_at')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'type'   => $item->type,
                    'name'   => $item->name,
                    'detail' => $item->detail,
                    'time'   => Carbon::parse($item->time)->diffForHumans(),
                ];
            });

        // ── Response ─────────────────────────────────────────────────────────

        return response()->json([
            'summary' => [
                'hired_employees'         => $hiredEmployees,
                'in_process'              => $inProcess,
                'total_branches'          => $totalBranches,
                'incomplete_requirements' => $incompleteRequirements,
            ],
            'hired_per_month'      => $hiredPerMonth,
            'hired_per_week'       => $hiredPerWeek, // ✅ ADDED
            'applicants_by_source' => $applicantsBySource,
            'branch_overview'      => $branchOverview,
            'recent_activity'      => $recentActivity,
        ]);
    }
}