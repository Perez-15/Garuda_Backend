<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Branch;
use App\Models\Client;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        Client::firstOrCreate(['name' => 'Mang Inasal'], [
            'type' => 'Food & Beverage', 'is_active' => true,
        ]);

    Client::firstOrCreate(['name' => 'Burlington'], [
        'type' => 'Retail', 'is_active' => true,
    ]);

    Client::firstOrCreate(['name' => 'Jaicom BPO Inc.'], [
        'type' => 'BPO', 'is_active' => true,
    ]);
}
}