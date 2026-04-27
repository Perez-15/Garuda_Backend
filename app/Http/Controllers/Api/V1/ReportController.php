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
    // ── Applicants by Branch ───────────────────────────────────────────────────
public function applicantsByBranch(Request $request)
{
    $query = Applicant::select(
            'branches.id as branch_id',
            'branches.branch_name',
            'clients.id as client_id',        // ← added
            'clients.name as client_name',    // ← added
            DB::raw('count(*) as count')
        )
        ->join('branches', 'applicants.branch_id', '=', 'branches.id')
        ->join('clients', 'branches.client_id', '=', 'clients.id')   // ← added
        ->groupBy('branches.id', 'branches.branch_name', 'clients.id', 'clients.name')
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
    // FIX: Query directly from applicants.created_by + applicants.status
    // instead of relying on applicant_activities description text matching
    // which was causing hired count to show 0 after convertFromApplicant()
    public function topRecruiters(Request $request)
    {
        $query = DB::table('users')
            ->join('applicants', 'applicants.created_by', '=', 'users.id')
            ->select(
                'users.id',
                'users.name',
                // Count all applicants this user created
                DB::raw('COUNT(DISTINCT applicants.id) as total_added'),
                // Count hired directly from applicants.status — reliable regardless
                // of how the conversion happened (convertFromApplicant or manual)
                DB::raw('COUNT(DISTINCT CASE WHEN applicants.status = "hired" THEN applicants.id END) as total_hired'),
                // Count step movements from activity log (kept for reference)
                DB::raw('COUNT(DISTINCT CASE WHEN applicant_activities.activity_type = "step_change" THEN applicant_activities.id END) as total_steps_moved')
            )
            ->leftJoin('applicant_activities', function ($join) {
                $join->on('applicant_activities.applicant_id', '=', 'applicants.id')
                     ->on('applicant_activities.user_id', '=', 'users.id');
            })
            ->whereNull('applicants.deleted_at');

        // Date filter on when the applicant was created
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('applicants.created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date   . ' 23:59:59',
            ]);
        }

        if ($request->filled('branch_id')) {
            $query->where('applicants.branch_id', $request->branch_id);
        }

        // Use alias to avoid duplicate join conflict with branches
        if ($request->filled('client_id')) {
            $query->join('branches as rb', 'applicants.branch_id', '=', 'rb.id')
                  ->where('rb.client_id', $request->client_id);
        }

        $recruiters = $query
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_added')
            ->limit(10)
            ->get()
            ->map(function ($recruiter) {
                $recruiter->conversion_rate = $recruiter->total_added > 0
                    ? round(($recruiter->total_hired / $recruiter->total_added) * 100, 1)
                    : 0;
                return $recruiter;
            });

        return response()->json(['data' => $recruiters]);
    }

    public function applicantsTrend(Request $request)
    {
        $query = Applicant::select(
                DB::raw('DATE(applied_at) as date'),
                DB::raw('COUNT(*) as applicants'),
                DB::raw('SUM(CASE WHEN status = "hired" THEN 1 ELSE 0 END) as hired')
            )
            ->groupBy(DB::raw('DATE(applied_at)'))
            ->orderBy('date');

        $this->applyDateFilter($query, $request);

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->filled('client_id')) {
            $query->whereHas('branch', fn($q) => $q->where('client_id', $request->client_id));
        }

        return response()->json(['data' => $query->get()]);
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
        $pooling         = $applicants->where('status', 'pooling')->count();
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
        // FIX: Same fix as topRecruiters() — use applicants.status directly
        // instead of activity log text matching to avoid 0 hired count bug
        $recruiterQuery = DB::table('users')
            ->join('applicants', 'applicants.created_by', '=', 'users.id')
            ->select(
                'users.name',
                DB::raw('COUNT(DISTINCT applicants.id) as total_added'),
                DB::raw('COUNT(DISTINCT CASE WHEN applicants.status = "hired" THEN applicants.id END) as total_hired')
            )
            ->whereNull('applicants.deleted_at');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $recruiterQuery->whereBetween('applicants.created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date   . ' 23:59:59',
            ]);
        }

        if ($request->filled('branch_id')) {
            $recruiterQuery->where('applicants.branch_id', $request->branch_id);
        }

        // Use alias to avoid duplicate join conflict
        if ($request->filled('client_id')) {
            $recruiterQuery->join('branches as eb', 'applicants.branch_id', '=', 'eb.id')
                           ->where('eb.client_id', $request->client_id);
        }

        $recruiters = $recruiterQuery
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_added')
            ->limit(10)
            ->get()
            ->map(function ($recruiter) {
                $recruiter->conversion_rate = $recruiter->total_added > 0
                    ? round(($recruiter->total_hired / $recruiter->total_added) * 100, 1)
                    : 0;
                return $recruiter;
            });

        // 6. Generate PDF
        $pdf = Pdf::loadView('reports.export', [
            'companyName'     => 'Garuda Recruitment Agency',
            'totalApplicants' => $totalApplicants,
            'hired'           => $hired,
            'active'          => $active,
            'rejected'        => $rejected,
            'pooling'         => $pooling,
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