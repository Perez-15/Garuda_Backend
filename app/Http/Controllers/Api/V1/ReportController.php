<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;


class ReportController extends Controller
{
    // ── Shared date/filter scope ───────────────────────────────────────────────
    private function applyDateFilter($query, Request $request, string $column = 'applied_at')
    {
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween($column, [
                $request->start_date . ' 00:00:00',
                $request->end_date   . ' 23:59:59',
            ]);
        }
        return $query;
    }

    // ── Applicants by Source ───────────────────────────────────────────────────
    public function applicantsBySource(Request $request)
    {
        $query = Applicant::select('source', DB::raw('count(*) as count'))
            ->groupBy('source')
            ->orderByDesc('count');

        $this->applyDateFilter($query, $request);

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('client_id')) {
            $query->whereHas('branch', fn($q) => $q->where('client_id', $request->client_id));
        }

        return response()->json(['data' => $query->get()]);
    }

    // ── Applicants by Status ───────────────────────────────────────────────────
    public function applicantsByStatus(Request $request)
    {
        $query = Applicant::select('status', DB::raw('count(*) as count'))
            ->groupBy('status');

        $this->applyDateFilter($query, $request);

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('client_id')) {
            $query->whereHas('branch', fn($q) => $q->where('client_id', $request->client_id));
        }

        return response()->json(['data' => $query->get()]);
    }

    // ── Applicants by Branch ───────────────────────────────────────────────────
    public function applicantsByBranch(Request $request)
    {
        $query = Applicant::select(
                'branches.branch_name',
                DB::raw('count(*) as count')
            )
            ->join('branches', 'applicants.branch_id', '=', 'branches.id')
            ->groupBy('branches.id', 'branches.branch_name')
            ->orderByDesc('count');

        $this->applyDateFilter($query, $request);

        if ($request->filled('client_id')) {
            $query->where('branches.client_id', $request->client_id);
        }

        if ($request->filled('branch_id')) {
            $query->where('applicants.branch_id', $request->branch_id);
        }

        return response()->json(['data' => $query->get()]);
    }

    // ── Workflow Conversion Funnel ─────────────────────────────────────────────
    public function conversionRate(Request $request)
    {
        $query = Applicant::select(
                'workflow_steps.step_name',
                'workflow_steps.step_order',
                DB::raw('count(*) as count')
            )
            ->join('workflow_steps', 'applicants.current_step_id', '=', 'workflow_steps.id')
            ->groupBy('workflow_steps.id', 'workflow_steps.step_name', 'workflow_steps.step_order')
            ->orderBy('workflow_steps.step_order');

        $this->applyDateFilter($query, $request);

        if ($request->filled('workflow_id')) {
            $query->where('applicants.workflow_id', $request->workflow_id);
        }

        if ($request->filled('branch_id')) {
            $query->where('applicants.branch_id', $request->branch_id);
        }

        return response()->json(['data' => $query->get()]);
    }

    // ── Top Recruiters ─────────────────────────────────────────────────────────
    public function topRecruiters(Request $request)
    {
        $query = DB::table('applicant_activities')
            ->join('users', 'applicant_activities.user_id', '=', 'users.id')
            ->join('applicants', 'applicant_activities.applicant_id', '=', 'applicants.id')
            ->select(
                'users.id',
                'users.name',
                DB::raw('COUNT(CASE WHEN applicant_activities.activity_type = "created" THEN 1 END) as total_added'),
                DB::raw('COUNT(CASE WHEN applicant_activities.activity_type = "status_change" AND applicant_activities.description LIKE "%to \'hired\'%" THEN 1 END) as total_hired'),
                DB::raw('COUNT(CASE WHEN applicant_activities.activity_type = "step_change" THEN 1 END) as total_steps_moved')
            )
            ->whereNull('applicants.deleted_at');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('applicant_activities.created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date   . ' 23:59:59',
            ]);
        }

        if ($request->filled('branch_id')) {
            $query->where('applicants.branch_id', $request->branch_id);
        }

        if ($request->filled('client_id')) {
            $query->join('branches', 'applicants.branch_id', '=', 'branches.id')
                  ->where('branches.client_id', $request->client_id);
        }

        $recruiters = $query
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_added')
            ->limit(5)
            ->get()
            ->map(function ($recruiter) {
                $recruiter->conversion_rate = $recruiter->total_added > 0
                    ? round(($recruiter->total_hired / $recruiter->total_added) * 100, 1)
                    : 0;
                return $recruiter;
            });

        return response()->json(['data' => $recruiters]);
    }

    // ── Export ─────────────────────────────────────────────────────────────────
    public function export(Request $request)
    {
        // 1. Base Query
        $query = Applicant::query();
        $this->applyDateFilter($query, $request);

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->filled('client_id')) {
            $query->whereHas('branch', fn($q) => $q->where('client_id', $request->client_id));
        }

        $applicants = $query->get();

        // 2. Summary
        $totalApplicants = $applicants->count();
        $hired           = $applicants->where('status', 'hired')->count();
        $active          = $applicants->where('status', 'active')->count();
        $rejected        = $applicants->where('status', 'rejected')->count();
        $conversionRate  = $totalApplicants > 0
            ? round(($hired / $totalApplicants) * 100, 1)
            : 0;

        // 3. Grouped Data
        $bySource = $applicants->groupBy('source')->map(fn($items) => $items->count());
        $byStatus = $applicants->groupBy('status')->map(fn($items) => $items->count());

        // 4. Branch Performance
        $byBranch = Applicant::select('branches.branch_name', DB::raw('count(*) as count'))
            ->join('branches', 'applicants.branch_id', '=', 'branches.id')
            ->groupBy('branches.id', 'branches.branch_name')
            ->orderByDesc('count');

        $this->applyDateFilter($byBranch, $request, 'applicants.created_at');

        if ($request->filled('client_id')) {
            $byBranch->where('branches.client_id', $request->client_id);
        }
        if ($request->filled('branch_id')) {
            $byBranch->where('applicants.branch_id', $request->branch_id);
        }

        $byBranch = $byBranch->get();

        // 5. Top Recruiters
        $recruiterQuery = DB::table('applicant_activities')
            ->join('users', 'applicant_activities.user_id', '=', 'users.id')
            ->join('applicants', 'applicant_activities.applicant_id', '=', 'applicants.id')
            ->select(
                'users.name',
                DB::raw('COUNT(CASE WHEN applicant_activities.activity_type = "created" THEN 1 END) as total_added'),
                DB::raw('COUNT(CASE WHEN applicant_activities.activity_type = "status_change" AND applicant_activities.description LIKE "%to \'hired\'%" THEN 1 END) as total_hired')
            )
            ->whereNull('applicants.deleted_at');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $recruiterQuery->whereBetween('applicant_activities.created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date   . ' 23:59:59',
            ]);
        }

        if ($request->filled('branch_id')) {
            $recruiterQuery->where('applicants.branch_id', $request->branch_id);
        }

        if ($request->filled('client_id')) {
            $recruiterQuery->join('branches', 'applicants.branch_id', '=', 'branches.id')
                           ->where('branches.client_id', $request->client_id);
        }

        $recruiters = $recruiterQuery
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_added')
            ->limit(5)
            ->get();

        // 6. Generate PDF
        $pdf = Pdf::loadView('reports.export', [
            'companyName'     => 'Garuda Recruitment Agency',
            'totalApplicants' => $totalApplicants,
            'hired'           => $hired,
            'active'          => $active,
            'rejected'        => $rejected,
            'conversionRate'  => $conversionRate,
            'bySource'        => $bySource,
            'byStatus'        => $byStatus,
            'byBranch'        => $byBranch,
            'recruiters'      => $recruiters,
            'startDate'       => $request->start_date,
            'endDate'         => $request->end_date,
        ]);

        return $pdf->download('garuda-report-' . now()->format('Y-m-d') . '.pdf');
    }
}