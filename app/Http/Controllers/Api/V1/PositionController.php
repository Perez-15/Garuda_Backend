<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PositionController extends Controller
{
    public function index(Request $request)
    {
        // ← add 'creator' to eager loads
        $query = Position::with('client', 'branch', 'creator');

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }
        if ($request->filled('preset')) {
            $now = Carbon::now();
            switch ($request->preset) {
                case 'today':
                    $query->whereDate('created_at', $now->toDateString()); break;
                case 'week':
                    $query->whereBetween('created_at', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()]); break;
                case 'month':
                    $query->whereBetween('created_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()]); break;
            }
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhereHas('client', fn ($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        $query->orderBy('created_at', 'desc');

        return response()->json($query->paginate($request->get('per_page', 15)));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id'   => 'required|exists:clients,id',
            'branch_id'   => 'nullable|exists:branches,id',
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'slots'       => 'required|integer|min:1',
            'is_active'   => 'boolean',
        ]);

        // ← stamp the authenticated user
        $validated['created_by'] = Auth::id();

        $position = Position::create($validated);

        return response()->json([
            'message'  => 'Position created successfully',
            'position' => $position->load('client', 'branch', 'creator'),
        ], 201);
    }

    public function show(Position $position)
    {
        return response()->json([
            'position' => $position->load('client', 'branch', 'creator'),
        ]);
    }

    public function update(Request $request, Position $position)
    {
        $validated = $request->validate([
            'client_id'   => 'sometimes|exists:clients,id',
            'branch_id'   => 'nullable|exists:branches,id',
            'title'       => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'slots'       => 'sometimes|integer|min:1',
            'is_active'   => 'boolean',
        ]);

        $position->update($validated);

        return response()->json([
            'message'  => 'Position updated successfully',
            'position' => $position->load('client', 'branch', 'creator'),
        ]);
    }

    public function destroy(Position $position)
    {
        $position->delete();
        return response()->json(['message' => 'Position deleted successfully']);
    }
}