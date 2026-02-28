<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        // Base query — count applicants added per user
        $query = DB::table('applicant_activities')
            ->join('users', 'applicant_activities.user_id', '=', 'users.id')
            ->join('applicants', 'applicant_activities.applicant_id', '=', 'applicants.id')
            ->select(
                'users.id',
                'users.name',
                // Total applicants they added
                DB::raw('COUNT(CASE WHEN applicant_activities.activity_type = "created" THEN 1 END) as total_added'),
                // Total they got hired
                DB::raw('COUNT(CASE WHEN applicant_activities.activity_type = "status_change" AND applicant_activities.description LIKE "%to \'hired\'%" THEN 1 END) as total_hired'),
                // Total step movements (shows activity level)
                DB::raw('COUNT(CASE WHEN applicant_activities.activity_type = "step_change" THEN 1 END) as total_steps_moved')
            )
            ->whereNull('applicants.deleted_at');

        // Apply date filter on activity created_at
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('applicant_activities.created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date   . ' 23:59:59',
            ]);
        }

        // Apply branch filter
        if ($request->filled('branch_id')) {
            $query->where('applicants.branch_id', $request->branch_id);
        }

        // Apply client filter
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
                // Calculate personal conversion rate
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
        $query = Applicant::with(['branch.client', 'workflow', 'currentStep']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $this->applyDateFilter($query, $request);

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        $applicants = $query->get();

        return response()->json([
            'message' => 'Export functionality - integrate with Excel package',
            'count'   => $applicants->count(),
        ]);
    }
}