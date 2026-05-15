<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Super Admin
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@garuda.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
            ]
        );
        $superAdmin->assignRole('super_admin');

        // HR Admin
        $hrAdmin = User::firstOrCreate(
            ['email' => 'hradmin@garuda.com'],
            [
                'name' => 'HR Admin',
                'password' => Hash::make('password'),
            ]
        );
        $hrAdmin->assignRole('hr_admin');

        // Talent Acquisition
        $ta = User::firstOrCreate(
            ['email' => 'ta.rodolfo@garuda.com'],
            [
                'name' => 'Rodolfo',
                'password' => Hash::make('password'),
            ]
        );
        $ta->assignRole('talent_acquisition');

        // Marketing
        $marketing = User::firstOrCreate(
            ['email' => 'mt.nakar@garuda.com'],
            [
                'name' => 'Nakar',
                'password' => Hash::make('password'),
            ]
        );
        $marketing->assignRole('marketing');
    }
}