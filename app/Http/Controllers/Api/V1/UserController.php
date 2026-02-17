<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        // Only HR Admin and Super Admin can view users
        if (!auth()->user()->hasAnyRole(['super_admin', 'hr_admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $users = User::with('roles')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($users);
    }

    public function store(Request $request)
    {
        $currentUser = auth()->user();

        // Check if user has permission
        if (!$currentUser->hasRole(['super_admin', 'hr_admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // HR Admin can only create TA
        // Super Admin can create HR Admin or TA
        $allowedRoles = $currentUser->hasRole('super_admin') 
            ? ['hr_admin', 'talent_acquisition']
            : ['talent_acquisition'];

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => ['required', Rule::in($allowedRoles)],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_active' => true,
        ]);

        $user->assignRole($validated['role']);

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user->load('roles'),
        ], 201);
    }

    public function show(User $user)
    {
        if (!auth()->user()->hasAnyRole(['super_admin', 'hr_admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'user' => $user->load('roles'),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $currentUser = auth()->user();

        if (!$currentUser->hasAnyRole(['super_admin', 'hr_admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Cannot edit super admin (unless you are super admin)
        if ($user->hasRole('super_admin') && !$currentUser->hasRole('super_admin')) {
            return response()->json(['message' => 'Cannot edit Super Admin'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user->load('roles'),
        ]);
    }

    public function destroy(User $user)
    {
        $currentUser = auth()->user();

        if (!$currentUser->hasRole('super_admin')) {
            return response()->json(['message' => 'Only Super Admin can delete users'], 403);
        }

        // Cannot delete yourself
        if ($user->id === $currentUser->id) {
            return response()->json(['message' => 'Cannot delete yourself'], 400);
        }

        // Cannot delete super admin
        if ($user->hasRole('super_admin')) {
            return response()->json(['message' => 'Cannot delete Super Admin'], 403);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully',
        ]);
    }

    public function updateStatus(Request $request, User $user)
    {
        if (!auth()->user()->hasAnyRole(['super_admin', 'hr_admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        // Cannot deactivate super admin
        if ($user->hasRole('super_admin')) {
            return response()->json(['message' => 'Cannot deactivate Super Admin'], 403);
        }

        // Cannot deactivate yourself
        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'Cannot deactivate yourself'], 400);
        }

        $user->update(['is_active' => $validated['is_active']]);

        return response()->json([
            'message' => 'User status updated successfully',
            'user' => $user,
        ]);
    }
}