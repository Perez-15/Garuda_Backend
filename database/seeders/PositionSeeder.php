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
        $mangInasal = Client::where('name', 'Mang Inasal')->first();
        $smRetail = Client::where('name', 'SM Retail')->first();

        $taguigBranch = Branch::where('branch_name', 'Mang Inasal Taguig')->first();
        $taytayBranch = Branch::where('branch_name', 'Mang Inasal Taytay')->first();

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

        Position::create([
            'client_id' => $mangInasal->id,
            'branch_id' => $taytayBranch->id,
            'title' => 'Server',
            'description' => 'Serve customers and take orders',
            'slots' => 4,
            'is_active' => true,
        ]);

        Position::create([
            'client_id' => $smRetail->id,
            'branch_id' => null,
            'title' => 'Sales Associate',
            'description' => 'Assist customers with purchases',
            'slots' => 10,
            'is_active' => true,
        ]);
    }
}