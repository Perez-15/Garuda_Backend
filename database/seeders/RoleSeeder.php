<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            'view_applicants',
            'create_applicants',
            'edit_applicants',
            'delete_applicants',
            'move_applicant_step',
            
            'view_clients',
            'create_clients',
            'edit_clients',
            'delete_clients',
            
            'view_branches',
            'create_branches',
            'edit_branches',
            'delete_branches',
            
            'view_workflows',
            'create_workflows',
            'edit_workflows',
            'delete_workflows',
            
            'view_positions',
            'create_positions',
            'edit_positions',
            'delete_positions',
            
            'view_reports',
            'export_reports',
            
            'manage_users', // Add/edit/delete users
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Super Admin - ALL permissions
        $superAdmin = Role::create(['name' => 'super_admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // HR Admin - Can manage everything except super admin users
        $hrAdmin = Role::create(['name' => 'hr_admin']);
        $hrAdmin->givePermissionTo([
            'view_applicants',
            'create_applicants',
            'edit_applicants',
            'delete_applicants',
            'move_applicant_step',
            
            'view_clients',
            'create_clients',
            'edit_clients',
            'delete_clients',
            
            'view_branches',
            'create_branches',
            'edit_branches',
            'delete_branches',
            
            'view_workflows',
            'create_workflows',
            'edit_workflows',
            'delete_workflows',
            
            'view_positions',
            'create_positions',
            'edit_positions',
            'delete_positions',
            
            'view_reports',
            'export_reports',
            
            'manage_users', // Can add/edit TA users only
        ]);

        // Talent Acquisition - Recruiting focused
        $ta = Role::create(['name' => 'talent_acquisition']);
        $ta->givePermissionTo([
            'view_applicants',
            'create_applicants',
            'edit_applicants',
            'move_applicant_step',
            'view_positions',
        ]);
    }
}