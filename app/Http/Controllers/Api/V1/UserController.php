<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    // ── Index ──────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
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

        if ($request->filled('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('requirements_status')) {
            $query->where('requirements_status', $request->requirements_status);
        }

        if (!auth()->user()->hasRole('super_admin')) {
            $query->whereDoesntHave('roles', fn($q) => $q->where('name', 'super_admin'));
        }

        $perPage = in_array((int) $request->get('per_page'), [15, 30, 50])
            ? (int) $request->get('per_page') : 15;

        $users = $query->orderBy('name')->paginate($perPage);

        // Append profile_photo_url to each user
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

    public function show(User $user)
    {
        return response()->json([
            'user' => $user->load(['roles', 'branches'])->append('profile_photo_url'),
        ]);
    }

    // ── Update ─────────────────────────────────────────────────────────────────

    public function update(Request $request, User $user)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name'                    => 'sometimes|string|max:255',
            'email'                   => 'sometimes|email|unique:users,email,' . $user->id,
            'password'                => 'nullable|string|min:8',
            'role'                    => 'nullable|string|exists:roles,name',
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

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        if (!empty($validated['role'])) {
            $user->syncRoles([$validated['role']]);
            unset($validated['role']);
        }

        if (isset($validated['branch_ids'])) {
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

    /**
     * POST /users/{user}/photo
     * Accepts multipart/form-data with field "photo" (jpg/png/webp, max 2MB)
     *
     * Stores to: profile_photos/{user_id}/photo.jpg
     * Works for local AND S3 — just change FILESYSTEM_DISK in .env
     */
    public function uploadPhoto(Request $request, User $user)
    {
        $this->authorizeAdminOrSelf($user);

        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,webp|max:2048',
        ]);

        // Delete old photo if exists
        if ($user->profile_photo) {
            Storage::disk(config('filesystems.default'))->delete($user->profile_photo);
        }

        // Store new photo
        // Path: profile_photos/{user_id}/filename.ext
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

    /**
     * DELETE /users/{user}/photo
     */
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

        // Clean up photo on delete
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

    public function updateCustomFields(Request $request, User $user)
    {
        $existing = $user->custom_fields ?? [];
        $merged   = array_merge($existing, $request->all());

        $user->custom_fields = $merged;
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
        return response()->json(['branches' => $user->branches]);
    }

    // ── Private Helpers ────────────────────────────────────────────────────────

    private function authorizeAdmin(): void
    {
        if (!auth()->user()->hasRole(['super_admin', 'hr_admin'])) {
            abort(403, 'Unauthorized.');
        }
    }

    private function authorizeAdminOrSelf(User $user): void
    {
        $authUser = auth()->user();
        if (
            $authUser->id !== $user->id &&
            !$authUser->hasRole(['super_admin', 'hr_admin'])
        ) {
            abort(403, 'Unauthorized.');
        }
    }
}