<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function applicantsBySource(Request $request)
    {
        $query = Applicant::select('source', DB::raw('count(*) as count'))
            ->groupBy('source');

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('applied_at', [$request->start_date, $request->end_date]);
        }

        $data = $query->get();

        return response()->json([
            'data' => $data,
        ]);
    }

    public function applicantsByStatus(Request $request)
    {
        $query = Applicant::select('status', DB::raw('count(*) as count'))
            ->groupBy('status');

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('applied_at', [$request->start_date, $request->end_date]);
        }

        $data = $query->get();

        return response()->json([
            'data' => $data,
        ]);
    }

    public function applicantsByBranch(Request $request)
    {
        $query = Applicant::select('branches.branch_name', DB::raw('count(*) as count'))
            ->join('branches', 'applicants.branch_id', '=', 'branches.id')
            ->groupBy('branches.id', 'branches.branch_name');

        if ($request->has('client_id')) {
            $query->where('branches.client_id', $request->client_id);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('applied_at', [$request->start_date, $request->end_date]);
        }

        $data = $query->get();

        return response()->json([
            'data' => $data,
        ]);
    }

    public function conversionRate(Request $request)
    {
        $query = Applicant::select(
            'workflow_steps.step_name',
            DB::raw('count(*) as count')
        )
            ->join('workflow_steps', 'applicants.current_step_id', '=', 'workflow_steps.id')
            ->groupBy('workflow_steps.id', 'workflow_steps.step_name', 'workflow_steps.step_order')
            ->orderBy('workflow_steps.step_order');

        if ($request->has('workflow_id')) {
            $query->where('applicants.workflow_id', $request->workflow_id);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('applied_at', [$request->start_date, $request->end_date]);
        }

        $data = $query->get();

        return response()->json([
            'data' => $data,
        ]);
    }

    public function export(Request $request)
    {
        // This would use maatwebsite/excel to export data
        // For now, returning JSON
        
        $query = Applicant::with(['branch.client', 'workflow', 'currentStep']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('applied_at', [$request->start_date, $request->end_date]);
        }

        $applicants = $query->get();

        return response()->json([
            'message' => 'Export functionality - integrate with Excel package',
            'count' => $applicants->count(),
        ]);
    }
}