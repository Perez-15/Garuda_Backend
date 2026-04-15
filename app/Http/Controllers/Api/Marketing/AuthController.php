<?php

namespace App\Http\Controllers\Api\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Login for marketing dashboard users.
     * Only users with the 'marketing' role can access the marketing dashboard.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        $user = Auth::user();

        // Only allow marketing role users
        if (!$user->hasAnyRole(['marketing', 'super_admin', 'hr_admin'])) {
            Auth::logout();
            return response()->json([
                'message' => 'Access denied. Marketing access only.',
            ], 403);
        }

        // Revoke old marketing tokens, issue a new one
        $user->tokens()->where('name', 'marketing-token')->delete();
        $token = $user->createToken('marketing-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
            ],
        ]);
    }

    /**
     * Logout — revoke the marketing token.
     */
    public function logout(Request $request)
    {
        $request->user()->tokens()->where('name', 'marketing-token')->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    /**
     * Return the currently authenticated marketing user.
     */
    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'roles' => $user->getRoleNames(),
        ]);
    }
}
