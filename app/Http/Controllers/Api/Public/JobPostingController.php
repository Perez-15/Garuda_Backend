<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\JobPosting;

class JobPostingController extends Controller
{
    /**
     * Return all active job postings for the public TA website.
     * No authentication required.
     */
    public function index()
{
    $jobs = JobPosting::where('is_active', true)
        ->latest()
        ->get()
        ->map(fn($job) => [
            'id'          => $job->id,
            'title'       => $job->title,
            'location'    => $job->location,
            'category'    => $job->category,
            'salary'      => $job->salary,
            'type'        => $job->type,
            'description' => $job->description,
            'created_at'  => $job->created_at?->toISOString(), // ← fixed
        ]);

    return response()->json($jobs);
}
}
