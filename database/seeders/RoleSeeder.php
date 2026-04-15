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

        // ── Permissions ────────────────────────────────────────────────────────

        $permissions = [
            // Applicants
            'view_applicants',
            'create_applicants',
            'edit_applicants',
            'delete_applicants',
            'move_applicant_step',

            // Clients
            'view_clients',
            'create_clients',
            'edit_clients',
            'delete_clients',

            // Branches
            'view_branches',
            'create_branches',
            'edit_branches',
            'delete_branches',

            // Workflows
            'view_workflows',
            'create_workflows',
            'edit_workflows',
            'delete_workflows',

            // Positions
            'view_positions',
            'create_positions',
            'edit_positions',
            'delete_positions',

            // Reports
            'view_reports',
            'export_reports',

            // Users
            'manage_users',       // Add / edit / delete users
            'assign_branches',    // Assign branches to TA users ← NEW
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // ── Super Admin — everything ───────────────────────────────────────────

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // ── HR Admin ───────────────────────────────────────────────────────────

        $hrAdmin = Role::firstOrCreate(['name' => 'hr_admin']);
        $hrAdmin->syncPermissions([
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

            'manage_users',
            'assign_branches',   // HR Admin can manage TA branch access ← NEW
        ]);

        // ── Talent Acquisition ─────────────────────────────────────────────────
        // Scoped to assigned branches via ApplicantController logic,
        // NOT via permissions — the controller handles the restriction.

        $ta = Role::firstOrCreate(['name' => 'talent_acquisition']);
        $ta->syncPermissions([
            'view_applicants',
            'create_applicants',
            'edit_applicants',
            'move_applicant_step',
            'view_positions',
        ]);
    }
}