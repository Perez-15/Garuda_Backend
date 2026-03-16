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
        DB::table('custom_columns')->truncate();

        $pages = ['hired', 'applicants', 'in_process', 'employed', 'internal_employees'];

        $docOptions     = json_encode(['submitted', 'pending', 'not_required']);
        $statusOptions  = json_encode(['complete', 'incomplete', 'pending']);

        $fields = [
            // ── Personal Information ──────────────────────────────────────────
            ['section'=>'Personal Information','field_key'=>'full_name','label'=>'Employee name','type'=>'text','options'=>null,'scope'=>'both','required'=>1,'is_fixed'=>1,'order'=>0],
            ['section'=>'Personal Information','field_key'=>'date_of_birth','label'=>'Birthday','type'=>'date','options'=>null,'scope'=>'both','required'=>0,'is_fixed'=>1,'order'=>1],
            ['section'=>'Personal Information','field_key'=>'age','label'=>'Age','type'=>'number','options'=>null,'scope'=>'both','required'=>0,'is_fixed'=>1,'order'=>2],
            ['section'=>'Personal Information','field_key'=>'gender','label'=>'Gender','type'=>'select','options'=>json_encode(['Male','Female']),'scope'=>'both','required'=>0,'is_fixed'=>1,'order'=>3],
            ['section'=>'Personal Information','field_key'=>'civil_status','label'=>'Civil status','type'=>'select','options'=>json_encode(['Single','Married','Widowed','Separated']),'scope'=>'both','required'=>0,'is_fixed'=>1,'order'=>4],
            ['section'=>'Personal Information','field_key'=>'contact_number','label'=>'Contact number','type'=>'phone','options'=>null,'scope'=>'both','required'=>1,'is_fixed'=>1,'order'=>5],
            ['section'=>'Personal Information','field_key'=>'email','label'=>'Email','type'=>'email','options'=>null,'scope'=>'both','required'=>0,'is_fixed'=>1,'order'=>6],
            ['section'=>'Personal Information','field_key'=>'address','label'=>'Address','type'=>'text','options'=>null,'scope'=>'both','required'=>0,'is_fixed'=>1,'order'=>7],
            ['section'=>'Personal Information','field_key'=>'emergency_contact_name','label'=>'Emergency contact name','type'=>'text','options'=>null,'scope'=>'both','required'=>0,'is_fixed'=>1,'order'=>8],
            ['section'=>'Personal Information','field_key'=>'emergency_contact_number','label'=>'Emergency contact number','type'=>'phone','options'=>null,'scope'=>'both','required'=>0,'is_fixed'=>1,'order'=>9],

            // ── Employment Details ────────────────────────────────────────────
            ['section'=>'Employment Details','field_key'=>'date_hired','label'=>'Date hired','type'=>'date','options'=>null,'scope'=>'both','required'=>1,'is_fixed'=>1,'order'=>0],
            ['section'=>'Employment Details','field_key'=>'date_ended','label'=>'Date end','type'=>'date','options'=>null,'scope'=>'ext','required'=>0,'is_fixed'=>1,'order'=>1],
            ['section'=>'Employment Details','field_key'=>'date_resigned','label'=>'Date resigned','type'=>'date','options'=>null,'scope'=>'ext','required'=>0,'is_fixed'=>1,'order'=>2],
            ['section'=>'Employment Details','field_key'=>'daily_rate','label'=>'Daily rate','type'=>'number','options'=>null,'scope'=>'both','required'=>0,'is_fixed'=>1,'order'=>3],
            ['section'=>'Employment Details','field_key'=>'employment_status','label'=>'Employment status','type'=>'select','options'=>json_encode(['resigned','terminated','endo','awol']),'scope'=>'ext','required'=>0,'is_fixed'=>1,'order'=>4],
            ['section'=>'Employment Details','field_key'=>'source','label'=>'Source','type'=>'text','options'=>null,'scope'=>'both','required'=>0,'is_fixed'=>1,'order'=>5],
            ['section'=>'Employment Details','field_key'=>'remarks','label'=>'Remarks','type'=>'text','options'=>null,'scope'=>'both','required'=>0,'is_fixed'=>1,'order'=>6],

            // ── Government IDs ────────────────────────────────────────────────
            ['section'=>'Government IDs','field_key'=>'sss','label'=>'SSS','type'=>'text','options'=>null,'scope'=>'both','required'=>0,'is_fixed'=>1,'order'=>0],
            ['section'=>'Government IDs','field_key'=>'pagibig','label'=>'Pag-ibig','type'=>'text','options'=>null,'scope'=>'both','required'=>0,'is_fixed'=>1,'order'=>1],
            ['section'=>'Government IDs','field_key'=>'philhealth','label'=>'PhilHealth','type'=>'text','options'=>null,'scope'=>'both','required'=>0,'is_fixed'=>1,'order'=>2],
            ['section'=>'Government IDs','field_key'=>'tin','label'=>'TIN','type'=>'text','options'=>null,'scope'=>'both','required'=>0,'is_fixed'=>1,'order'=>3],

            // ── Documents & Requirements ──────────────────────────────────────
            ['section'=>'Documents & Requirements','field_key'=>'psa_birth_cert','label'=>'PSA / Birth Certificate','type'=>'select','options'=>$docOptions,'scope'=>'both','required'=>0,'is_fixed'=>0,'order'=>0],
            ['section'=>'Documents & Requirements','field_key'=>'sss_doc','label'=>'SSS Document','type'=>'select','options'=>$docOptions,'scope'=>'both','required'=>0,'is_fixed'=>0,'order'=>1],
            ['section'=>'Documents & Requirements','field_key'=>'philhealth_doc','label'=>'PhilHealth Document','type'=>'select','options'=>$docOptions,'scope'=>'both','required'=>0,'is_fixed'=>0,'order'=>2],
            ['section'=>'Documents & Requirements','field_key'=>'pagibig_doc','label'=>'Pag-ibig Document','type'=>'select','options'=>$docOptions,'scope'=>'both','required'=>0,'is_fixed'=>0,'order'=>3],
            ['section'=>'Documents & Requirements','field_key'=>'tin_doc','label'=>'TIN Document','type'=>'select','options'=>$docOptions,'scope'=>'both','required'=>0,'is_fixed'=>0,'order'=>4],
            ['section'=>'Documents & Requirements','field_key'=>'nbi_status','label'=>'NBI / Police Clearance','type'=>'select','options'=>$docOptions,'scope'=>'both','required'=>0,'is_fixed'=>1,'order'=>5],
            ['section'=>'Documents & Requirements','field_key'=>'medcert_status','label'=>'Medical Basic 5 / Drug Test','type'=>'select','options'=>$docOptions,'scope'=>'both','required'=>0,'is_fixed'=>1,'order'=>6],
            ['section'=>'Documents & Requirements','field_key'=>'coe','label'=>'Certificate of Employment (COE)','type'=>'select','options'=>$docOptions,'scope'=>'both','required'=>0,'is_fixed'=>0,'order'=>7],
            ['section'=>'Documents & Requirements','field_key'=>'tor_diploma','label'=>'TOR / Diploma','type'=>'select','options'=>$docOptions,'scope'=>'both','required'=>0,'is_fixed'=>0,'order'=>8],
            ['section'=>'Documents & Requirements','field_key'=>'valid_id_photocopy','label'=>'Photocopy of Valid ID','type'=>'select','options'=>$docOptions,'scope'=>'both','required'=>0,'is_fixed'=>0,'order'=>9],
            ['section'=>'Documents & Requirements','field_key'=>'picture_1x1','label'=>'2pcs 1x1 Picture','type'=>'select','options'=>$docOptions,'scope'=>'both','required'=>0,'is_fixed'=>0,'order'=>10],
            ['section'=>'Documents & Requirements','field_key'=>'requirements_status','label'=>'Overall requirements status','type'=>'select','options'=>$statusOptions,'scope'=>'both','required'=>0,'is_fixed'=>1,'order'=>11],
        ];

        foreach ($pages as $page) {
            foreach ($fields as $field) {
                DB::table('custom_columns')->insert(array_merge($field, [
                    'page'       => $page,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }
        }

        $total = DB::table('custom_columns')->count();
        $this->command->info("✅ {$total} columns seeded across " . count($pages) . " pages.");
    }
}