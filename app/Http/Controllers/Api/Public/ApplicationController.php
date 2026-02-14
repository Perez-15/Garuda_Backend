<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\Workflow;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:applicants,email',
            'phone' => 'required|string|max:20',
            'branch_id' => 'required|exists:branches,id',
            'resume' => 'nullable|file|mimes:pdf,doc,docx|max:5120',
        ]);

        // Get workflow for the branch
        $workflow = Workflow::where('branch_id', $validated['branch_id'])
            ->where('is_active', true)
            ->first();

        if (!$workflow) {
            return response()->json([
                'message' => 'No active workflow found for this branch',
            ], 400);
        }

        // Get first step
        $firstStep = $workflow->steps()->orderBy('step_order')->first();

        // Handle resume upload
        if ($request->hasFile('resume')) {
            $resumePath = $request->file('resume')->store('resumes', 'public');
            $validated['resume_path'] = $resumePath;
        }

        $validated['source'] = 'WordPress';
        $validated['workflow_id'] = $workflow->id;
        $validated['current_step_id'] = $firstStep?->id;
        $validated['applied_at'] = now();

        $applicant = Applicant::create($validated);

        // Log activity
        $applicant->activities()->create([
            'activity_type' => 'created',
            'description' => 'Application submitted via WordPress',
        ]);

        return response()->json([
            'message' => 'Application submitted successfully',
            'applicant_id' => $applicant->id,
        ], 201);
    }
}