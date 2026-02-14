<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Client;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        Client::create([
            'name' => 'Mang Inasal',
            'type' => 'Food & Beverage',
            'is_active' => true,
        ]);

        Client::create([
            'name' => 'SM Retail',
            'type' => 'Retail',
            'is_active' => true,
        ]);

        Client::create([
            'name' => 'Jollibee Foods Corporation',
            'type' => 'Food & Beverage',
            'is_active' => true,
        ]);
    }
}