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
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@garuda.com',
            'password' => Hash::make('password'),
        ]);
        $superAdmin->assignRole('super_admin');

        // HR Admin
        $hrAdmin = User::create([
            'name' => 'HR Admin',
            'email' => 'hradmin@garuda.com',
            'password' => Hash::make('password'),
        ]);
        $hrAdmin->assignRole('hr_admin');

        // Talent Acquisition
        $ta = User::create([
            'name' => 'John Recruiter',
            'email' => 'ta@garuda.com',
            'password' => Hash::make('password'),
        ]);
        $ta->assignRole('talent_acquisition');
    }
}