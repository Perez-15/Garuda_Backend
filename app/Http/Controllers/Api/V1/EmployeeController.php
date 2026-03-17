<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\Employee;
use App\Models\EmployeeHrAction;
use Illuminate\Http\Request;

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
        // 'position' removed from with() — it's now a plain text column
        $query = Employee::with(['branch.client', 'createdBy']);

        $this->scopeToBranches($query);

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where(function ($q) {
                    $q->where('employment_status', 'hired')
                      ->orWhereNull('employment_status');
                });
            } elseif ($request->status !== 's') {
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

        $applicant->status = 'hired';
        $applicant->save();

        $employee = Employee::create([
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

        // 'position' removed from load() — plain text column, no relationship
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

        // 'position' removed from load() — plain text column, no relationship
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

        // 'position' removed from load() — plain text column, no relationship
        return response()->json([
            'employee' => $employee->load([
                'branch.client',
                'applicant',
                'hrActions.createdBy',
                'createdBy',
            ]),
        ]);
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

        // 'position' removed from load() — plain text column, no relationship
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

    // ── Destroy ────────────────────────────────────────────────────────────────

    public function destroy(Employee $employee)
    {
        $branchIds = $this->allowedBranchIds();
        if ($branchIds !== null && !in_array($employee->branch_id, $branchIds)) {
            return response()->json(['message' => 'Access denied.'], 403);
        }

        $employee->delete();

        return response()->json(['message' => 'Employee deleted successfully.']);
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
        ]);

        $validated['employee_id'] = $employee->id;
        $validated['created_by']  = auth()->id();

        $action = EmployeeHrAction::create($validated);

        return response()->json([
            'message' => 'HR action added successfully.',
            'action'  => $action->load('createdBy'),
        ], 201);
    }

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
        ]);

        $action->update($validated);

        return response()->json([
            'message' => 'HR action updated successfully.',
            'action'  => $action->load('createdBy'),
        ]);
    }

    public function deleteHrAction(Employee $employee, EmployeeHrAction $action)
    {
        if ($action->employee_id !== $employee->id) {
            return response()->json(['message' => 'Action does not belong to this employee.'], 403);
        }

        $action->delete();

        return response()->json(['message' => 'HR action deleted successfully.']);
    }

    // ── Custom Fields ─────────────────────────────────────────────────────────

    public function updateCustomFields(Request $request, Employee $employee)
    {
        $branchIds = $this->allowedBranchIds();
        if ($branchIds !== null && !in_array($employee->branch_id, $branchIds)) {
            return response()->json(['message' => 'Access denied.'], 403);
        }

        $existing = $employee->custom_fields ?? [];
        $employee->custom_fields = array_merge($existing, $request->all());
        $employee->save();

        return response()->json([
            'message'       => 'Custom fields updated.',
            'custom_fields' => $employee->custom_fields,
        ]);
    }
}