<?php

namespace Database\Seeders;

use App\Models\Applicant;
use App\Models\User;
use App\Models\Branch;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ApplicantSeeder extends Seeder
{
    // ── Seeder fingerprint ─────────────────────────────────────────────────────
    const SEED_TAG = '[SEEDED]';

    // ── Per-TA counts ──────────────────────────────────────────────────────────

    // In-process spread
    // [ label, count, applied_at_from, applied_at_to ]
    const INPROCESS_PERIODS = [
        ['recent', 2, '-7 days',   'now'     ],
        ['older',  1, '-3 months', '-8 days' ],
    ];

    // Pooling spread
    const POOLING_PERIODS = [
        ['recent', 1, '-14 days',  'now'      ],
        ['older',  1, '-4 months', '-15 days' ],
    ];

    // Hired spread (applicant status only — no Employee record created)
    // [ label, count, applied_at_from, applied_at_to, date_hired placeholder (unused) ]
    const HIRED_PERIODS = [
        ['today',       1, '-3 hours',  'now'      ],
        ['this_week',   2, '-6 days',   '-1 day'   ],
        ['this_month',  2, '-25 days',  '-7 days'  ],
        ['last_month',  2, '-55 days',  '-30 days' ],
        ['older',       1, '-6 months', '-56 days' ],
    ];

    // ── Name lists ─────────────────────────────────────────────────────────────
    const FIRST_NAMES_MALE = [
        'Juan', 'Jose', 'Miguel', 'Carlo', 'Marco', 'Rafael', 'Gabriel',
        'Christian', 'Angelo', 'Mark', 'John', 'Paul', 'Ryan', 'Kevin',
        'Daniel', 'Jerome', 'Kenneth', 'Rodel', 'Arvin', 'Danilo',
        'Renato', 'Eduardo', 'Ferdinand', 'Rodrigo', 'Antonio', 'Ricardo',
        'Andrei', 'Kristopher', 'Lance', 'Noel', 'Marvin', 'Alvin',
        'Renz', 'Nico', 'Jomar', 'Rommel', 'Dennis', 'Patrick', 'Neil',
    ];

    const FIRST_NAMES_FEMALE = [
        'Maria', 'Ana', 'Kristina', 'Michelle', 'Angelica', 'Liza',
        'Maricel', 'Joyce', 'Lovely', 'Grace', 'Rhea', 'Jasmine',
        'Christine', 'Katrina', 'Jennilyn', 'Rowena', 'Sheryl', 'Marites',
        'Natividad', 'Rosario', 'Luzviminda', 'Carla', 'Yvonne', 'Dianne',
        'Camille', 'Patricia', 'Abigail', 'Stephanie', 'Hannah', 'Trisha',
        'Lorraine', 'Melanie', 'Erica', 'Gladys', 'Precious', 'Cherry',
        'Maribel', 'Corazon', 'Erlinda', 'Teresita',
    ];

    const LAST_NAMES = [
        'Santos', 'Reyes', 'Cruz', 'Bautista', 'Ocampo', 'Garcia',
        'Mendoza', 'Torres', 'Flores', 'Espiritu', 'Ramos', 'Aquino',
        'Villanueva', 'Gonzales', 'Castillo', 'Morales', 'Dela Cruz',
        'Laguna', 'Magtibay', 'Evangelista', 'Paglinawan', 'Delos Santos',
        'Lim', 'Tan', 'Go', 'Uy', 'Co', 'Chan', 'Sy', 'Chua',
        'Magalang', 'Tolentino', 'Poblete', 'Hernandez', 'Pascual',
        'Manalo', 'Navarro', 'Soriano', 'Aguilar', 'Dimaculangan',
        'Macaraeg', 'Buenaventura', 'Panganiban', 'Salamat', 'Macapagal',
    ];

    const SOURCES = [
        'Website', 'Gmail', 'Facebook', 'BossJobs', 'Walk-in', 'Referral',
    ];

    // ── Entry point ────────────────────────────────────────────────────────────

    public function run(): void
    {
        $this->command->info('🧹 Cleaning up previously seeded applicant records...');
        $this->cleanUp();

        // Strictly only talent_acquisition users — no HR, no admin, no other roles
        $taUsers = User::role('talent_acquisition')->get();

        if ($taUsers->isEmpty()) {
            $this->command->error('No Talent Acquisition users found. Aborting.');
            return;
        }

        $validBranches = Branch::whereHas('workflows', function ($q) {
            $q->where('is_active', true);
        })->get();

        if ($validBranches->isEmpty()) {
            $this->command->error('No branches with active workflows found. Aborting.');
            return;
        }

        $this->command->info("Found {$taUsers->count()} TA user(s), {$validBranches->count()} branch(es) with active workflows.");
        $this->command->info('🌱 Seeding applicants...');

        $totalApplicants = 0;

        foreach ($taUsers as $ta) {
            $this->command->line("  → TA: {$ta->name} (ID: {$ta->id})");

            // ── In-Process ────────────────────────────────────────────────────
            foreach (self::INPROCESS_PERIODS as [, $count, $from, $to]) {
                for ($i = 0; $i < $count; $i++) {
                    $branch    = $validBranches->random();
                    $workflow  = $this->getActiveWorkflow($branch);
                    $step      = $this->getRandomStep($workflow);
                    $appliedAt = fake()->dateTimeBetween($from, $to);

                    Applicant::create(array_merge(
                        $this->makeApplicantData($branch, $workflow, $step, $ta->id, $appliedAt),
                        ['status' => 'active']
                    ));
                    $totalApplicants++;
                }
            }

            // ── Pooling ───────────────────────────────────────────────────────
            foreach (self::POOLING_PERIODS as [, $count, $from, $to]) {
                for ($i = 0; $i < $count; $i++) {
                    $branch    = $validBranches->random();
                    $workflow  = $this->getActiveWorkflow($branch);
                    $step      = $this->getRandomStep($workflow);
                    $appliedAt = fake()->dateTimeBetween($from, $to);

                    Applicant::create(array_merge(
                        $this->makeApplicantData($branch, $workflow, $step, $ta->id, $appliedAt),
                        ['status' => 'pooling']
                    ));
                    $totalApplicants++;
                }
            }

            // ── Hired (status only — no Employee record created) ──────────────
            foreach (self::HIRED_PERIODS as [, $count, $from, $to]) {
                for ($i = 0; $i < $count; $i++) {
                    $branch    = $validBranches->random();
                    $workflow  = $this->getActiveWorkflow($branch);
                    $lastStep  = $workflow->steps()->orderBy('step_order', 'desc')->first();
                    $appliedAt = fake()->dateTimeBetween($from, $to);

                    Applicant::create(array_merge(
                        $this->makeApplicantData($branch, $workflow, $lastStep, $ta->id, $appliedAt),
                        ['status' => 'hired']
                    ));
                    $totalApplicants++;
                }
            }
        }

        // ── Summary ───────────────────────────────────────────────────────────
        $hiredPerTA   = array_sum(array_column(self::HIRED_PERIODS,    1));
        $inprocPerTA  = array_sum(array_column(self::INPROCESS_PERIODS, 1));
        $poolingPerTA = array_sum(array_column(self::POOLING_PERIODS,   1));
        $perTA        = $hiredPerTA + $inprocPerTA + $poolingPerTA;

        $this->command->info('');
        $this->command->info('✅ Seeding complete!');
        $this->command->info("   TAs seeded        : {$taUsers->count()}");
        $this->command->info("   Per TA            : {$perTA} applicants");
        $this->command->info("     In-Process      : {$inprocPerTA}  (2 recent + 1 older)");
        $this->command->info("     Pooling         : {$poolingPerTA}  (1 recent + 1 older)");
        $this->command->info("     Hired           : {$hiredPerTA}  (1 today + 2 this week + 2 this month + 2 last month + 1 older)");
        $this->command->info("   Total applicants  : {$totalApplicants}");
        $this->command->info("   Employees created : 0 (use EmployeeSeeder separately if needed)");
    }

    // ── Clean up previously seeded applicant records ──────────────────────────

    private function cleanUp(): void
    {
        $count = Applicant::withTrashed()
            ->where('notes', 'like', '%' . self::SEED_TAG . '%')
            ->count();

        if ($count > 0) {
            Applicant::withTrashed()
                ->where('notes', 'like', '%' . self::SEED_TAG . '%')
                ->forceDelete();

            $this->command->line("  → Removed {$count} previously seeded applicant(s).");
        } else {
            $this->command->line('  → No previously seeded records found.');
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function getActiveWorkflow(Branch $branch): Workflow
    {
        return $branch->workflows()->where('is_active', true)->first();
    }

    private function getRandomStep(Workflow $workflow): ?WorkflowStep
    {
        return $workflow->steps()->inRandomOrder()->first();
    }

    private function randomName(): array
    {
        $gender    = fake()->randomElement(['male', 'female']);
        $firstName = $gender === 'male'
            ? fake()->randomElement(self::FIRST_NAMES_MALE)
            : fake()->randomElement(self::FIRST_NAMES_FEMALE);
        $lastName  = fake()->randomElement(self::LAST_NAMES);

        return ['full_name' => "{$firstName} {$lastName}", 'gender' => ucfirst($gender)];
    }

    private function randomPhone(): string { return '09' . fake()->numerify('########'); }

    private function makeApplicantData(
        Branch        $branch,
        Workflow      $workflow,
        ?WorkflowStep $step,
        int           $taId,
        \DateTime     $appliedAt
    ): array {
        $name = $this->randomName();

        return [
            'full_name'       => $name['full_name'],
            'email'           => Str::slug($name['full_name']) . '.' . fake()->unique()->numerify('####') . '@example.com',
            'phone'           => $this->randomPhone(),
            'source'          => fake()->randomElement(self::SOURCES),
            'branch_id'       => $branch->id,
            'workflow_id'     => $workflow->id,
            'current_step_id' => $step?->id,
            'applied_at'      => $appliedAt,
            'created_by'      => $taId,   // ← always a TA user ID
            'notes'           => self::SEED_TAG,
        ];
    }
}