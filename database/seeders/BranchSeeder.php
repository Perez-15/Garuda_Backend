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
    $burlington = Client::where('name', 'Burlington')->first();
    $jaicom = Client::where('name', 'Jaicom BPO Inc.')->first();

    Branch::firstOrCreate(['branch_name' => 'Mang Inasal Taguig'], [
        'client_id' => $mangInasal->id, 'location' => 'Taguig City', 'is_active' => true,
    ]);

    Branch::firstOrCreate(['branch_name' => 'Mang Inasal Velasquez'], [
        'client_id' => $mangInasal->id, 'location' => 'Taytay, Rizal', 'is_active' => true,
    ]);

    Branch::firstOrCreate(['branch_name' => 'Mang Inasal Ejercito'], [
        'client_id' => $mangInasal->id, 'location' => 'C6, Taytay, Rizal', 'is_active' => true,
    ]);

    Branch::firstOrCreate(['branch_name' => 'Mang Inasal Marikina Bayan'], [
        'client_id' => $mangInasal->id, 'location' => 'Marikina City', 'is_active' => true,
    ]);

    Branch::firstOrCreate(['branch_name' => 'Jaicom Ortigas Center'], [
        'client_id' => $jaicom->id, 'location' => 'Ortigas Center', 'is_active' => true,
    ]);

    Branch::firstOrCreate(['branch_name' => 'Burlington SM North'], [
        'client_id' => $burlington->id, 'location' => 'Quezon City', 'is_active' => true,
    ]);
}
}