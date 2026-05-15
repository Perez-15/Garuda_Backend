<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Position;
use App\Models\Client;
use App\Models\Branch;

class PositionSeeder extends Seeder
{
    public function run(): void
    {
        // Clients (must match ClientSeeder exactly)
        $mangInasal = Client::where('name', 'Mang Inasal')->first();
        $burlington = Client::where('name', 'Burlington')->first();

        if (!$mangInasal) {
            throw new \Exception("Client 'Mang Inasal' not found. Check ClientSeeder.");
        }

        if (!$burlington) {
            throw new \Exception("Client 'Burlington' not found. Check ClientSeeder.");
        }

        // Branches (must match BranchSeeder exactly)
        $taguigBranch = Branch::where('branch_name', 'Mang Inasal Taguig')->first();
        $velasquezBranch = Branch::where('branch_name', 'Mang Inasal Velasquez')->first();

        if (!$taguigBranch) {
            throw new \Exception("Branch 'Mang Inasal Taguig' not found. Check BranchSeeder.");
        }

        if (!$velasquezBranch) {
            throw new \Exception("Branch 'Mang Inasal Velasquez' not found. Check BranchSeeder.");
        }

        // Positions - Mang Inasal Taguig
        Position::create([
            'client_id' => $mangInasal->id,
            'branch_id' => $taguigBranch->id,
            'title' => 'Cashier',
            'description' => 'Handle customer transactions and payments',
            'slots' => 3,
            'is_active' => true,
        ]);

        Position::create([
            'client_id' => $mangInasal->id,
            'branch_id' => $taguigBranch->id,
            'title' => 'Kitchen Staff',
            'description' => 'Prepare food and maintain kitchen cleanliness',
            'slots' => 5,
            'is_active' => true,
        ]);

        // Positions - Mang Inasal Velasquez
        Position::create([
            'client_id' => $mangInasal->id,
            'branch_id' => $velasquezBranch->id,
            'title' => 'Server',
            'description' => 'Serve customers and take orders',
            'slots' => 4,
            'is_active' => true,
        ]);

        // Positions - Burlington (no branch)
        Position::create([
            'client_id' => $burlington->id,
            'branch_id' => null,
            'title' => 'Sales Associate',
            'description' => 'Assist customers with retail products',
            'slots' => 10,
            'is_active' => true,
        ]);
    }
}