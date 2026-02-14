<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Client;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $query = Branch::with('client')->withCount('workflows', 'applicants');

        if ($request->has('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('branch_name', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        $branches = $query->paginate($request->get('per_page', 15));

        return response()->json($branches);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'branch_name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $branch = Branch::create($validated);

        return response()->json([
            'message' => 'Branch created successfully',
            'branch' => $branch->load('client'),
        ], 201);
    }

    public function show(Branch $branch)
    {
        return response()->json([
            'branch' => $branch->load('client', 'workflows.steps', 'applicants'),
        ]);
    }

    public function update(Request $request, Branch $branch)
    {
        $validated = $request->validate([
            'client_id' => 'sometimes|exists:clients,id',
            'branch_name' => 'sometimes|string|max:255',
            'location' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $branch->update($validated);

        return response()->json([
            'message' => 'Branch updated successfully',
            'branch' => $branch->load('client'),
        ]);
    }

    public function destroy(Branch $branch)
    {
        $branch->delete();

        return response()->json([
            'message' => 'Branch deleted successfully',
        ]);
    }

    public function byClient(Client $client)
    {
        $branches = $client->branches()
            ->withCount('workflows', 'applicants')
            ->get();

        return response()->json([
            'branches' => $branches,
        ]);
    }
}