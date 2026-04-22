<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CustomColumn;

class ClientProspectColumnsSeeder extends Seeder
{
    public function run(): void
    {
        $cols = [
            ['label' => 'Company Name',     'field_key' => 'company_name',     'type' => 'text',     'section' => 'Company Info', 'order' => 1, 'required' => true],
            ['label' => 'Phone Number',     'field_key' => 'phone_number',     'type' => 'text',     'section' => 'Company Info', 'order' => 2, 'required' => false],
            ['label' => 'Telephone Number', 'field_key' => 'telephone_number', 'type' => 'text',     'section' => 'Company Info', 'order' => 3, 'required' => false],
            ['label' => 'Contact Person',   'field_key' => 'contact_person',   'type' => 'text',     'section' => 'Contact',      'order' => 4, 'required' => false],
            ['label' => 'Email Address',    'field_key' => 'email_address',    'type' => 'email',    'section' => 'Contact',      'order' => 5, 'required' => false],
            ['label' => 'Location',         'field_key' => 'location',         'type' => 'textarea', 'section' => 'Contact',      'order' => 6, 'required' => false],
            ['label' => 'Status',           'field_key' => 'status',           'type' => 'select',   'section' => 'Status',       'order' => 7, 'required' => false],
            ['label' => 'Remarks',          'field_key' => 'remarks',          'type' => 'textarea', 'section' => 'Status',       'order' => 8, 'required' => false],
        ];

      
    }
}