<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CustomColumnSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // ── Only clear pages we are about to re-seed ──────────────────────
        DB::table('custom_columns')
            ->whereIn('page', ['hired', 'in_process', 'applicants'])
            ->delete();
        // internal_employees is kept — seeded separately by InternalEmployeeSchemaSeeder

        $docOptions    = json_encode(['submitted', 'pending', 'not_required']);
        $statusOptions = json_encode(['complete', 'incomplete', 'pending']);

        // ══════════════════════════════════════════════════════════════════
        // HIRED PAGE
        // ══════════════════════════════════════════════════════════════════
        $hiredFields = [

            // ── Personal Information ──────────────────────────────────────
            ['section' => 'Personal Information', 'field_key' => 'full_name',                 'label' => 'Employee Name',             'type' => 'text',   'options' => null,          'scope' => 'ext', 'required' => 1, 'is_fixed' => 1, 'order' => 0],
            ['section' => 'Personal Information', 'field_key' => 'date_of_birth',             'label' => 'Date of Birth',             'type' => 'date',   'options' => null,          'scope' => 'ext', 'required' => 0, 'is_fixed' => 1, 'order' => 1],
            ['section' => 'Personal Information', 'field_key' => 'age',                       'label' => 'Age',                       'type' => 'number', 'options' => null,          'scope' => 'ext', 'required' => 0, 'is_fixed' => 1, 'order' => 2],
            ['section' => 'Personal Information', 'field_key' => 'gender',                    'label' => 'Gender',                    'type' => 'select', 'options' => json_encode(['Male', 'Female']), 'scope' => 'ext', 'required' => 0, 'is_fixed' => 1, 'order' => 3],
            ['section' => 'Personal Information', 'field_key' => 'civil_status',              'label' => 'Civil Status',              'type' => 'select', 'options' => json_encode(['Single', 'Married', 'Widowed', 'Separated']), 'scope' => 'ext', 'required' => 0, 'is_fixed' => 1, 'order' => 4],
            ['section' => 'Personal Information', 'field_key' => 'contact_number',            'label' => 'Contact Number',            'type' => 'phone',  'options' => null,          'scope' => 'ext', 'required' => 1, 'is_fixed' => 1, 'order' => 5],
            ['section' => 'Personal Information', 'field_key' => 'email',                     'label' => 'Email',                     'type' => 'email',  'options' => null,          'scope' => 'ext', 'required' => 0, 'is_fixed' => 1, 'order' => 6],
            ['section' => 'Personal Information', 'field_key' => 'address',                   'label' => 'Address',                   'type' => 'text',   'options' => null,          'scope' => 'ext', 'required' => 0, 'is_fixed' => 1, 'order' => 7],
            ['section' => 'Personal Information', 'field_key' => 'emergency_contact_name',    'label' => 'Emergency Contact Name',    'type' => 'text',   'options' => null,          'scope' => 'ext', 'required' => 0, 'is_fixed' => 1, 'order' => 8],
            ['section' => 'Personal Information', 'field_key' => 'emergency_contact_number',  'label' => 'Emergency Contact Number',  'type' => 'phone',  'options' => null,          'scope' => 'ext', 'required' => 0, 'is_fixed' => 1, 'order' => 9],

            // ── Employment Details ────────────────────────────────────────
            ['section' => 'Employment Details', 'field_key' => 'branch',            'label' => 'Branch / Location',  'type' => 'text',   'options' => null, 'scope' => 'ext', 'required' => 1, 'is_fixed' => 1, 'order' => 0],
            ['section' => 'Employment Details', 'field_key' => 'position',          'label' => 'Position',           'type' => 'text',   'options' => null, 'scope' => 'ext', 'required' => 0, 'is_fixed' => 1, 'order' => 1],
            ['section' => 'Employment Details', 'field_key' => 'date_hired',        'label' => 'Date Hired',         'type' => 'date',   'options' => null, 'scope' => 'ext', 'required' => 1, 'is_fixed' => 1, 'order' => 2],
            ['section' => 'Employment Details', 'field_key' => 'date_resigned',     'label' => 'Date Resigned',      'type' => 'date',   'options' => null, 'scope' => 'ext', 'required' => 0, 'is_fixed' => 1, 'order' => 3],
            ['section' => 'Employment Details', 'field_key' => 'date_ended',        'label' => 'Date Ended',         'type' => 'date',   'options' => null, 'scope' => 'ext', 'required' => 0, 'is_fixed' => 1, 'order' => 4],
            ['section' => 'Employment Details', 'field_key' => 'daily_rate',        'label' => 'Daily Rate',         'type' => 'number', 'options' => null, 'scope' => 'ext', 'required' => 0, 'is_fixed' => 1, 'order' => 5],
            ['section' => 'Employment Details', 'field_key' => 'employment_status', 'label' => 'Employment Status',  'type' => 'select', 'options' => json_encode(['hired', 'resigned', 'terminated', 'endo', 'awol']), 'scope' => 'ext', 'required' => 0, 'is_fixed' => 1, 'order' => 6],
            ['section' => 'Employment Details', 'field_key' => 'source',            'label' => 'Source',             'type' => 'text',   'options' => null, 'scope' => 'ext', 'required' => 0, 'is_fixed' => 1, 'order' => 7],
            ['section' => 'Employment Details', 'field_key' => 'remarks',           'label' => 'Remarks',            'type' => 'text',   'options' => null, 'scope' => 'ext', 'required' => 0, 'is_fixed' => 1, 'order' => 8],

            // ── Government IDs ────────────────────────────────────────────
            ['section' => 'Government IDs', 'field_key' => 'sss',        'label' => 'SSS',        'type' => 'text', 'options' => null, 'scope' => 'ext', 'required' => 0, 'is_fixed' => 1, 'order' => 0],
            ['section' => 'Government IDs', 'field_key' => 'pagibig',    'label' => 'Pag-IBIG',   'type' => 'text', 'options' => null, 'scope' => 'ext', 'required' => 0, 'is_fixed' => 1, 'order' => 1],
            ['section' => 'Government IDs', 'field_key' => 'philhealth', 'label' => 'PhilHealth', 'type' => 'text', 'options' => null, 'scope' => 'ext', 'required' => 0, 'is_fixed' => 1, 'order' => 2],
            ['section' => 'Government IDs', 'field_key' => 'tin',        'label' => 'TIN',        'type' => 'text', 'options' => null, 'scope' => 'ext', 'required' => 0, 'is_fixed' => 1, 'order' => 3],

            // ── Documents & Requirements ──────────────────────────────────
            ['section' => 'Documents & Requirements', 'field_key' => 'psa_birth_cert',           'label' => 'PSA / Birth Certificate',         'type' => 'select', 'options' => $docOptions,    'scope' => 'ext', 'required' => 0, 'is_fixed' => 1, 'order' => 0],
            ['section' => 'Documents & Requirements', 'field_key' => 'sss_doc',                  'label' => 'SSS Document',                    'type' => 'select', 'options' => $docOptions,    'scope' => 'ext', 'required' => 0, 'is_fixed' => 1, 'order' => 1],
            ['section' => 'Documents & Requirements', 'field_key' => 'philhealth_doc',           'label' => 'PhilHealth Document',             'type' => 'select', 'options' => $docOptions,    'scope' => 'ext', 'required' => 0, 'is_fixed' => 1, 'order' => 2],
            ['section' => 'Documents & Requirements', 'field_key' => 'pagibig_doc',              'label' => 'Pag-IBIG Document',               'type' => 'select', 'options' => $docOptions,    'scope' => 'ext', 'required' => 0, 'is_fixed' => 1, 'order' => 3],
            ['section' => 'Documents & Requirements', 'field_key' => 'tin_doc',                  'label' => 'TIN Document',                    'type' => 'select', 'options' => $docOptions,    'scope' => 'ext', 'required' => 0, 'is_fixed' => 1, 'order' => 4],
            ['section' => 'Documents & Requirements', 'field_key' => 'nbi_status',               'label' => 'NBI / Police Clearance',          'type' => 'select', 'options' => $docOptions,    'scope' => 'ext', 'required' => 0, 'is_fixed' => 1, 'order' => 5],
            ['section' => 'Documents & Requirements', 'field_key' => 'medcert_status',           'label' => 'Medical Basic 5 / Drug Test',     'type' => 'select', 'options' => $docOptions,    'scope' => 'ext', 'required' => 0, 'is_fixed' => 1, 'order' => 6],
            ['section' => 'Documents & Requirements', 'field_key' => 'coe',                      'label' => 'Certificate of Employment (COE)', 'type' => 'select', 'options' => $docOptions,    'scope' => 'ext', 'required' => 0, 'is_fixed' => 1, 'order' => 7],
            ['section' => 'Documents & Requirements', 'field_key' => 'tor_diploma',              'label' => 'TOR / Diploma',                   'type' => 'select', 'options' => $docOptions,    'scope' => 'ext', 'required' => 0, 'is_fixed' => 1, 'order' => 8],
            ['section' => 'Documents & Requirements', 'field_key' => 'valid_id_photocopy',       'label' => 'Photocopy of Valid ID',           'type' => 'select', 'options' => $docOptions,    'scope' => 'ext', 'required' => 0, 'is_fixed' => 1, 'order' => 9],
            ['section' => 'Documents & Requirements', 'field_key' => 'picture_1x1',              'label' => '2pcs 1x1 Picture',               'type' => 'select', 'options' => $docOptions,    'scope' => 'ext', 'required' => 0, 'is_fixed' => 1, 'order' => 10],
            ['section' => 'Documents & Requirements', 'field_key' => 'requirements_status',      'label' => 'Overall Requirements Status',     'type' => 'select', 'options' => $statusOptions, 'scope' => 'ext', 'required' => 0, 'is_fixed' => 1, 'order' => 11],

            // ── HR Actions (special tab entries) ──────────────────────────
            ['section' => 'HR Actions', 'field_key' => '__tab_hr_actions', 'label' => 'HR Actions',        'type' => 'tab',  'options' => null, 'scope' => 'ext', 'required' => 0, 'is_fixed' => 1, 'order' => 0],
            ['section' => 'HR Actions', 'field_key' => '__action_memo',    'label' => 'Memo',              'type' => 'tab',  'options' => null, 'scope' => 'ext', 'required' => 0, 'is_fixed' => 1, 'order' => 1],
            ['section' => 'HR Actions', 'field_key' => '__action_ir',      'label' => 'Incident Report',   'type' => 'tab',  'options' => null, 'scope' => 'ext', 'required' => 0, 'is_fixed' => 1, 'order' => 2],
            ['section' => 'HR Actions', 'field_key' => '__action_loa',     'label' => 'LOA',               'type' => 'tab',  'options' => null, 'scope' => 'ext', 'required' => 0, 'is_fixed' => 1, 'order' => 3],
        ];

        foreach ($hiredFields as $field) {
            DB::table('custom_columns')->insert(array_merge($field, [
                'page'       => 'hired',
                'created_by' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        // ══════════════════════════════════════════════════════════════════
        // IN-PROCESS PAGE
        // ══════════════════════════════════════════════════════════════════
        $inProcessFields = [
            ['section' => 'General', 'field_key' => 'full_name',    'label' => 'Applicant',      'type' => 'text', 'options' => null, 'scope' => 'ext', 'required' => 1, 'is_fixed' => 1, 'order' => 0],
            ['section' => 'General', 'field_key' => 'branch',       'label' => 'Branch',         'type' => 'text', 'options' => null, 'scope' => 'ext', 'required' => 0, 'is_fixed' => 1, 'order' => 1],
            ['section' => 'General', 'field_key' => 'current_step', 'label' => 'Current Step',   'type' => 'text', 'options' => null, 'scope' => 'ext', 'required' => 0, 'is_fixed' => 1, 'order' => 2],
            ['section' => 'General', 'field_key' => 'source',       'label' => 'Source',         'type' => 'text', 'options' => null, 'scope' => 'ext', 'required' => 0, 'is_fixed' => 1, 'order' => 3],
            ['section' => 'General', 'field_key' => 'applied_at',   'label' => 'Applied',        'type' => 'date', 'options' => null, 'scope' => 'ext', 'required' => 0, 'is_fixed' => 1, 'order' => 4],
            ['section' => 'General', 'field_key' => 'handled_by',   'label' => 'Recruiter (TA)', 'type' => 'text', 'options' => null, 'scope' => 'ext', 'required' => 0, 'is_fixed' => 1, 'order' => 5],
            ['section' => 'General', 'field_key' => 'added_by',     'label' => 'Added By',       'type' => 'text', 'options' => null, 'scope' => 'ext', 'required' => 0, 'is_fixed' => 1, 'order' => 6],
        ];

        foreach ($inProcessFields as $field) {
            DB::table('custom_columns')->insert(array_merge($field, [
                'page'       => 'in_process',
                'created_by' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        // ══════════════════════════════════════════════════════════════════
        // APPLICANTS PAGE
        // ══════════════════════════════════════════════════════════════════
        $applicantsFields = [
            ['section' => 'General', 'field_key' => 'full_name',  'label' => 'Applicant',      'type' => 'text', 'options' => null, 'scope' => 'ext', 'required' => 1, 'is_fixed' => 1, 'order' => 0],
            ['section' => 'General', 'field_key' => 'branch',     'label' => 'Branch',         'type' => 'text', 'options' => null, 'scope' => 'ext', 'required' => 0, 'is_fixed' => 1, 'order' => 1],
            ['section' => 'General', 'field_key' => 'status',     'label' => 'Status',         'type' => 'text', 'options' => null, 'scope' => 'ext', 'required' => 0, 'is_fixed' => 1, 'order' => 2],
            ['section' => 'General', 'field_key' => 'source',     'label' => 'Source',         'type' => 'text', 'options' => null, 'scope' => 'ext', 'required' => 0, 'is_fixed' => 1, 'order' => 3],
            ['section' => 'General', 'field_key' => 'applied_at', 'label' => 'Applied',        'type' => 'date', 'options' => null, 'scope' => 'ext', 'required' => 0, 'is_fixed' => 1, 'order' => 4],
            ['section' => 'General', 'field_key' => 'handled_by', 'label' => 'Recruiter (TA)', 'type' => 'text', 'options' => null, 'scope' => 'ext', 'required' => 0, 'is_fixed' => 1, 'order' => 5],
            ['section' => 'General', 'field_key' => 'added_by',   'label' => 'Added By',       'type' => 'text', 'options' => null, 'scope' => 'ext', 'required' => 0, 'is_fixed' => 1, 'order' => 6],
        ];

        foreach ($applicantsFields as $field) {
            DB::table('custom_columns')->insert(array_merge($field, [
                'page'       => 'applicants',
                'created_by' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        $total = DB::table('custom_columns')->count();
        $this->command->info("✅ {$total} total columns in custom_columns.");
        $this->command->info("   hired:               " . DB::table('custom_columns')->where('page', 'hired')->count());
        $this->command->info("   in_process:          " . DB::table('custom_columns')->where('page', 'in_process')->count());
        $this->command->info("   applicants:          " . DB::table('custom_columns')->where('page', 'applicants')->count());
        $this->command->info("   internal_employees:  " . DB::table('custom_columns')->where('page', 'internal_employees')->count());
    }
}