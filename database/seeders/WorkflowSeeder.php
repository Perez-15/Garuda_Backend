<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Branch;
use App\Models\Workflow;
use App\Models\WorkflowStep;

class WorkflowSeeder extends Seeder
{
    public function run(): void
    {
        // Mang Inasal Taguig Workflow
        $taguigBranch = Branch::where('branch_name', 'Mang Inasal Taguig')->first();
        $taguigWorkflow = Workflow::create([
            'branch_id' => $taguigBranch->id,
            'workflow_name' => 'Mang Inasal Taguig Hiring Process',
            'is_active' => true,
        ]);

        $taguigSteps = [
            ['step_name' => 'Resume Received', 'step_order' => 1],
            ['step_name' => 'Screening Call', 'step_order' => 2],
            ['step_name' => 'Endorsement Letter', 'step_order' => 3],
            ['step_name' => '3 Days Training', 'step_order' => 4],
            ['step_name' => 'Confirmation', 'step_order' => 5],
            ['step_name' => 'Orientation', 'step_order' => 6],
            ['step_name' => 'Deployment', 'step_order' => 7, 'is_final_step' => true],
        ];

        foreach ($taguigSteps as $step) {
            WorkflowStep::create([
                'workflow_id' => $taguigWorkflow->id,
                'step_name' => $step['step_name'],
                'step_order' => $step['step_order'],
                'is_final_step' => $step['is_final_step'] ?? false,
            ]);
        }

        // Mang Inasal Taytay Workflow
        $taytayBranch = Branch::where('branch_name', 'Mang Inasal Taytay')->first();
        $taytayWorkflow = Workflow::create([
            'branch_id' => $taytayBranch->id,
            'workflow_name' => 'Mang Inasal Taytay Hiring Process',
            'is_active' => true,
        ]);

        $taytaySteps = [
            ['step_name' => 'Initial Interview', 'step_order' => 1],
            ['step_name' => 'Endorsement Letter', 'step_order' => 2],
            ['step_name' => 'Store Final Interview', 'step_order' => 3],
            ['step_name' => 'Office Orientation', 'step_order' => 4],
            ['step_name' => 'Deployment', 'step_order' => 5, 'is_final_step' => true],
        ];

        foreach ($taytaySteps as $step) {
            WorkflowStep::create([
                'workflow_id' => $taytayWorkflow->id,
                'step_name' => $step['step_name'],
                'step_order' => $step['step_order'],
                'is_final_step' => $step['is_final_step'] ?? false,
            ]);
        }

        // SM North Workflow
        $smNorthBranch = Branch::where('branch_name', 'SM North EDSA')->first();
        $smNorthWorkflow = Workflow::create([
            'branch_id' => $smNorthBranch->id,
            'workflow_name' => 'SM North Hiring Process',
            'is_active' => true,
        ]);

        $smNorthSteps = [
            ['step_name' => 'Initial Interview (Online/Face-to-Face)', 'step_order' => 1],
            ['step_name' => 'Endorsement Letter', 'step_order' => 2],
            ['step_name' => 'Store Interview', 'step_order' => 3],
            ['step_name' => 'Final Interview', 'step_order' => 4],
            ['step_name' => 'Orientation', 'step_order' => 5],
            ['step_name' => 'Deployment', 'step_order' => 6, 'is_final_step' => true],
        ];

        foreach ($smNorthSteps as $step) {
            WorkflowStep::create([
                'workflow_id' => $smNorthWorkflow->id,
                'step_name' => $step['step_name'],
                'step_order' => $step['step_order'],
                'is_final_step' => $step['is_final_step'] ?? false,
            ]);
        }
    }
}