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
        if (!$taguigBranch) {
            $this->command->warn('Branch "Mang Inasal Taguig" not found. Skipping.');
        } else {
            $taguigWorkflow = Workflow::create([
                'branch_id'     => $taguigBranch->id,
                'workflow_name' => 'Mang Inasal Taguig Hiring Process',
                'is_active'     => true,
            ]);

            foreach ([
                ['step_name' => 'Resume Received',   'step_order' => 1],
                ['step_name' => 'Screening Call',     'step_order' => 2],
                ['step_name' => 'Endorsement Letter', 'step_order' => 3],
                ['step_name' => '3 Days Training',    'step_order' => 4],
                ['step_name' => 'Confirmation',       'step_order' => 5],
                ['step_name' => 'Orientation',        'step_order' => 6],
                ['step_name' => 'Deployment',         'step_order' => 7, 'is_final_step' => true],
            ] as $step) {
                WorkflowStep::create([
                    'workflow_id'   => $taguigWorkflow->id,
                    'step_name'     => $step['step_name'],
                    'step_order'    => $step['step_order'],
                    'is_final_step' => $step['is_final_step'] ?? false,
                ]);
            }
        }

        // Mang Inasal Velasquez Workflow
        $velasquezBranch = Branch::where('branch_name', 'Mang Inasal Velasquez')->first();
        if (!$velasquezBranch) {
            $this->command->warn('Branch "Mang Inasal Velasquez" not found. Skipping.');
        } else {
            $velasquezWorkflow = Workflow::create([
                'branch_id'     => $velasquezBranch->id,
                'workflow_name' => 'Mang Inasal Velasquez Hiring Process',
                'is_active'     => true,
            ]);

            foreach ([
                ['step_name' => 'Initial Interview',    'step_order' => 1],
                ['step_name' => 'Endorsement Letter',   'step_order' => 2],
                ['step_name' => 'Store Final Interview', 'step_order' => 3],
                ['step_name' => 'Office Orientation',   'step_order' => 4],
                ['step_name' => 'Deployment',           'step_order' => 5, 'is_final_step' => true],
            ] as $step) {
                WorkflowStep::create([
                    'workflow_id'   => $velasquezWorkflow->id,
                    'step_name'     => $step['step_name'],
                    'step_order'    => $step['step_order'],
                    'is_final_step' => $step['is_final_step'] ?? false,
                ]);
            }
        }

        // Jaicom Ortigas Center Workflow
        $jaicomBranch = Branch::where('branch_name', 'Jaicom Ortigas Center')->first();
        if (!$jaicomBranch) {
            $this->command->warn('Branch "Jaicom Ortigas Center" not found. Skipping.');
        } else {
            $jaicomWorkflow = Workflow::create([
                'branch_id'     => $jaicomBranch->id,
                'workflow_name' => 'Jaicom Ortigas Center Hiring Process',
                'is_active'     => true,
            ]);

            foreach ([
                ['step_name' => 'Initial Interview (Online/Face-to-Face)', 'step_order' => 1],
                ['step_name' => 'Endorsement Letter',                      'step_order' => 2],
                ['step_name' => 'Store Interview',                         'step_order' => 3],
                ['step_name' => 'Final Interview',                         'step_order' => 4],
                ['step_name' => 'Orientation',                             'step_order' => 5],
                ['step_name' => 'Deployment',                              'step_order' => 6, 'is_final_step' => true],
            ] as $step) {
                WorkflowStep::create([
                    'workflow_id'   => $jaicomWorkflow->id,
                    'step_name'     => $step['step_name'],
                    'step_order'    => $step['step_order'],
                    'is_final_step' => $step['is_final_step'] ?? false,
                ]);
            }
        }
    }
}