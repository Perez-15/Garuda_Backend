<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\WorkflowStep;
use App\Models\Workflow;
use Illuminate\Http\Request;

class WorkflowStepController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'workflow_id' => 'required|exists:workflows,id',
            'step_name' => 'required|string|max:255',
            'step_order' => 'required|integer',
            'is_final_step' => 'boolean',
            'description' => 'nullable|string',
        ]);

        $step = WorkflowStep::create($validated);

        return response()->json([
            'message' => 'Workflow step created successfully',
            'step' => $step,
        ], 201);
    }

    public function show(WorkflowStep $workflowStep)
    {
        return response()->json([
            'step' => $workflowStep->load('workflow'),
        ]);
    }

    public function update(Request $request, WorkflowStep $workflowStep)
    {
        $validated = $request->validate([
            'step_name' => 'sometimes|string|max:255',
            'step_order' => 'sometimes|integer',
            'is_final_step' => 'boolean',
            'description' => 'nullable|string',
        ]);

        $workflowStep->update($validated);

        return response()->json([
            'message' => 'Workflow step updated successfully',
            'step' => $workflowStep,
        ]);
    }

    public function destroy(WorkflowStep $workflowStep)
    {
        $workflowStep->delete();

        return response()->json([
            'message' => 'Workflow step deleted successfully',
        ]);
    }

    public function byWorkflow(Workflow $workflow)
    {
        $steps = $workflow->steps()
            ->orderBy('step_order')
            ->get();

        return response()->json([
            'steps' => $steps,
        ]);
    }
}