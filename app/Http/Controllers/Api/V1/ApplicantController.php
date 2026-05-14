<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\ApplicantNote;
use App\Models\Employee;
use App\Models\Workflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ApplicantController extends Controller
{
    // ── Helpers ────────────────────────────────────────────────────────────────

    private function allowedBranchIds(): ?array
    {
        return auth()->user()->assignedBranchIds();
    }

    private function scopeToBranches($query)
    {
        $branchIds = $this->allowedBranchIds();
        if ($branchIds !== null) {
            $query->whereIn('branch_id', $branchIds);
        }
        return $query;
    }

    /**
     * Check if the authenticated user can modify (edit/delete/act on) an applicant.
     * - super_admin / hr_admin  → always allowed
     * - talent_acquisition      → only if they personally created the applicant
     */
    private function canModify(Applicant $applicant): bool
    {
        $user = auth()->user();
        if ($user->hasRole(['super_admin', 'hr_admin'])) {
            return true;
        }
        return (int) $applicant->created_by === (int) $user->id;
    }

    private function applyAdminFilters($query, Request $request)
    {
        if (!auth()->user()->hasRole(['super_admin', 'hr_admin'])) {
            return $query;
        }

        if ($request->filled('ta_id')) {
            $query->where('created_by', $request->ta_id);
        }

        if ($request->filled('period')) {
            switch ($request->period) {
                case 'today':
                    $query->whereDate('applied_at', today());
                    break;
                case 'this_week':
                    $query->whereBetween('applied_at', [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
                case 'this_month':
                    $query->whereMonth('applied_at', now()->month)
                          ->whereYear('applied_at',  now()->year);
                    break;
                case 'this_year':
                    $query->whereYear('applied_at', now()->year);
                    break;
            }
        }

        return $query;
    }

    private function applyDateFilter($query, Request $request)
    {
        if (!$request->filled('date_filter')) {
            return $query;
        }

        switch ($request->date_filter) {
            case 'today':
                $query->whereDate('applied_at', today());
                break;
            case 'this_week':
                $query->whereBetween('applied_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'this_month':
                $query->whereMonth('applied_at', now()->month)
                      ->whereYear('applied_at',  now()->year);
                break;
        }

        return $query;
    }

    private function applyCommonFilters($query, Request $request)
    {
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('email',   'like', "%{$search}%")
                  ->orWhere('phone',   'like', "%{$search}%");
            });
        }

        if ($request->get('scope') === 'own') {
            $query->where('created_by', auth()->id());
        }

        $this->applyDateFilter($query, $request);
        $this->applyAdminFilters($query, $request);

        return $query;
    }

    // ── Index ──────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = Applicant::with(['branch.client', 'workflow', 'currentStep', 'createdBy']);

        $this->applyCommonFilters($query, $request);

        $allowedSortColumns = ['applied_at', 'full_name'];
        $sortBy  = in_array($request->get('sort_by'), $allowedSortColumns)
                    ? $request->get('sort_by') : 'applied_at';
        $sortDir = in_array($request->get('sort_dir'), ['asc', 'desc'])
                    ? $request->get('sort_dir') : 'desc';

        $query->orderBy($sortBy, $sortDir);

        $perPage = in_array((int) $request->get('per_page'), [15, 30, 50])
                    ? (int) $request->get('per_page') : 15;

        return response()->json($query->paginate($perPage));
    }

    // ── Stats ──────────────────────────────────────────────────────────────────

    public function stats(Request $request)
    {
        $base = Applicant::query();
        $this->applyCommonFilters($base, $request);

        // Employee base — mirrors the same filters as the applicant base
        $employeeBase = Employee::query();

        if ($request->filled('branch_id')) {
            $employeeBase->where('branch_id', $request->branch_id);
        }
        if ($request->filled('ta_id') && auth()->user()->hasRole(['super_admin', 'hr_admin'])) {
            $employeeBase->where('created_by', $request->ta_id);
        }
        if ($request->filled('period') && auth()->user()->hasRole(['super_admin', 'hr_admin'])) {
            switch ($request->period) {
                case 'today':
                    $employeeBase->whereDate('date_hired', today());
                    break;
                case 'this_week':
                    $employeeBase->whereBetween('date_hired', [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
                case 'this_month':
                    $employeeBase->whereMonth('date_hired', now()->month)
                                 ->whereYear('date_hired', now()->year);
                    break;
                case 'this_year':
                    $employeeBase->whereYear('date_hired', now()->year);
                    break;
            }
        }

        return response()->json([
            'in_process'       => (clone $base)->where('status', 'active')->count(),
            'hired'            => (clone $employeeBase)->count(),
            'active_employees' => (clone $employeeBase)->where(function ($q) {
                                      $q->where('employment_status', 'hired')
                                        ->orWhereNull('employment_status');
                                  })->count(),
            'pooling'          => (clone $base)->where('status', 'pooling')->count(),
            // ── Back outs — applicants who withdrew themselves ─────────────
            'backout'          => (clone $base)->where('status', 'backout')->count(),
            'this_month'       => (clone $base)
                                      ->whereMonth('applied_at', now()->month)
                                      ->whereYear('applied_at',  now()->year)
                                      ->count(),
        ]);
    }

    // ── Store ──────────────────────────────────────────────────────────────────

   public function store(Request $request)
{
    $validated = $request->validate([
        'full_name' => 'required|string|max:255',
        'email'     => 'required|email|unique:applicants,email',
        'phone'     => 'required|string|max:20',
        'source'    => 'required|string',
        'branch_id' => 'required|exists:branches,id',
        'position'  => 'nullable|string|max:255',
        'resume'    => 'nullable|file|mimes:pdf,doc,docx|max:5120',
        'notes'     => 'nullable|string',
    ]);

    $branchIds = $this->allowedBranchIds();
    if ($branchIds !== null && !in_array($validated['branch_id'], $branchIds)) {
        return response()->json(['message' => 'You are not assigned to this branch.'], 403);
    }

    $workflow = Workflow::where('branch_id', $validated['branch_id'])
        ->where('is_active', true)
        ->first();

    if (!$workflow) {
        return response()->json(['message' => 'No active workflow found for this branch.'], 400);
    }

    $firstStep = $workflow->steps()->orderBy('step_order')->first();

    if ($request->hasFile('resume')) {
        $validated['resume_path'] = $request->file('resume')->store('resumes', 'public');
    }

    // Extract notes before passing to create() since it's no longer fillable
    $noteText = $validated['notes'] ?? null;
    unset($validated['notes']);

    $validated['workflow_id']     = $workflow->id;
    $validated['current_step_id'] = $firstStep?->id;
    $validated['applied_at']      = now();
    $validated['created_by']      = auth()->id();
    $validated['status']          = 'active'; // ← moved BEFORE create()

    $applicant = Applicant::create($validated);

    $applicant->activities()->create([
        'user_id'       => auth()->id(),
        'activity_type' => 'created',
        'description'   => 'Applicant created',
    ]);

    if ($noteText) {
        ApplicantNote::create([
            'applicant_id' => $applicant->id,
            'user_id'      => auth()->id(),
            'note'         => $noteText,
        ]);
    }

    return response()->json([
        'message'   => 'Applicant created successfully',
        'applicant' => $applicant->load(['branch.client', 'workflow', 'currentStep', 'createdBy']),
    ], 201);
}
    // ── Show ───────────────────────────────────────────────────────────────────

    public function show(Applicant $applicant)
    {
        return response()->json([
            'applicant' => $applicant->load([
                'branch.client',
                'workflow.steps',
                'currentStep',
                'notes.user',
                'activities.user.roles',
                'createdBy',
                'employee',
            ]),
        ]);
    }

    // ── Update ─────────────────────────────────────────────────────────────────

   public function update(Request $request, Applicant $applicant)
{
    if (!$this->canModify($applicant)) {
        return response()->json(['message' => 'You can only edit applicants you added.'], 403);
    }

    $validated = $request->validate([
        'full_name' => 'sometimes|string|max:255',
        'email'     => 'sometimes|email|unique:applicants,email,' . $applicant->id,
        'phone'     => 'sometimes|string|max:20',
        'source'    => 'sometimes|string',
        'notes'     => 'nullable|string',
        'resume'    => 'nullable|file|mimes:pdf,doc,docx|max:5120',
    ]);

    if ($request->hasFile('resume')) {
        if ($applicant->resume_path) {
            Storage::disk('public')->delete($applicant->resume_path);
        }
        $validated['resume_path'] = $request->file('resume')->store('resumes', 'public');
    }

    // Extract notes before update() since it's no longer fillable
    $noteText = $validated['notes'] ?? null;
    unset($validated['notes']);

    $applicant->update($validated);

    // Save note separately if provided
    if ($noteText) {
        ApplicantNote::create([
            'applicant_id' => $applicant->id,
            'user_id'      => auth()->id(),
            'note'         => $noteText,
        ]);
    }

    $applicant->activities()->create([
        'user_id'       => auth()->id(),
        'activity_type' => 'updated',
        'description'   => 'Applicant information updated',
    ]);

    return response()->json([
        'message'   => 'Applicant updated successfully',
        'applicant' => $applicant->load(['branch.client', 'workflow', 'currentStep', 'createdBy']),
    ]);
}
    // ── Destroy (soft delete) ──────────────────────────────────────────────────

    public function destroy(Applicant $applicant)
    {
        if (!$this->canModify($applicant)) {
            return response()->json(['message' => 'You can only delete applicants you added.'], 403);
        }

        DB::transaction(function () use ($applicant) {
            if ($applicant->employee) {
                $applicant->employee->delete();
            }

            if ($applicant->resume_path) {
                Storage::disk('public')->delete($applicant->resume_path);
            }

            $applicant->delete();
        });

        return response()->json(['message' => 'Applicant (and linked employee record) moved to trash.']);
    }

    // ── Trashed (recently deleted) ─────────────────────────────────────────────

   public function trashed(Request $request)
{
    $user  = auth()->user();
    $query = Applicant::onlyTrashed()
        ->with(['branch.client', 'createdBy', 'employee' => fn($q) => $q->withTrashed()]);

    // TAs only see their own deleted applicants
    if (!$user->hasRole(['super_admin', 'hr_admin'])) {
        $query->where('created_by', $user->id);
    }

    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function ($q) use ($search) {
            $q->where('full_name', 'like', "%{$search}%")
              ->orWhere('email',   'like', "%{$search}%")
              ->orWhere('phone',   'like', "%{$search}%");
        });
    }

    $query->orderBy('deleted_at', 'desc');
    $perPage = in_array((int) $request->get('per_page'), [15, 30, 50]) ? (int) $request->get('per_page') : 15;

    return response()->json($query->paginate($perPage));
}
    // ── Restore ────────────────────────────────────────────────────────────────

    public function restore(int $id)
{
    $user      = auth()->user();
    $applicant = Applicant::onlyTrashed()->findOrFail($id);

    // TA can only restore their own
    if (!$user->hasRole(['super_admin', 'hr_admin']) && (int) $applicant->created_by !== (int) $user->id) {
        return response()->json(['message' => 'Unauthorized.'], 403);
    }

    DB::transaction(function () use ($applicant) {
        $applicant->restore();
        $linked = Employee::withTrashed()->where('applicant_id', $applicant->id)->whereNotNull('deleted_at')->first();
        if ($linked) $linked->restore();
    });

    return response()->json([
        'message'   => 'Applicant restored successfully.',
        'applicant' => $applicant->load(['branch.client', 'createdBy', 'employee']),
    ]);
}

public function forceDelete(int $id)
{
    $user      = auth()->user();
    $applicant = Applicant::onlyTrashed()->findOrFail($id);

    // TA can only force-delete their own
    if (!$user->hasRole(['super_admin', 'hr_admin']) && (int) $applicant->created_by !== (int) $user->id) {
        return response()->json(['message' => 'Unauthorized.'], 403);
    }

    DB::transaction(function () use ($applicant) {
        $linked = Employee::withTrashed()->where('applicant_id', $applicant->id)->first();
        if ($linked) {
            if ($linked->profile_photo) Storage::disk(config('filesystems.default'))->delete($linked->profile_photo);
            $linked->forceDelete();
        }
        if ($applicant->resume_path) Storage::disk('public')->delete($applicant->resume_path);
        $applicant->forceDelete();
    });

    return response()->json(['message' => 'Applicant permanently deleted.']);
}

    // ── Move Step ──────────────────────────────────────────────────────────────

    public function moveStep(Request $request, Applicant $applicant)
    {
        if (!$this->canModify($applicant)) {
            return response()->json(['message' => 'You can only move steps for applicants you added.'], 403);
        }

        $request->validate([
            'direction' => 'required|in:next,previous,specific',
            'step_id'   => 'required_if:direction,specific|exists:workflow_steps,id',
        ]);

        $oldStep = $applicant->currentStep;
        $newStep = match ($request->direction) {
            'next'     => $oldStep->nextStep(),
            'previous' => $oldStep->previousStep(),
            default    => \App\Models\WorkflowStep::find($request->step_id),
        };

        if (!$newStep) {
            return response()->json(['message' => 'No step found in that direction.'], 400);
        }

        $applicant->current_step_id = $newStep->id;
        $applicant->save();

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
            'message'   => 'Applicant moved successfully',
            'applicant' => $applicant->load(['branch.client', 'workflow', 'currentStep']),
        ]);
    }

    // ── Update Status ──────────────────────────────────────────────────────────

    public function updateStatus(Request $request, Applicant $applicant)
    {
        if (!$this->canModify($applicant)) {
            return response()->json(['message' => 'You can only update status for applicants you added.'], 403);
        }

        $request->validate([
            // ── backout added — applicant withdrew themselves ──────────────
            'status' => 'required|in:active,pooling,backout',
        ]);

        $oldStatus         = $applicant->status;
        $applicant->status = $request->status;
        $applicant->save();

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

    // ── Add Note ───────────────────────────────────────────────────────────────

    public function addNote(Request $request, Applicant $applicant)
    {
        if (!$this->canModify($applicant)) {
            return response()->json(['message' => 'You can only add notes to applicants you added.'], 403);
        }

        $request->validate(['note' => 'required|string']);

        $note = ApplicantNote::create([
            'applicant_id' => $applicant->id,
            'user_id'      => auth()->id(),
            'note'         => $request->note,
        ]);

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

    // ── Activities ─────────────────────────────────────────────────────────────

    public function activities(Applicant $applicant)
    {
        $activities = $applicant->activities()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($activities);
    }

    // ── Update Custom Fields ───────────────────────────────────────────────────

   public function updateCustomFields(Request $request, Applicant $applicant)
{
    if (!$this->canModify($applicant)) {
        return response()->json(['message' => 'You can only update applicants you added.'], 403);
    }

    // Whitelist only keys defined as custom columns for this page
    $validKeys = \App\Models\CustomColumn::where('page', 'in_process')
        ->where('is_fixed', false)
        ->pluck('field_key')
        ->toArray();

    if (empty($validKeys)) {
        return response()->json(['message' => 'No custom columns defined for this page.'], 422);
    }

    $incoming = $request->only($validKeys);

    $existing = $applicant->custom_fields ?? [];
    $applicant->custom_fields = array_merge($existing, $incoming);
    $applicant->save();

    $applicant->activities()->create([
        'user_id'       => auth()->id(),
        'activity_type' => 'updated',
        'description'   => 'Custom fields updated',
    ]);

    return response()->json([
        'message'       => 'Custom fields updated.',
        'custom_fields' => $applicant->custom_fields,
    ]);
}
}