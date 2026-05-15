<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\WebsiteApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ApplicationController extends Controller
{
    /**
     * Receive a job application from the public TA website.
     * No authentication required.
     * Stores into website_applications (staging table) — HR reviews from Garuda.
     */
    public function store(Request $request)
    {
        $request->validate([
            'full_name'       => 'required|string|max:255',
            'email'           => 'required|email|max:255',
            'phone'           => 'required|string|max:20',
            'address'         => 'nullable|string|max:500',
            'position_applied'=> 'required|string|max:255',
            'job_posting_id'  => 'nullable|integer|exists:job_postings,id',
            'resume'          => 'required|file|mimes:pdf,docx,doc|max:5120', // 5MB max
        ]);

        // Store resume in storage/app/public/website-resumes/
        $resumePath = $request->file('resume')->store('website-resumes', 'public');

        $application = WebsiteApplication::create([
            'full_name'        => $request->full_name,
            'email'            => $request->email,
            'phone'            => $request->phone,
            'address'          => $request->address,
            'position_applied' => $request->position_applied,
            'job_posting_id'   => $request->job_posting_id,
            'resume_path'      => $resumePath,
            'status'           => 'pending',
        ]);

        return response()->json([
            'message' => 'Application submitted successfully. We will get in touch with you soon!',
            'id'      => $application->id,
        ], 201);
    }
}
