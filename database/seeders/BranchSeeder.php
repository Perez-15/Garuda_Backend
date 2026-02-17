<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Branch;
use App\Models\Client;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $mangInasal = Client::where('name', 'Mang Inasal')->first();
        $Burlington = Client::where('name', 'Burlington')->first();
        $Jaicom = Client::where('name', 'Jaicom BPO Inc.')->first();

        // Mang Inasal Branches
        Branch::create([
            'client_id' => $mangInasal->id,
            'branch_name' => 'Mang Inasal Taguig',
            'location' => 'Taguig City',
            'is_active' => true,
        ]);

        Branch::create([
            'client_id' => $mangInasal->id,
            'branch_name' => 'Mang Inasal Velasquez',
            'location' => 'Taytay, Rizal',
            'is_active' => true,
        ]);

        Branch::create([
            'client_id' => $mangInasal->id,
            'branch_name' => 'Mang Inasal Ejercito',
            'location' => 'C6, Taytay, Rizal',
            'is_active' => true,
        ]);

        // SM Retail Branches
        Branch::create([
            'client_id' => $mangInasal->id,
            'branch_name' => 'Mang Inasal Marikina Bayan',
            'location' => 'Marikina City',
            'is_active' => true,
        ]);

        Branch::create([
            'client_id' => $Burlington->id,
            'branch_name' => 'Burlington SM North',
            'location' => 'Quezon City City',
            'is_active' => true,
        ]);

        Branch::create([
            'client_id' => $Jaicom->id,
            'branch_name' => 'Jaicom Ortigas Center',
            'location' => '10th Floor, Strata 2000 Building. Ortigas, Pasig City',
            'is_active' => true,
        ]);
    }
}