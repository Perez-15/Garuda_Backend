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
        $smRetail = Client::where('name', 'SM Retail')->first();

        // Mang Inasal Branches
        Branch::create([
            'client_id' => $mangInasal->id,
            'branch_name' => 'Mang Inasal Taguig',
            'location' => 'Taguig City',
            'is_active' => true,
        ]);

        Branch::create([
            'client_id' => $mangInasal->id,
            'branch_name' => 'Mang Inasal Taytay',
            'location' => 'Taytay, Rizal',
            'is_active' => true,
        ]);

        Branch::create([
            'client_id' => $mangInasal->id,
            'branch_name' => 'Mang Inasal Makati',
            'location' => 'Makati City',
            'is_active' => true,
        ]);

        // SM Retail Branches
        Branch::create([
            'client_id' => $smRetail->id,
            'branch_name' => 'SM North EDSA',
            'location' => 'Quezon City',
            'is_active' => true,
        ]);

        Branch::create([
            'client_id' => $smRetail->id,
            'branch_name' => 'SM Megamall',
            'location' => 'Mandaluyong City',
            'is_active' => true,
        ]);
    }
}