<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{

   public function index(Request $request)
{
    // BEFORE: $this->authorizeAdmin();
    // AFTER: All authenticated users can list employees.
    // Admins get full list; others get the same list but frontend restricts
    // the detail view to Personal Information only.

    $authUser = auth()->user();
    $isAdmin  = $authUser->hasRole(['super_admin', 'hr_admin']);

    $query = User::with(['roles', 'branches']);

    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function ($q) use ($search) {
            $q->where('name',            'like', "%{$search}%")
              ->orWhere('email',          'like', "%{$search}%")
              ->orWhere('contact_number', 'like', "%{$search}%");
        });
    }

    if ($request->filled('role')) {
        $query->whereHas('roles', fn($q) => $q->where('name', $request->role));
    }

    if ($request->filled('department') && $request->department !== 'all') {
        $deptRoleMap = [
            'hr'                 => 'hr_admin',
            'talent_acquisition' => 'talent_acquisition',
            'accounting'         => 'accounting',
            'marketing'          => 'marketing',
        ];
        $roleName = $deptRoleMap[$request->department] ?? $request->department;
        $query->whereHas('roles', fn($q) => $q->where('name', $roleName));
    }

    if ($request->filled('employment_status')) {
        $query->where('employment_status', $request->employment_status);
    }

    if ($request->filled('requirements_status')) {
        $query->where('requirements_status', $request->requirements_status);
    }

    // Always hide super_admin accounts from non-super-admins
    if (!$authUser->hasRole('super_admin')) {
        $query->whereDoesntHave('roles', fn($q) => $q->where('name', 'super_admin'));
    }

    $perPage = in_array((int) $request->get('per_page'), [15, 30, 50])
        ? (int) $request->get('per_page') : 15;

    $allowedSorts = ['name', 'date_hired'];
    $allowedDirs  = ['asc', 'desc'];

    $sort      = in_array($request->get('sort'), $allowedSorts) ? $request->get('sort') : 'date_hired';
    $direction = in_array($request->get('direction'), $allowedDirs) ? $request->get('direction') : 'desc';

    $users = $query->orderBy($sort, $direction)->paginate($perPage);

    $users->getCollection()->transform(function ($user) {
        $user->append('profile_photo_url');
        return $user;
    });

    return response()->json($users);
}

    // ── Store ──────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name'                    => 'required|string|max:255',
            'email'                   => 'required|email|unique:users,email',
            'password'                => 'required|string|min:8',
            'role'                    => 'required|string|exists:roles,name',
            'is_active'               => 'boolean',
            'contact_number'          => 'nullable|string|max:20',
            'address'                 => 'nullable|string',
            'date_of_birth'           => 'nullable|date',
            'gender'                  => 'nullable|string|max:20',
            'civil_status'            => 'nullable|string|max:30',
            'emergency_contact_name'  => 'nullable|string|max:100',
            'emergency_contact_number'=> 'nullable|string|max:20',
            'department'              => 'nullable|string|max:100',
            'date_hired'              => 'nullable|date',
            'employment_status'       => 'nullable|string|max:50',
            'nbi_status'              => 'nullable|in:submitted,pending,not_required',
            'medcert_status'          => 'nullable|in:submitted,pending,not_required',
            'police_clearance_status' => 'nullable|in:submitted,pending,not_required',
            'contract_status'         => 'nullable|in:submitted,pending,not_required',
            'sss'                     => 'nullable|string|max:50',
            'pagibig'                 => 'nullable|string|max:50',
            'philhealth'              => 'nullable|string|max:50',
            'tin'                     => 'nullable|string|max:50',
            'requirements_status'     => 'nullable|in:complete,incomplete,pending',
            'custom_fields'           => 'nullable|array',
            'branch_ids'              => 'nullable|array',
            'branch_ids.*'            => 'exists:branches,id',
        ]);

        $user = User::create([
            ...$validated,
            'password' => Hash::make($validated['password']),
        ]);

        $user->assignRole($validated['role']);

        if (!empty($validated['branch_ids'])) {
            $user->branches()->sync($validated['branch_ids']);
        }

        return response()->json([
            'message' => 'User created successfully.',
            'user'    => $user->load(['roles', 'branches'])->append('profile_photo_url'),
        ], 201);
    }

    // ── Show ───────────────────────────────────────────────────────────────────

// UserController.php

public function show(User $user)
{
    $authUser = auth()->user();
    $isAdmin  = $authUser->hasRole(['super_admin', 'hr_admin']);

    $user->load(['roles', 'branches'])->append('profile_photo_url');

    // Admins get the full record
    if ($isAdmin) {
        return response()->json(['user' => $user]);
    }

    // Everyone else only gets Personal Information fields —
    // sensitive fields (gov IDs, salary, documents, custom_fields) are stripped.
    $safe = [
        'id',
        'name',
        'email',
        'contact_number',
        'address',
        'date_of_birth',
        'age',
        'gender',
        'civil_status',
        'emergency_contact_name',
        'emergency_contact_number',
        'profile_photo_url',
        'is_active',
        'date_hired',
        'roles',
        'branches',
    ];

    $filtered = collect($user->toArray())->only($safe)->toArray();

    return response()->json(['user' => $filtered]);
}

    // ── Update ─────────────────────────────────────────────────────────────────

    public function update(Request $request, User $user)
    {
        $authUser = auth()->user();
        $isAdmin  = $authUser->hasRole(['super_admin', 'hr_admin']);
        $isSelf   = $authUser->id === $user->id;

        if (!$isAdmin && !$isSelf) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $selfEditableFields = [
            'name', 'contact_number', 'address', 'date_of_birth',
            'age', 'gender', 'civil_status',
            'emergency_contact_name', 'emergency_contact_number',
        ];

        $validated = $request->validate([
            'name'                     => 'sometimes|string|max:255',
            'email'                    => 'sometimes|email|unique:users,email,' . $user->id,
            'password'                 => 'nullable|string|min:8',
            'role'                     => 'nullable|string|exists:roles,name',
            'is_active'                => 'boolean',
            'contact_number'           => 'nullable|string|max:20',
            'address'                  => 'nullable|string',
            'date_of_birth'            => 'nullable|date',
            'age'                      => 'nullable|integer',
            'gender'                   => 'nullable|string|max:20',
            'civil_status'             => 'nullable|string|max:30',
            'emergency_contact_name'   => 'nullable|string|max:100',
            'emergency_contact_number' => 'nullable|string|max:20',
            'department'               => 'nullable|string|max:100',
            'date_hired'               => 'nullable|date',
            'date_ended'               => 'nullable|date',
            'date_resigned'            => 'nullable|date',
            'daily_rate'               => 'nullable|numeric',
            'employment_status'        => 'nullable|string|max:50',
            'source'                   => 'nullable|string|max:100',
            'remarks'                  => 'nullable|string',
            'nbi_status'               => 'nullable|in:submitted,pending,not_required',
            'medcert_status'           => 'nullable|in:submitted,pending,not_required',
            'police_clearance_status'  => 'nullable|in:submitted,pending,not_required',
            'contract_status'          => 'nullable|in:submitted,pending,not_required',
            'sss'                      => 'nullable|string|max:50',
            'pagibig'                  => 'nullable|string|max:50',
            'philhealth'               => 'nullable|string|max:50',
            'tin'                      => 'nullable|string|max:50',
            'requirements_status'      => 'nullable|in:complete,incomplete,pending',
            'custom_fields'            => 'nullable|array',
            'branch_ids'               => 'nullable|array',
            'branch_ids.*'             => 'exists:branches,id',
        ]);

        if (!$isAdmin) {
            $validated = array_intersect_key($validated, array_flip($selfEditableFields));
        }

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        if ($isAdmin && !empty($validated['role'])) {
            $user->syncRoles([$validated['role']]);
            unset($validated['role']);
        }

        if ($isAdmin && isset($validated['branch_ids'])) {
            $user->branches()->sync($validated['branch_ids']);
            unset($validated['branch_ids']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'User updated successfully.',
            'user'    => $user->load(['roles', 'branches'])->append('profile_photo_url'),
        ]);
    }

    // ── Upload Profile Photo ───────────────────────────────────────────────────

    public function uploadPhoto(Request $request, User $user)
    {
        $this->authorizeAdminOrSelf($user);

        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,webp|max:2048',
        ]);

        if ($user->profile_photo) {
            Storage::disk(config('filesystems.default'))->delete($user->profile_photo);
        }

        $path = $request->file('photo')->store(
            "profile_photos/{$user->id}",
            config('filesystems.default')
        );

        $user->update(['profile_photo' => $path]);

        return response()->json([
            'message'           => 'Profile photo updated.',
            'profile_photo_url' => $user->fresh()->profile_photo_url,
        ]);
    }

    // ── Delete Photo ───────────────────────────────────────────────────────────

    public function deletePhoto(User $user)
    {
        $this->authorizeAdminOrSelf($user);

        if ($user->profile_photo) {
            Storage::disk(config('filesystems.default'))->delete($user->profile_photo);
            $user->update(['profile_photo' => null]);
        }

        return response()->json(['message' => 'Profile photo removed.']);
    }

    // ── Destroy ────────────────────────────────────────────────────────────────

    public function destroy(User $user)
    {
        $this->authorizeAdmin();

        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'You cannot delete your own account.'], 403);
        }

        if ($user->profile_photo) {
            Storage::disk(config('filesystems.default'))->delete($user->profile_photo);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully.']);
    }

    // ── Toggle Active Status ───────────────────────────────────────────────────

    public function updateStatus(Request $request, User $user)
    {
        $this->authorizeAdmin();

        $request->validate(['is_active' => 'required|boolean']);

        if ($user->id === auth()->id() && !$request->is_active) {
            return response()->json(['message' => 'You cannot deactivate your own account.'], 403);
        }

        $user->update(['is_active' => $request->is_active]);

        return response()->json([
            'message' => 'User status updated.',
            'user'    => $user->load(['roles', 'branches'])->append('profile_photo_url'),
        ]);
    }

    // ── Update Requirements ────────────────────────────────────────────────────

    public function updateRequirements(Request $request, User $user)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'nbi_status'              => 'nullable|in:submitted,pending,not_required',
            'medcert_status'          => 'nullable|in:submitted,pending,not_required',
            'police_clearance_status' => 'nullable|in:submitted,pending,not_required',
            'contract_status'         => 'nullable|in:submitted,pending,not_required',
            'sss'                     => 'nullable|string|max:50',
            'pagibig'                 => 'nullable|string|max:50',
            'philhealth'              => 'nullable|string|max:50',
            'tin'                     => 'nullable|string|max:50',
            'requirements_status'     => 'nullable|in:complete,incomplete,pending',
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Requirements updated successfully.',
            'user'    => $user->load(['roles', 'branches'])->append('profile_photo_url'),
        ]);
    }

    // ── Update Custom Fields ───────────────────────────────────────────────────
    // FIX: Added role guard + field whitelist from custom_columns table.
    // Previously: no auth check, accepted any arbitrary key via $request->all().
    // Now: only super_admin/hr_admin can call this, and only valid custom column
    // keys for the internal_employees page are accepted — no other keys pass through.

    public function updateCustomFields(Request $request, User $user)
    {
        // Role guard — TA and other roles cannot write custom fields on user records
        if (!auth()->user()->hasRole(['super_admin', 'hr_admin'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // Self-guard — admins can update anyone; non-admins blocked above already
        // (keeping this explicit for clarity if roles change in the future)
        $authUser = auth()->user();
        if (!$authUser->hasRole(['super_admin', 'hr_admin']) && $authUser->id !== $user->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // Whitelist: only accept keys that exist in custom_columns for this page
        // This prevents injecting arbitrary keys into the JSON column
        $validKeys = \App\Models\CustomColumn::where('page', 'internal_employees')
            ->where('is_fixed', false)
            ->pluck('field_key')
            ->toArray();

        if (empty($validKeys)) {
            return response()->json(['message' => 'No custom columns defined for this page.'], 422);
        }

        $incoming = $request->only($validKeys);

        $existing = $user->custom_fields ?? [];
        $user->custom_fields = array_merge($existing, $incoming);
        $user->save();

        return response()->json([
            'message'       => 'Custom fields updated.',
            'custom_fields' => $user->custom_fields,
        ]);
    }

    // ── Branch Assignments ─────────────────────────────────────────────────────

    public function assignBranches(Request $request, User $user)
    {
        $this->authorizeAdmin();

        $request->validate([
            'branch_ids'   => 'required|array',
            'branch_ids.*' => 'exists:branches,id',
        ]);

        $user->branches()->sync($request->branch_ids);

        return response()->json([
            'message'  => 'Branches assigned successfully.',
            'branches' => $user->branches,
        ]);
    }

    public function branches(User $user)
    {
        // Users can view their own branches; admins can view anyone's
        $authUser = auth()->user();
        if ($authUser->id !== $user->id && !$authUser->hasRole(['super_admin', 'hr_admin'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        return response()->json(['branches' => $user->branches]);
    }

    // ── Private Helpers ────────────────────────────────────────────────────────

    private function authorizeAdmin(): void
    {
        if (!auth()->user()->hasRole(['super_admin', 'hr_admin'])) {
            response()->json(['message' => 'Unauthorized.'], 403)->throwResponse();
        }
    }

    private function authorizeAdminOrSelf(User $user): void
    {
        $authUser = auth()->user();
        if (
            $authUser->id !== $user->id &&
            !$authUser->hasRole(['super_admin', 'hr_admin'])
        ) {
            response()->json(['message' => 'Unauthorized.'], 403)->throwResponse();
        }
    }
}