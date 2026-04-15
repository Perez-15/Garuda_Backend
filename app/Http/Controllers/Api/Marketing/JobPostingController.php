<?php

namespace App\Http\Controllers\Api\Marketing;

use App\Http\Controllers\Controller;
use App\Models\JobPosting;
use Illuminate\Http\Request;

class JobPostingController extends Controller
{
    /**
     * List all job postings (active + inactive) for the marketing dashboard.
     */
    public function index()
    {
        $jobs = JobPosting::with('postedBy:id,name')
            ->latest()
            ->get()
            ->map(fn($job) => $this->format($job));

        return response()->json($jobs);
    }

    /**
     * Create a new job posting.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'location'    => 'required|string|max:255',
            'category'    => 'nullable|string|max:255',
            'salary'      => 'nullable|string|max:100',
            'type'        => 'nullable|in:Full-time,Part-time,Contract',
            'description' => 'nullable|string',
            'is_active'   => 'nullable|boolean',
        ]);

        $data['posted_by'] = $request->user()->id;
        $data['title']     = strtoupper($data['title']);
        $data['is_active'] = $data['is_active'] ?? true;
        $data['salary']    = $data['salary'] ?: 'TBD';

        $job = JobPosting::create($data);

        return response()->json($this->format($job->fresh('postedBy')), 201);
    }

    /**
     * Show a single job posting.
     */
    public function show(JobPosting $jobPosting)
    {
        return response()->json($this->format($jobPosting->load('postedBy:id,name')));
    }

    /**
     * Update a job posting.
     */
    public function update(Request $request, JobPosting $jobPosting)
    {
        $data = $request->validate([
            'title'       => 'sometimes|string|max:255',
            'location'    => 'sometimes|string|max:255',
            'category'    => 'nullable|string|max:255',
            'salary'      => 'nullable|string|max:100',
            'type'        => 'nullable|in:Full-time,Part-time,Contract',
            'description' => 'nullable|string',
            'is_active'   => 'nullable|boolean',
        ]);

        if (isset($data['title'])) {
            $data['title'] = strtoupper($data['title']);
        }

        $jobPosting->update($data);

        return response()->json($this->format($jobPosting->fresh('postedBy')));
    }

    /**
     * Delete a job posting.
     */
    public function destroy(JobPosting $jobPosting)
    {
        $jobPosting->delete();

        return response()->json(['message' => 'Job posting deleted.']);
    }

    /**
     * Toggle is_active status.
     */
    public function toggleActive(JobPosting $jobPosting)
    {
        $jobPosting->update(['is_active' => !$jobPosting->is_active]);

        return response()->json($this->format($jobPosting->fresh()));
    }

    // ── Private Helpers ────────────────────────────────────────────────────────

    private function format(JobPosting $job): array
    {
        return [
            'id'          => $job->id,
            'title'       => $job->title,
            'location'    => $job->location,
            'category'    => $job->category,
            'salary'      => $job->salary,
            'type'        => $job->type,
            'description' => $job->description,
            'is_active'   => $job->is_active,
            'posted_by'   => $job->postedBy?->name,
            'created_at' => $job->created_at?->toISOString(),
            'updated_at'  => $job->updated_at?->toISOString(),
        ];
    }
}
