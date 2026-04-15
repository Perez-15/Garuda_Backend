<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\WebsiteApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WebsiteApplicationController extends Controller
{
    /**
     * List website applications with optional status filter.
     * Used by the Garuda HR dashboard.
     */
    public function index(Request $request)
    {
        $query = WebsiteApplication::with(['jobPosting:id,title', 'reviewedBy:id,name'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $applications = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'data'  => $applications->items(),
            'meta'  => [
                'current_page' => $applications->currentPage(),
                'last_page'    => $applications->lastPage(),
                'total'        => $applications->total(),
            ],
        ]);
    }

    /**
     * Count of pending applications — used for the notification badge.
     */
    public function pendingCount()
    {
        return response()->json([
            'count' => WebsiteApplication::pending()->count(),
        ]);
    }

    /**
     * Accept an application: creates an Applicant record and marks as accepted.
     * Mirrors the same workflow/step logic as ApplicantController::store().
     */
    public function accept(Request $request, WebsiteApplication $websiteApplication)
    {
        if ($websiteApplication->status !== 'pending') {
            return response()->json(['message' => 'Application has already been reviewed.'], 422);
        }

        $request->validate([
            'branch_id' => 'required|integer|exists:branches,id',
        ]);

        // Auto-detect the active workflow for this branch (same as ApplicantController::store)
        $workflow = \App\Models\Workflow::where('branch_id', $request->branch_id)
            ->where('is_active', true)
            ->first();

        if (!$workflow) {
            return response()->json([
                'message' => 'No active workflow found for this branch. Please assign a workflow first.',
            ], 400);
        }

        $firstStep = $workflow->steps()->orderBy('step_order')->first();

        // Check for duplicate email in applicants table
        if (\App\Models\Applicant::where('email', $websiteApplication->email)->exists()) {
            return response()->json([
                'message' => "An applicant with email {$websiteApplication->email} already exists in the pipeline.",
            ], 422);
        }

        $applicant = null;

        DB::transaction(function () use ($request, $websiteApplication, $workflow, $firstStep, &$applicant) {
            $applicant = Applicant::create([
                'full_name'       => $websiteApplication->full_name,
                'email'           => $websiteApplication->email,
                'phone'           => $websiteApplication->phone,
                'source'          => 'website',
                'resume_path'     => $websiteApplication->resume_path,
                'branch_id'       => $request->branch_id,
                'workflow_id'     => $workflow->id,
                'current_step_id' => $firstStep?->id,
                'applied_at'      => $websiteApplication->created_at,
                'status'          => 'active',
                'created_by'      => $request->user()->id,
            ]);

            $applicant->activities()->create([
                'user_id'       => $request->user()->id,
                'activity_type' => 'created',
                'description'   => 'Applicant created from website application',
            ]);

            $websiteApplication->update([
                'status'                 => 'accepted',
                'reviewed_by'            => $request->user()->id,
                'reviewed_at'            => now(),
                'converted_applicant_id' => $applicant->id,
            ]);
        });

        return response()->json([
            'message'      => 'Application accepted and added to the pipeline.',
            'applicant_id' => $applicant->id,
        ]);
    }

    /**
     * Dismiss an application.
     */
    public function dismiss(Request $request, WebsiteApplication $websiteApplication)
    {
        if ($websiteApplication->status !== 'pending') {
            return response()->json(['message' => 'Application has already been reviewed.'], 422);
        }

        $websiteApplication->update([
            'status'      => 'dismissed',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return response()->json(['message' => 'Application dismissed.']);
    }

    /**
     * Get resume download URL for a website application.
     */
    public function resumeUrl(WebsiteApplication $websiteApplication)
    {
        if (!$websiteApplication->resume_path) {
            return response()->json(['message' => 'No resume on file.'], 404);
        }

        return response()->json([
            'url' => asset('storage/' . $websiteApplication->resume_path),
        ]);
    }
}
