<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Applicant permissions
            'view_applicants',
            'create_applicants',
            'edit_applicants',
            'delete_applicants',
            'move_applicant_step',
            
            // Client permissions
            'view_clients',
            'create_clients',
            'edit_clients',
            'delete_clients',
            
            // Branch permissions
            'view_branches',
            'create_branches',
            'edit_branches',
            'delete_branches',
            
            // Workflow permissions
            'view_workflows',
            'create_workflows',
            'edit_workflows',
            'delete_workflows',
            
            // Report permissions
            'view_reports',
            'export_reports',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        
        // Super Admin - all permissions
        $superAdmin = Role::create(['name' => 'super_admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // Admin - manage clients, branches, workflows
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo([
            'view_applicants',
            'create_applicants',
            'edit_applicants',
            'move_applicant_step',
            'view_clients',
            'create_clients',
            'edit_clients',
            'view_branches',
            'create_branches',
            'edit_branches',
            'view_workflows',
            'create_workflows',
            'edit_workflows',
            'view_reports',
            'export_reports',
        ]);

        // HR Manager
        $hrManager = Role::create(['name' => 'hr_manager']);
        $hrManager->givePermissionTo([
            'view_applicants',
            'create_applicants',
            'edit_applicants',
            'move_applicant_step',
            'view_branches',
            'view_workflows',
            'view_reports',
            'export_reports',
        ]);

        // HR Staff
        $hrStaff = Role::create(['name' => 'hr_staff']);
        $hrStaff->givePermissionTo([
            'view_applicants',
            'create_applicants',
            'edit_applicants',
            'move_applicant_step',
        ]);

        // Talent Acquisition
        $ta = Role::create(['name' => 'talent_acquisition']);
        $ta->givePermissionTo([
            'view_applicants',
            'create_applicants',
            'edit_applicants',
        ]);
    }
}