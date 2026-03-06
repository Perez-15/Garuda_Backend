<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    // ── Index ──────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = User::with(['roles', 'branches']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name',  'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->role($request->role); // Spatie helper
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        $users = $query->orderBy('name')->paginate($request->get('per_page', 20));

        return response()->json($users);
    }

    // ── Store ──────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email',
            'password'   => ['required', Password::min(8)],
            'role'       => 'required|in:super_admin,hr_admin,talent_acquisition',
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => 'exists:branches,id',
        ]);

        // Only hr_admin and super_admin can create super_admin
        if ($validated['role'] === 'super_admin' && !auth()->user()->hasRole('super_admin')) {
            return response()->json(['message' => 'Unauthorized to assign super_admin role.'], 403);
        }

        $user = User::create([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'password'  => Hash::make($validated['password']),
            'is_active' => true,
        ]);

        $user->assignRole($validated['role']);

        // Assign branches (only meaningful for talent_acquisition)
        if (!empty($validated['branch_ids'])) {
            $user->branches()->sync($validated['branch_ids']);
        }

        return response()->json([
            'message' => 'User created successfully',
            'user'    => $user->load(['roles', 'branches.client']),
        ], 201);
    }

    // ── Show ───────────────────────────────────────────────────────────────────

    public function show(User $user)
    {
        return response()->json([
            'user' => $user->load(['roles', 'branches.client']),
        ]);
    }

    // ── Update ─────────────────────────────────────────────────────────────────

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'     => 'sometimes|string|max:255',
            'email'    => 'sometimes|email|unique:users,email,' . $user->id,
            'password' => ['sometimes', Password::min(8)],
            'role'     => 'sometimes|in:super_admin,hr_admin,talent_acquisition',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        // Prevent downgrading/upgrading to super_admin unless you are one
        if (
            isset($validated['role']) &&
            $validated['role'] === 'super_admin' &&
            !auth()->user()->hasRole('super_admin')
        ) {
            return response()->json(['message' => 'Unauthorized to assign super_admin role.'], 403);
        }

        $user->update(collect($validated)->except('role')->toArray());

        if (isset($validated['role'])) {
            $user->syncRoles([$validated['role']]);
        }

        return response()->json([
            'message' => 'User updated successfully',
            'user'    => $user->load(['roles', 'branches.client']),
        ]);
    }

    // ── Destroy ────────────────────────────────────────────────────────────────

    public function destroy(User $user)
    {
        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'You cannot delete your own account.'], 422);
        }

        // Prevent deleting super_admin unless you are one
        if ($user->hasRole('super_admin') && !auth()->user()->hasRole('super_admin')) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

    // ── Toggle Active Status ───────────────────────────────────────────────────

    public function updateStatus(Request $request, User $user)
    {
        $request->validate([
            'is_active' => 'required|boolean',
        ]);

        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'You cannot deactivate your own account.'], 422);
        }

        $user->update(['is_active' => $request->is_active]);

        return response()->json([
            'message' => 'User status updated',
            'user'    => $user->load(['roles', 'branches.client']),
        ]);
    }

    // ── Assign Branches (TA access control) ───────────────────────────────────

    /**
     * Syncs the branches assigned to a talent_acquisition user.
     * Passing an empty array removes all branch access.
     *
     * POST /api/v1/users/{user}/branches
     * Body: { "branch_ids": [1, 2, 3] }
     */
    public function assignBranches(Request $request, User $user)
    {
        $request->validate([
            'branch_ids'   => 'required|array',
            'branch_ids.*' => 'exists:branches,id',
        ]);

        if (!$user->hasRole('talent_acquisition')) {
            return response()->json([
                'message' => 'Branch assignment is only applicable to Talent Acquisition users.',
            ], 422);
        }

        $user->branches()->sync($request->branch_ids);

        return response()->json([
            'message'  => 'Branch access updated successfully',
            'user'     => $user->load(['roles', 'branches.client']),
        ]);
    }

    // ── Get assigned branches for a user ──────────────────────────────────────

    public function branches(User $user)
    {
        return response()->json([
            'branches' => $user->branches()->with('client')->get(),
        ]);
    }
}