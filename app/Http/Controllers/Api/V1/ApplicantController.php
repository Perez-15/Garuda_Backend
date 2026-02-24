<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\ApplicantNote;
use App\Models\Workflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ApplicantController extends Controller
{
    public function index(Request $request)
    {
        $query = Applicant::with(['branch.client', 'workflow', 'currentStep']);

        // ── Filters ──────────────────────────────────────────────

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('source')) {
            $query->where('source', $request->source);
        }

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('current_step_id')) {
            $query->where('current_step_id', $request->current_step_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // ── Date Filter ───────────────────────────────────────────
        // Handles: today, this_week, this_month from frontend sortFilter

        if ($request->has('date_filter')) {
            switch ($request->date_filter) {
                case 'today':
                    $query->whereDate('applied_at', today());
                    break;

                case 'this_week':
                    $query->whereBetween('applied_at', [
                        now()->startOfWeek(),
                        now()->endOfWeek(),
                    ]);
                    break;

                case 'this_month':
                    $query->whereMonth('applied_at', now()->month)
                          ->whereYear('applied_at', now()->year);
                    break;
            }
        }

        // ── Sorting ───────────────────────────────────────────────
        // Whitelist allowed columns to prevent SQL injection

        $allowedSortColumns = ['applied_at', 'full_name'];
        $sortBy  = in_array($request->get('sort_by'), $allowedSortColumns)
                    ? $request->get('sort_by')
                    : 'applied_at';

        // Frontend sends sort_dir (was sort_order before — now fixed)
        $sortDir = in_array($request->get('sort_dir'), ['asc', 'desc'])
                    ? $request->get('sort_dir')
                    : 'desc';

        $query->orderBy($sortBy, $sortDir);

        // ── Paginate ──────────────────────────────────────────────

        $applicants = $query->paginate($request->get('per_page', 15));

        return response()->json($applicants);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email'     => 'required|email|unique:applicants,email',
            'phone'     => 'required|string|max:20',
            'source'    => 'required|string',
            'branch_id' => 'required|exists:branches,id',
            'resume'    => 'nullable|file|mimes:pdf,doc,docx|max:5120',
            'notes'     => 'nullable|string',
        ]);

        // Get active workflow for the branch
        $workflow = Workflow::where('branch_id', $validated['branch_id'])
            ->where('is_active', true)
            ->first();

        if (!$workflow) {
            return response()->json([
                'message' => 'No active workflow found for this branch',
            ], 400);
        }

        // Get first step of workflow
        $firstStep = $workflow->steps()->orderBy('step_order')->first();

        // Handle resume upload
        if ($request->hasFile('resume')) {
            $resumePath = $request->file('resume')->store('resumes', 'public');
            $validated['resume_path'] = $resumePath;
        }

        $validated['workflow_id']      = $workflow->id;
        $validated['current_step_id']  = $firstStep?->id;
        $validated['applied_at']       = now();

        $applicant = Applicant::create($validated);

        // Log activity
        $applicant->activities()->create([
            'user_id'       => auth()->id(),
            'activity_type' => 'created',
            'description'   => 'Applicant created',
        ]);

        return response()->json([
            'message'   => 'Applicant created successfully',
            'applicant' => $applicant->load(['branch.client', 'workflow', 'currentStep']),
        ], 201);
    }

    public function show(Applicant $applicant)
    {
        return response()->json([
            'applicant' => $applicant->load([
                'branch.client',
                'workflow.steps',
                'currentStep',
                'notes.user',
                'activities.user',
            ]),
        ]);
    }

    public function update(Request $request, Applicant $applicant)
    {
        $validated = $request->validate([
            'full_name' => 'sometimes|string|max:255',
            'email'     => 'sometimes|email|unique:applicants,email,' . $applicant->id,
            'phone'     => 'sometimes|string|max:20',
            'notes'     => 'nullable|string',
            'resume'    => 'nullable|file|mimes:pdf,doc,docx|max:5120',
        ]);

        // Handle resume upload
        if ($request->hasFile('resume')) {
            // Delete old resume first
            if ($applicant->resume_path) {
                Storage::disk('public')->delete($applicant->resume_path);
            }
            $resumePath = $request->file('resume')->store('resumes', 'public');
            $validated['resume_path'] = $resumePath;
        }

        $applicant->update($validated);

        // Log activity
        $applicant->activities()->create([
            'user_id'       => auth()->id(),
            'activity_type' => 'updated',
            'description'   => 'Applicant information updated',
        ]);

        return response()->json([
            'message'   => 'Applicant updated successfully',
            'applicant' => $applicant->load(['branch.client', 'workflow', 'currentStep']),
        ]);
    }

    public function destroy(Applicant $applicant)
    {
        // Delete resume file if exists
        if ($applicant->resume_path) {
            Storage::disk('public')->delete($applicant->resume_path);
        }

        $applicant->delete();

        return response()->json([
            'message' => 'Applicant deleted successfully',
        ]);
    }

    public function moveStep(Request $request, Applicant $applicant)
    {
        $request->validate([
            'direction' => 'required|in:next,previous,specific',
            'step_id'   => 'required_if:direction,specific|exists:workflow_steps,id',
        ]);

        $oldStep = $applicant->currentStep;

        if ($request->direction === 'next') {
            $newStep = $oldStep->nextStep();
        } elseif ($request->direction === 'previous') {
            $newStep = $oldStep->previousStep();
        } else {
            $newStep = \App\Models\WorkflowStep::find($request->step_id);
        }

        if (!$newStep) {
            return response()->json([
                'message' => 'No step found in that direction',
            ], 400);
        }

        $applicant->current_step_id = $newStep->id;
        $applicant->save();

        // Log activity
        $applicant->activities()->create([
            'user_id'       => auth()->id(),
            'activity_type' => 'step_change',
            'description'   => "Moved from '{$oldStep->step_name}' to '{$newStep->step_name}'",
            'metadata'      => json_encode([
                'from_step_id' => $oldStep->id,
                'to_step_id'   => $newStep->id,
            ]),
        ]);

        return response()->json([
            'message'   => 'Applicant moved to next step successfully',
            'applicant' => $applicant->load(['branch.client', 'workflow', 'currentStep']),
        ]);
    }

    public function updateStatus(Request $request, Applicant $applicant)
    {
        $request->validate([
            'status' => 'required|in:active,withdrawn,hired,rejected',
        ]);

        $oldStatus       = $applicant->status;
        $applicant->status = $request->status;
        $applicant->save();

        // Log activity
        $applicant->activities()->create([
            'user_id'       => auth()->id(),
            'activity_type' => 'status_change',
            'description'   => "Status changed from '{$oldStatus}' to '{$request->status}'",
        ]);

        return response()->json([
            'message'   => 'Applicant status updated successfully',
            'applicant' => $applicant,
        ]);
    }

    public function addNote(Request $request, Applicant $applicant)
    {
        $request->validate([
            'note' => 'required|string',
        ]);

        $note = ApplicantNote::create([
            'applicant_id' => $applicant->id,
            'user_id'      => auth()->id(),
            'note'         => $request->note,
        ]);

        // Log activity
        $applicant->activities()->create([
            'user_id'       => auth()->id(),
            'activity_type' => 'note_added',
            'description'   => 'Note added to applicant',
        ]);

        return response()->json([
            'message' => 'Note added successfully',
            'note'    => $note->load('user'),
        ], 201);
    }

    public function activities(Applicant $applicant)
    {
        $activities = $applicant->activities()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($activities);
    }
}