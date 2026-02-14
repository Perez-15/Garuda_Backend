<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Workflow;
use App\Models\Branch;
use Illuminate\Http\Request;

class WorkflowController extends Controller
{
    public function index(Request $request)
    {
        $query = Workflow::with('branch.client')->withCount('steps', 'applicants');

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $workflows = $query->paginate($request->get('per_page', 15));

        return response()->json($workflows);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'workflow_name' => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        $workflow = Workflow::create($validated);

        return response()->json([
            'message' => 'Workflow created successfully',
            'workflow' => $workflow->load('branch.client'),
        ], 201);
    }

    public function show(Workflow $workflow)
    {
        return response()->json([
            'workflow' => $workflow->load('branch.client', 'steps'),
        ]);
    }

    public function update(Request $request, Workflow $workflow)
    {
        $validated = $request->validate([
            'branch_id' => 'sometimes|exists:branches,id',
            'workflow_name' => 'sometimes|string|max:255',
            'is_active' => 'boolean',
        ]);

        $workflow->update($validated);

        return response()->json([
            'message' => 'Workflow updated successfully',
            'workflow' => $workflow->load('branch.client'),
        ]);
    }

    public function destroy(Workflow $workflow)
    {
        $workflow->delete();

        return response()->json([
            'message' => 'Workflow deleted successfully',
        ]);
    }

    public function byBranch(Branch $branch)
    {
        $workflows = $branch->workflows()
            ->withCount('steps', 'applicants')
            ->with('steps')
            ->get();

        return response()->json([
            'workflows' => $workflows,
        ]);
    }

    public function reorderSteps(Request $request, Workflow $workflow)
    {
        $request->validate([
            'steps' => 'required|array',
            'steps.*.id' => 'required|exists:workflow_steps,id',
            'steps.*.step_order' => 'required|integer',
        ]);

        foreach ($request->steps as $stepData) {
            $workflow->steps()
                ->where('id', $stepData['id'])
                ->update(['step_order' => $stepData['step_order']]);
        }

        return response()->json([
            'message' => 'Workflow steps reordered successfully',
            'workflow' => $workflow->load('steps'),
        ]);
    }
}