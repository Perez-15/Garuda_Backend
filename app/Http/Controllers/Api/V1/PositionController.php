<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Position;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    public function index(Request $request)
    {
        $query = Position::with('client', 'branch');

        if ($request->has('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $positions = $query->paginate($request->get('per_page', 15));

        return response()->json($positions);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'branch_id' => 'nullable|exists:branches,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'slots' => 'required|integer|min:1',
            'is_active' => 'boolean',
        ]);

        $position = Position::create($validated);

        return response()->json([
            'message' => 'Position created successfully',
            'position' => $position->load('client', 'branch'),
        ], 201);
    }

    public function show(Position $position)
    {
        return response()->json([
            'position' => $position->load('client', 'branch'),
        ]);
    }

    public function update(Request $request, Position $position)
    {
        $validated = $request->validate([
            'client_id' => 'sometimes|exists:clients,id',
            'branch_id' => 'nullable|exists:branches,id',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'slots' => 'sometimes|integer|min:1',
            'is_active' => 'boolean',
        ]);

        $position->update($validated);

        return response()->json([
            'message' => 'Position updated successfully',
            'position' => $position->load('client', 'branch'),
        ]);
    }

    public function destroy(Position $position)
    {
        $position->delete();

        return response()->json([
            'message' => 'Position deleted successfully',
        ]);
    }
}