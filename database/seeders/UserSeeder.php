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

        // Admin
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@garuda.com',
            'password' => Hash::make('password'),
        ]);
        $admin->assignRole('admin');

        // HR Manager
        $hrManager = User::create([
            'name' => 'HR Manager',
            'email' => 'hrmanager@garuda.com',
            'password' => Hash::make('password'),
        ]);
        $hrManager->assignRole('hr_manager');

        // HR Staff
        $hrStaff = User::create([
            'name' => 'HR Staff',
            'email' => 'hrstaff@garuda.com',
            'password' => Hash::make('password'),
        ]);
        $hrStaff->assignRole('hr_staff');

        // Talent Acquisition
        $ta = User::create([
            'name' => 'Talent Acquisition',
            'email' => 'ta@garuda.com',
            'password' => Hash::make('password'),
        ]);
        $ta->assignRole('talent_acquisition');
    }
}