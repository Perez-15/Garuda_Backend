<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\Client;
use App\Models\Branch;
use App\Models\Workflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Total counts
        $totalApplicants = Applicant::count();
        $activeApplicants = Applicant::where('status', 'active')->count();
        $hiredApplicants = Applicant::where('status', 'hired')->count();
        $rejectedApplicants = Applicant::where('status', 'rejected')->count();

        // Applicants by source
        $applicantsBySource = Applicant::select('source', DB::raw('count(*) as count'))
            ->groupBy('source')
            ->get();

        // Applicants by status
        $applicantsByStatus = Applicant::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        // Recent applicants
        $recentApplicants = Applicant::with(['branch.client', 'currentStep'])
            ->orderBy('applied_at', 'desc')
            ->limit(10)
            ->get();

        // Top branches by applicants
        $topBranches = Branch::withCount('applicants')
            ->orderBy('applicants_count', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'summary' => [
                'total_applicants' => $totalApplicants,
                'active_applicants' => $activeApplicants,
                'hired_applicants' => $hiredApplicants,
                'rejected_applicants' => $rejectedApplicants,
                'total_clients' => Client::count(),
                'total_branches' => Branch::count(),
                'total_workflows' => Workflow::count(),
            ],
            'applicants_by_source' => $applicantsBySource,
            'applicants_by_status' => $applicantsByStatus,
            'recent_applicants' => $recentApplicants,
            'top_branches' => $topBranches,
        ]);
    }
}