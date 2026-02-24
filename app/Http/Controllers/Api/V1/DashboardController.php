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
        $totalApplicants    = Applicant::count();
        $activeApplicants   = Applicant::where('status', 'active')->count();
        $hiredApplicants    = Applicant::where('status', 'hired')->count();
        $rejectedApplicants = Applicant::where('status', 'rejected')->count();

        // New applicants today
        $newToday = Applicant::whereDate('applied_at', today())->count();

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

        // Branch overview — what the frontend expects
        $branchOverview = Branch::withCount([
                'applicants as total_applicants',
                'applicants as hired_count' => function ($q) {
                    $q->where('status', 'hired');
                },
            ])
            ->get()
            ->map(function ($branch) {
                return [
                    'branch_name'      => $branch->branch_name,
                    'location'         => $branch->location,
                    'total_employees'  => $branch->hired_count,   // hired = deployed employees
                    'total_applicants' => $branch->total_applicants,
                    'pending_docs'     => 0, // Update this when you add document tracking
                ];
            });

        return response()->json([
            'summary' => [
                'total_applicants'        => $totalApplicants,
                'active_applicants'       => $activeApplicants,
                'hired_applicants'        => $hiredApplicants,
                'rejected_applicants'     => $rejectedApplicants,
                'total_clients'           => Client::count(),
                'total_branches'          => Branch::count(),
                'total_workflows'         => Workflow::count(),
                'total_employees'         => $hiredApplicants,    // hired = deployed employees
                'incomplete_requirements' => 0,                   // Update when document tracking is added
                'new_today'               => $newToday,
            ],
            'applicants_by_source' => $applicantsBySource,
            'applicants_by_status' => $applicantsByStatus,
            'recent_applicants'    => $recentApplicants,
            'branch_overview'      => $branchOverview,            // renamed from top_branches
        ]);
    }
}