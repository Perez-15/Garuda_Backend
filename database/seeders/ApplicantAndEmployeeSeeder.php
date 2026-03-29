<?php

namespace Database\Seeders;

use App\Models\Applicant;
use App\Models\Employee;
use App\Models\User;
use App\Models\Branch;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ApplicantAndEmployeeSeeder extends Seeder
{
    // ── Seeder fingerprint ─────────────────────────────────────────────────────
    const SEED_TAG = '[SEEDED]';

    // ── Per-TA counts ──────────────────────────────────────────────────────────
    // Hired spread across time periods so date filters and reports look realistic
    // [ label, count, applied_at_from, applied_at_to, date_hired_from, date_hired_to ]
    const HIRED_PERIODS = [
        ['today',       1, '-3 hours',  'now',       '-2 hours',  'now'      ],
        ['this_week',   2, '-6 days',   '-1 day',    '-5 days',   '-1 day'   ],
        ['this_month',  2, '-25 days',  '-7 days',   '-20 days',  '-7 days'  ],
        ['last_month',  2, '-55 days',  '-30 days',  '-50 days',  '-30 days' ],
        ['older',       1, '-6 months', '-56 days',  '-5 months', '-56 days' ],
    ];

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

    const POSITIONS = [
        'Service Crew', 'Cashier', 'Supervisor', 'Team Leader',
        'Utility Worker', 'Security Guard', 'Maintenance Staff',
        'Production Operator', 'Warehouse Staff', 'Delivery Rider',
        'Customer Service Representative', 'Admin Assistant',
        'Sales Associate', 'Cook', 'Barista', 'Encoder',
    ];

    const SOURCES = [
        'WordPress', 'Gmail', 'Facebook', 'BossJobs', 'Walk-in', 'Referral',
    ];

    const CITIES = [
        'Quezon City', 'Manila', 'Caloocan', 'Marikina', 'Pasig',
        'Makati', 'Paranaque', 'Las Pinas', 'Muntinlupa', 'Taguig',
        'Valenzuela', 'Malabon', 'Navotas', 'Mandaluyong', 'Pasay',
    ];

    // ── Entry point ────────────────────────────────────────────────────────────

    public function run(): void
    {
        $this->command->info('🧹 Cleaning up previously seeded records...');
        $this->cleanUp();

        $taUsers = User::role('talent_acquisition')->get();
        if ($taUsers->isEmpty()) {
            $this->command->error('No Talent Acquisition users found.');
            return;
        }

        $validBranches = Branch::whereHas('workflows', function ($q) {
            $q->where('is_active', true);
        })->get();

        if ($validBranches->isEmpty()) {
            $this->command->error('No branches with active workflows found.');
            return;
        }

        $this->command->info("Found {$taUsers->count()} TA(s), {$validBranches->count()} branch(es) with active workflows.");
        $this->command->info('🌱 Seeding...');

        $totalApplicants = 0;
        $totalEmployees  = 0;

        foreach ($taUsers as $ta) {
            $this->command->line("  → TA: {$ta->name}");

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

            // ── Hired (applicant → converted to employee) ─────────────────────
            foreach (self::HIRED_PERIODS as [, $count, $appFrom, $appTo, $hireFrom, $hireTo]) {
                for ($i = 0; $i < $count; $i++) {
                    $branch    = $validBranches->random();
                    $workflow  = $this->getActiveWorkflow($branch);
                    $lastStep  = $workflow->steps()->orderBy('step_order', 'desc')->first();
                    $appliedAt = fake()->dateTimeBetween($appFrom, $appTo);
                    $dateHired = fake()->dateTimeBetween($hireFrom, $hireTo);

                    DB::transaction(function () use (
                        $branch, $workflow, $lastStep, $ta,
                        $appliedAt, $dateHired,
                        &$totalApplicants, &$totalEmployees
                    ) {
                        $applicant = Applicant::create(array_merge(
                            $this->makeApplicantData($branch, $workflow, $lastStep, $ta->id, $appliedAt),
                            ['status' => 'hired']
                        ));

                        Employee::create(
                            $this->makeEmployeeData($applicant, $branch, $ta->id, $dateHired)
                        );

                        $totalApplicants++;
                        $totalEmployees++;
                    });
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
        $this->command->info("   TAs              : {$taUsers->count()}");
        $this->command->info("   Per TA           : {$perTA} applicants");
        $this->command->info("     In-Process     : {$inprocPerTA}  (2 recent + 1 older)");
        $this->command->info("     Pooling        : {$poolingPerTA}  (1 recent + 1 older)");
        $this->command->info("     Hired          : {$hiredPerTA}  (1 today + 2 this week + 2 this month + 2 last month + 1 older)");
        $this->command->info("   Total applicants : {$totalApplicants}");
        $this->command->info("   Total employees  : {$totalEmployees} (all linked to hired applicants)");
    }

    // ── Clean up previously seeded records ────────────────────────────────────

    private function cleanUp(): void
    {
        $seededIds = Applicant::withTrashed()
            ->where('notes', 'like', '%' . self::SEED_TAG . '%')
            ->pluck('id');

        if ($seededIds->isNotEmpty()) {
            Employee::withTrashed()
                ->whereIn('applicant_id', $seededIds)
                ->forceDelete();

            Applicant::withTrashed()
                ->whereIn('id', $seededIds)
                ->forceDelete();
        }

        $this->command->line('  → Previous seeded records removed.');
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

    private function randomPhone(): string      { return '09' . fake()->numerify('########'); }
    private function randomSss(): string        { return fake()->numerify('##-#######-#'); }
    private function randomTin(): string        { return fake()->numerify('###-###-###-###'); }
    private function randomPhilhealth(): string { return fake()->numerify('##-#########-#'); }
    private function randomPagibig(): string    { return fake()->numerify('############'); }

    private function randomAddress(): string
    {
        $city = fake()->randomElement(self::CITIES);
        return fake()->buildingNumber() . ' ' . fake()->streetName() . ', ' . $city . ', Metro Manila';
    }

    private function makeApplicantData(
        Branch       $branch,
        Workflow     $workflow,
        ?WorkflowStep $step,
        int          $taId,
        \DateTime    $appliedAt
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
            'created_by'      => $taId,
            'notes'           => self::SEED_TAG,
        ];
    }

    private function makeEmployeeData(
        Applicant $applicant,
        Branch    $branch,
        int       $taId,
        \DateTime $dateHired
    ): array {
        $dob       = fake()->dateTimeBetween('-45 years', '-18 years');
        $docStatus = ['submitted', 'pending', 'not_required'];

        // Weighted 4:1 toward active so the Active tab shows meaningful numbers
        $empStatus = fake()->randomElement([
            'hired', 'hired', 'hired', 'hired',
            'resigned', 'terminated', 'endo', 'awol',
        ]);

        $data = [
            'applicant_id'               => $applicant->id,
            'branch_id'                  => $branch->id,
            'full_name'                  => $applicant->full_name,
            'email'                      => $applicant->email,
            'contact_number'             => $applicant->phone,
            'source'                     => $applicant->source,
            'position'                   => fake()->randomElement(self::POSITIONS),
            'date_of_birth'              => $dob,
            'gender'                     => fake()->randomElement(['Male', 'Female']),
            'civil_status'               => fake()->randomElement(['Single', 'Married', 'Widowed', 'Separated']),
            'address'                    => $this->randomAddress(),
            'emergency_contact_name'     => fake()->randomElement(self::FIRST_NAMES_FEMALE) . ' ' . fake()->randomElement(self::LAST_NAMES),
            'emergency_contact_number'   => $this->randomPhone(),

            // Gov IDs
            'sss'                        => $this->randomSss(),
            'pagibig'                    => $this->randomPagibig(),
            'philhealth'                 => $this->randomPhilhealth(),
            'tin'                        => $this->randomTin(),

            // Document statuses
            'nbi_status'                 => fake()->randomElement($docStatus),
            'police_clearance_status'    => fake()->randomElement($docStatus),
            'medcert_status'             => fake()->randomElement($docStatus),
            'psa_status'                 => fake()->randomElement($docStatus),
            'sss_document_status'        => fake()->randomElement($docStatus),
            'philhealth_document_status' => fake()->randomElement($docStatus),
            'pagibig_document_status'    => fake()->randomElement($docStatus),
            'tin_document_status'        => fake()->randomElement($docStatus),
            'coe_status'                 => fake()->randomElement($docStatus),
            'tor_diploma_status'         => fake()->randomElement($docStatus),
            'valid_id_status'            => fake()->randomElement($docStatus),
            'picture_1x1_status'         => fake()->randomElement($docStatus),

            'requirements_status'        => fake()->randomElement(['complete', 'incomplete', 'pending']),
            'employment_status'          => $empStatus,
            'date_hired'                 => $dateHired,
            'daily_rate'                 => fake()->randomElement([570, 610, 645, 700, 750, 800, 850, 900]),
            'created_by'                 => $taId,
            'remarks'                    => self::SEED_TAG,
        ];

        if ($empStatus === 'resigned') {
            $data['date_resigned'] = fake()->dateTimeBetween($dateHired, 'now');
        } elseif (in_array($empStatus, ['terminated', 'endo', 'awol'])) {
            $data['date_ended'] = fake()->dateTimeBetween($dateHired, 'now');
        }

        return $data;
    }
}