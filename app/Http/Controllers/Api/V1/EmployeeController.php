<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\Employee;
use App\Models\EmployeeHrAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmployeeController extends Controller
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

    // ── Index ──────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = Employee::with(['branch.client', 'createdBy']);

        $this->scopeToBranches($query);

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where(function ($q) {
                    $q->where('employment_status', 'hired')
                      ->orWhereNull('employment_status');
                });
            } elseif ($request->status !== 'all') {
    $query->where('employment_status', $request->status);
}
        }

        if ($request->filled('branch_id')) {
            $branchIds = $this->allowedBranchIds();
            if ($branchIds !== null && !in_array($request->branch_id, $branchIds)) {
                return response()->json(['message' => 'Access to this branch is not allowed.'], 403);
            }
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name',       'like', "%{$search}%")
                  ->orWhere('email',         'like', "%{$search}%")
                  ->orWhere('contact_number','like', "%{$search}%");
            });
        }

        if ($request->filled('requirements_status')) {
            $query->where('requirements_status', $request->requirements_status);
        }

        if ($request->filled('ta_id') && auth()->user()->hasRole(['super_admin', 'hr_admin'])) {
            $query->where('created_by', $request->ta_id);
        }

        if ($request->filled('date_hired_from')) {
            $query->whereDate('date_hired', '>=', $request->date_hired_from);
        }
        if ($request->filled('date_hired_to')) {
            $query->whereDate('date_hired', '<=', $request->date_hired_to);
        }

        // Period shorthand — admin only
        if ($request->filled('period') && auth()->user()->hasRole(['super_admin', 'hr_admin'])) {
            switch ($request->period) {
                case 'today':
                    $query->whereDate('date_hired', today());
                    break;
                case 'this_week':
                    $query->whereBetween('date_hired', [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
                case 'this_month':
                    $query->whereMonth('date_hired', now()->month)
                          ->whereYear('date_hired', now()->year);
                    break;
                case 'this_year':
                    $query->whereYear('date_hired', now()->year);
                    break;
            }
        }

        $allowedSortColumns = ['full_name', 'date_hired', 'date_resigned', 'daily_rate', 'employment_status'];
        $sortBy  = in_array($request->get('sort_by'), $allowedSortColumns)
                    ? $request->get('sort_by') : 'date_hired';
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
        $base = Employee::query();
        $this->scopeToBranches($base);

        if ($request->filled('branch_id')) {
            $base->where('branch_id', $request->branch_id);
        }

        $requirementsCounts = (clone $base)
            ->selectRaw('requirements_status, COUNT(*) as total')
            ->groupBy('requirements_status')
            ->pluck('total', 'requirements_status');

        return response()->json([
            's'          => (clone $base)->count(),
            'active'     => (clone $base)->where(function ($q) {
                                $q->where('employment_status', 'hired')
                                  ->orWhereNull('employment_status');
                            })->count(),
            'resigned'   => (clone $base)->where('employment_status', 'resigned')->count(),
            'terminated' => (clone $base)->where('employment_status', 'terminated')->count(),
            'endo'       => (clone $base)->where('employment_status', 'endo')->count(),
            'awol'       => (clone $base)->where('employment_status', 'awol')->count(),
            'requirements_complete'   => $requirementsCounts['complete']   ?? 0,
            'requirements_incomplete' => $requirementsCounts['incomplete'] ?? 0,
            'requirements_pending'    => $requirementsCounts['pending']    ?? 0,
        ]);
    }

    // ── Convert Applicant → Employee ───────────────────────────────────────────
    // Wrapped in a DB transaction so a failed employee insert
    // never leaves the applicant stuck in 'hired' status with no employee record.

    public function convertFromApplicant(Request $request, Applicant $applicant)
    {
        $branchIds = $this->allowedBranchIds();
        if ($branchIds !== null && !in_array($applicant->branch_id, $branchIds)) {
            return response()->json(['message' => 'Access denied.'], 403);
        }

        if (Employee::where('applicant_id', $applicant->id)->exists()) {
            return response()->json(['message' => 'This applicant has already been converted to an employee.'], 409);
        }

        $validated = $request->validate([
            'position'   => 'nullable|string|max:255',
            'date_hired' => 'required|date',
            'daily_rate' => 'nullable|numeric|min:0',
            'remarks'    => 'nullable|string',
        ]);

        $employee = DB::transaction(function () use ($applicant, $validated) {
            // Step 1: mark applicant as hired
            $applicant->status = 'hired';
            $applicant->save();

            // Step 2: create employee record
            // If this fails, the transaction rolls back step 1 automatically
            return Employee::create([
                'applicant_id'      => $applicant->id,
                'branch_id'         => $applicant->branch_id,
                'position'          => $validated['position'] ?? null,
                'full_name'         => $applicant->full_name,
                'email'             => $applicant->email,
                'contact_number'    => $applicant->phone,
                'source'            => $applicant->source,
                'date_hired'        => $validated['date_hired'],
                'daily_rate'        => $validated['daily_rate'] ?? null,
                'remarks'           => $validated['remarks'] ?? null,
                'employment_status' => 'hired',
                'created_by'        => $applicant->created_by,
            ]);
        });

        return response()->json([
            'message'  => 'Applicant successfully converted to employee.',
            'employee' => $employee->load(['branch.client', 'applicant']),
        ], 201);
    }

    // ── Store (manual create) ──────────────────────────────────────────────────

    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_id'                  => 'required|exists:branches,id',
            'position'                   => 'nullable|string|max:255',
            'full_name'                  => 'required|string|max:255',
            'date_of_birth'              => 'nullable|date',
            'gender'                     => 'nullable|in:Male,Female',
            'civil_status'               => 'nullable|in:Single,Married,Widowed,Separated',
            'contact_number'             => 'required|string|max:20',
            'email'                      => 'nullable|email',
            'address'                    => 'nullable|string',
            'emergency_contact_name'     => 'nullable|string|max:255',
            'emergency_contact_number'   => 'nullable|string|max:20',
            'sss'                        => 'nullable|string|max:50',
            'pagibig'                    => 'nullable|string|max:50',
            'philhealth'                 => 'nullable|string|max:50',
            'tin'                        => 'nullable|string|max:50',
            'nbi_status'                 => 'nullable|in:submitted,pending,not_required',
            'nbi_expiry'                 => 'nullable|date',
            'police_clearance_status'    => 'nullable|in:submitted,pending,not_required',
            'police_clearance_expiry'    => 'nullable|date',
            'medcert_status'             => 'nullable|in:submitted,pending,not_required',
            'medcert_expiry'             => 'nullable|date',
            'psa_status'                 => 'nullable|in:submitted,pending,not_required',
            'sss_document_status'        => 'nullable|in:submitted,pending,not_required',
            'philhealth_document_status' => 'nullable|in:submitted,pending,not_required',
            'pagibig_document_status'    => 'nullable|in:submitted,pending,not_required',
            'tin_document_status'        => 'nullable|in:submitted,pending,not_required',
            'coe_status'                 => 'nullable|in:submitted,pending,not_required',
            'tor_diploma_status'         => 'nullable|in:submitted,pending,not_required',
            'valid_id_status'            => 'nullable|in:submitted,pending,not_required',
            'picture_1x1_status'         => 'nullable|in:submitted,pending,not_required',
            'requirements_status'        => 'nullable|in:complete,incomplete,pending',
            'date_hired'                 => 'required|date',
            'daily_rate'                 => 'nullable|numeric|min:0',
            'source'                     => 'nullable|string|max:100',
            'remarks'                    => 'nullable|string',
        ]);

        $branchIds = $this->allowedBranchIds();
        if ($branchIds !== null && !in_array($validated['branch_id'], $branchIds)) {
            return response()->json(['message' => 'You are not assigned to this branch.'], 403);
        }

        $validated['employment_status'] = 'hired';
        $validated['created_by']        = auth()->id();

        $employee = Employee::create($validated);

        return response()->json([
            'message'  => 'Employee created successfully.',
            'employee' => $employee->load(['branch.client']),
        ], 201);
    }

    // ── Show ───────────────────────────────────────────────────────────────────

 public function show(Employee $employee)
{
    $branchIds = $this->allowedBranchIds();
    if ($branchIds !== null && !in_array($employee->branch_id, $branchIds)) {
        return response()->json(['message' => 'Access denied.'], 403);
    }

    $employee->load(['branch.client', 'applicant', 'hrActions.createdBy', 'createdBy']);

    // Append public file_url to each HR action so frontend can preview directly
    $employee->hrActions->each(function ($action) {
        $action->file_url = $action->file_path
            ? Storage::disk('public')->url($action->file_path)
            : null;
    });

    return response()->json(['employee' => $employee]);
}

    // ── Update ─────────────────────────────────────────────────────────────────

    public function update(Request $request, Employee $employee)
    {
        $branchIds = $this->allowedBranchIds();
        if ($branchIds !== null && !in_array($employee->branch_id, $branchIds)) {
            return response()->json(['message' => 'Access denied.'], 403);
        }

        $validated = $request->validate([
            'branch_id'                  => 'sometimes|exists:branches,id',
            'position'                   => 'sometimes|nullable|string|max:255',
            'full_name'                  => 'sometimes|string|max:255',
            'date_of_birth'              => 'nullable|date',
            'gender'                     => 'nullable|in:Male,Female',
            'civil_status'               => 'nullable|in:Single,Married,Widowed,Separated',
            'contact_number'             => 'sometimes|string|max:20',
            'email'                      => 'nullable|email',
            'address'                    => 'nullable|string',
            'emergency_contact_name'     => 'nullable|string|max:255',
            'emergency_contact_number'   => 'nullable|string|max:20',
            'sss'                        => 'nullable|string|max:50',
            'pagibig'                    => 'nullable|string|max:50',
            'philhealth'                 => 'nullable|string|max:50',
            'tin'                        => 'nullable|string|max:50',
            'nbi_status'                 => 'nullable|in:submitted,pending,not_required',
            'nbi_expiry'                 => 'nullable|date',
            'police_clearance_status'    => 'nullable|in:submitted,pending,not_required',
            'police_clearance_expiry'    => 'nullable|date',
            'medcert_status'             => 'nullable|in:submitted,pending,not_required',
            'medcert_expiry'             => 'nullable|date',
            'psa_status'                 => 'nullable|in:submitted,pending,not_required',
            'sss_document_status'        => 'nullable|in:submitted,pending,not_required',
            'philhealth_document_status' => 'nullable|in:submitted,pending,not_required',
            'pagibig_document_status'    => 'nullable|in:submitted,pending,not_required',
            'tin_document_status'        => 'nullable|in:submitted,pending,not_required',
            'coe_status'                 => 'nullable|in:submitted,pending,not_required',
            'tor_diploma_status'         => 'nullable|in:submitted,pending,not_required',
            'valid_id_status'            => 'nullable|in:submitted,pending,not_required',
            'picture_1x1_status'         => 'nullable|in:submitted,pending,not_required',
            'requirements_status'        => 'nullable|in:complete,incomplete,pending',
            'date_hired'                 => 'sometimes|date',
            'date_resigned'              => 'nullable|date',
            'date_ended'                 => 'nullable|date',
            'daily_rate'                 => 'nullable|numeric|min:0',
            'source'                     => 'nullable|string|max:100',
            'remarks'                    => 'nullable|string',
        ]);

        $employee->update($validated);

        return response()->json([
            'message'  => 'Employee updated successfully.',
            'employee' => $employee->load(['branch.client', 'hrActions.createdBy']),
        ]);
    }

    // ── Update Employment Status ───────────────────────────────────────────────

    public function updateStatus(Request $request, Employee $employee)
    {
        $branchIds = $this->allowedBranchIds();
        if ($branchIds !== null && !in_array($employee->branch_id, $branchIds)) {
            return response()->json(['message' => 'Access denied.'], 403);
        }

        $request->validate([
            'employment_status' => 'required|in:hired,resigned,terminated,endo,awol',
            'effective_date'    => 'nullable|date',
        ]);

        $status        = $request->employment_status;
        $effectiveDate = $request->effective_date ?? now()->toDateString();

        $employee->employment_status = $status;

        if ($status === 'resigned') {
            $employee->date_resigned = $effectiveDate;
        } elseif (in_array($status, ['terminated', 'endo', 'awol'])) {
            $employee->date_ended = $effectiveDate;
        }

        $employee->save();

        return response()->json([
            'message'  => "Employment status updated to '{$status}'.",
            'employee' => $employee,
        ]);
    }

    // ── Destroy (soft delete) ──────────────────────────────────────────────────

    public function destroy(Employee $employee)
    {
        $branchIds = $this->allowedBranchIds();
        if ($branchIds !== null && !in_array($employee->branch_id, $branchIds)) {
            return response()->json(['message' => 'Access denied.'], 403);
        }

        $employee->delete();

        return response()->json(['message' => 'Employee moved to trash.']);
    }

    // ── Trashed (recently deleted) ─────────────────────────────────────────────

   public function trashed(Request $request)
{
    $user  = auth()->user();
    $query = Employee::onlyTrashed()->with(['branch.client', 'createdBy']);

    // TAs only see employees they created
    if (!$user->hasRole(['super_admin', 'hr_admin'])) {
        $query->where('created_by', $user->id);
    }

    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function ($q) use ($search) {
            $q->where('full_name',        'like', "%{$search}%")
              ->orWhere('email',          'like', "%{$search}%")
              ->orWhere('contact_number', 'like', "%{$search}%");
        });
    }

    $query->orderBy('deleted_at', 'desc');
    $perPage = in_array((int) $request->get('per_page'), [15, 30, 50]) ? (int) $request->get('per_page') : 15;

    return response()->json($query->paginate($perPage));
}

public function restore(int $id)
{
    $user     = auth()->user();
    $employee = Employee::onlyTrashed()->findOrFail($id);

    if (!$user->hasRole(['super_admin', 'hr_admin']) && (int) $employee->created_by !== (int) $user->id) {
        return response()->json(['message' => 'Unauthorized.'], 403);
    }

    $employee->restore();

    return response()->json([
        'message'  => 'Employee restored successfully.',
        'employee' => $employee->load(['branch.client', 'createdBy']),
    ]);
}

public function forceDelete(int $id)
{
    $user     = auth()->user();
    $employee = Employee::onlyTrashed()->findOrFail($id);

    if (!$user->hasRole(['super_admin', 'hr_admin']) && (int) $employee->created_by !== (int) $user->id) {
        return response()->json(['message' => 'Unauthorized.'], 403);
    }

    if ($employee->profile_photo) {
        Storage::disk(config('filesystems.default'))->delete($employee->profile_photo);
    }

    $employee->forceDelete();

    return response()->json(['message' => 'Employee permanently deleted.']);
}

    // ── HR Actions ─────────────────────────────────────────────────────────────

    public function hrActions(Employee $employee)
    {
        $branchIds = $this->allowedBranchIds();
        if ($branchIds !== null && !in_array($employee->branch_id, $branchIds)) {
            return response()->json(['message' => 'Access denied.'], 403);
        }

        return response()->json(
            $employee->hrActions()->with('createdBy')->paginate(20)
        );
    }

    public function addHrAction(Request $request, Employee $employee)
{
    $branchIds = $this->allowedBranchIds();
    if ($branchIds !== null && !in_array($employee->branch_id, $branchIds)) {
        return response()->json(['message' => 'Access denied.'], 403);
    }
 
    $validated = $request->validate([
        'type'        => 'required|in:memo,ir,loa',
        'subject'     => 'nullable|string|max:255',
        'description' => 'nullable|string',
        'action_date' => 'required|date',
        'loa_start'   => 'nullable|date|required_if:type,loa',
        'loa_end'     => 'nullable|date|after_or_equal:loa_start',
        'file'        => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
    ]);
 
    // Handle file upload
    $filePath = null;
    $fileName = null;
    $fileSize = null;
 
    if ($request->hasFile('file')) {
        $file     = $request->file('file');
        $safeName = Str::slug($employee->full_name);
        $dateStr  = now()->format('Ymd_His');
        $fileName = $file->getClientOriginalName();
        $filePath = $file->storeAs(
            'hr_actions',
            "{$validated['type']}_{$safeName}_{$dateStr}." . $file->getClientOriginalExtension(),
            'public'
        );
        $fileSize = $file->getSize();
    }
 
    $action = EmployeeHrAction::create([
        'employee_id' => $employee->id,
        'type'        => $validated['type'],
        'subject'     => $validated['subject']     ?? null,
        'description' => $validated['description'] ?? null,
        'action_date' => $validated['action_date'],
        'loa_start'   => $validated['loa_start']   ?? null,
        'loa_end'     => $validated['loa_end']      ?? null,
        'file_name'   => $fileName,
        'file_path'   => $filePath,
        'file_size'   => $fileSize,
        'created_by'  => auth()->id(),
    ]);
 
    return response()->json([
        'message' => 'HR action added successfully.',
        'action'  => $action->load('createdBy'),
    ], 201);
}
 
// ── Update HR Action ───────────────────────────────────────────────────────────
 
public function updateHrAction(Request $request, Employee $employee, EmployeeHrAction $action)
{
    if ($action->employee_id !== $employee->id) {
        return response()->json(['message' => 'Action does not belong to this employee.'], 403);
    }
 
    $validated = $request->validate([
        'type'        => 'sometimes|in:memo,ir,loa',
        'subject'     => 'nullable|string|max:255',
        'description' => 'nullable|string',
        'action_date' => 'sometimes|date',
        'loa_start'   => 'nullable|date',
        'loa_end'     => 'nullable|date|after_or_equal:loa_start',
        'file'        => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        'remove_file' => 'nullable|in:1',
    ]);
 
    // Remove existing file if requested
    if ($request->input('remove_file') === '1') {
        if ($action->file_path && Storage::disk('public')->exists($action->file_path)) {
            Storage::disk('public')->delete($action->file_path);
        }
        $validated['file_path'] = null;
        $validated['file_name'] = null;
        $validated['file_size'] = null;
    }
 
    // Replace with new file if provided
    if ($request->hasFile('file')) {
        // Delete the old file first
        if ($action->file_path && Storage::disk('public')->exists($action->file_path)) {
            Storage::disk('public')->delete($action->file_path);
        }
 
        $file     = $request->file('file');
        $safeName = Str::slug($employee->full_name);
        $dateStr  = now()->format('Ymd_His');
        $type     = $request->input('type', $action->type);
 
        $validated['file_name'] = $file->getClientOriginalName();
        $validated['file_path'] = $file->storeAs(
            'hr_actions',
            "{$type}_{$safeName}_{$dateStr}." . $file->getClientOriginalExtension(),
            'public'
        );
        $validated['file_size'] = $file->getSize();
    }
 
    // Remove internal keys before updating
    unset($validated['file'], $validated['remove_file']);
 
    $action->update($validated);
 
    return response()->json([
        'message' => 'HR action updated successfully.',
        'action'  => $action->load('createdBy'),
    ]);
}
 
// ── Delete HR Action ───────────────────────────────────────────────────────────
 
public function deleteHrAction(Employee $employee, EmployeeHrAction $action)
{
    if ($action->employee_id !== $employee->id) {
        return response()->json(['message' => 'Action does not belong to this employee.'], 403);
    }
 
    // Clean up the physical file
    if ($action->file_path && Storage::disk('public')->exists($action->file_path)) {
        Storage::disk('public')->delete($action->file_path);
    }
 
    $action->delete();
 
    return response()->json(['message' => 'HR action deleted successfully.']);
}
 
// ── Get File URL (for viewing in browser) ─────────────────────────────────────
 
public function getHrActionFileUrl(Employee $employee, EmployeeHrAction $action)
{
    if ($action->employee_id !== $employee->id) {
        return response()->json(['message' => 'Action does not belong to this employee.'], 403);
    }
 
    if (!$action->file_path || !Storage::disk('public')->exists($action->file_path)) {
        return response()->json(['message' => 'No file attached to this action.'], 404);
    }
 
    return response()->json([
        'url' => Storage::disk('public')->url($action->file_path),
    ]);
}

    // ── Custom Fields ──────────────────────────────────────────────────────────

   public function updateCustomFields(Request $request, Employee $employee)
{
    // Role guard
    if (!auth()->user()->hasRole(['super_admin', 'hr_admin', 'talent_acquisition'])) {
        return response()->json(['message' => 'Unauthorized.'], 403);
    }

    // Branch guard
    $branchIds = $this->allowedBranchIds();
    if ($branchIds !== null && !in_array($employee->branch_id, $branchIds)) {
        return response()->json(['message' => 'Access denied.'], 403);
    }

    // Only accept keys that exist as custom columns for this page
    $validKeys = \App\Models\CustomColumn::where('page', 'hired')
        ->pluck('field_key')
        ->toArray();

    $incoming = $request->only($validKeys);

    $existing = $employee->custom_fields ?? [];
    $employee->custom_fields = array_merge($existing, $incoming);
    $employee->save();

    return response()->json([
        'message'       => 'Custom fields updated.',
        'custom_fields' => $employee->custom_fields,
    ]);
}
}