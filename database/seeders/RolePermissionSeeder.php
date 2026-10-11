<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $guardName = 'web';

        // 1. Define all system permissions
        $permissions = [
            // Tasks
            'tasks.view_any',
            'tasks.view_own',
            'tasks.view_assigned',
            'tasks.create',
            'tasks.update_own',
            'tasks.update_assigned',
            'tasks.delete_own',
            'tasks.manage',
            'tasks.assign',

            // Documents
            'documents.view_any',
            'documents.view_own',
            'documents.view_assigned',
            'documents.upload',
            'documents.delete_own',
            'documents.manage',
            'documents.verify',

            // Agents
            'agents.view',
            'agents.update_own',
            'agents.manage',
            'agents.verify',

            // Payments
            'payments.view_any',
            'payments.view_own',
            'payments.view_assigned',
            'payments.create',
            'payments.manage',
            'payments.refund',

            // Reviews
            'reviews.view',
            'reviews.create',
            'reviews.manage',

            // Profiles
            'profiles.view_own',
            'profiles.update_own',
            'profiles.manage',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => $guardName,
            ]);
        }

        // 2. Create roles and assign permissions

        // SUPER_ADMIN - All permissions
        $superAdminRole = Role::firstOrCreate([
            'name' => UserRole::SUPER_ADMIN->value,
            'guard_name' => $guardName,
        ]);
        $superAdminRole->syncPermissions(Permission::where('guard_name', $guardName)->get());

        // ADMIN - Full operational and management access
        $adminRole = Role::firstOrCreate([
            'name' => UserRole::ADMIN->value,
            'guard_name' => $guardName,
        ]);
        $adminRole->syncPermissions([
            'tasks.view_any',
            'tasks.create',
            'tasks.manage',
            'tasks.assign',
            'documents.view_any',
            'documents.upload',
            'documents.manage',
            'documents.verify',
            'agents.view',
            'agents.manage',
            'agents.verify',
            'payments.view_any',
            'payments.create',
            'payments.manage',
            'payments.refund',
            'reviews.view',
            'reviews.manage',
            'profiles.view_own',
            'profiles.update_own',
            'profiles.manage',
        ]);

        // OPERATIONS - Daily operations, assignment, verification, moderation
        $operationsRole = Role::firstOrCreate([
            'name' => UserRole::OPERATIONS->value,
            'guard_name' => $guardName,
        ]);
        $operationsRole->syncPermissions([
            'tasks.view_any',
            'tasks.create',
            'tasks.manage',
            'tasks.assign',
            'documents.view_any',
            'documents.upload',
            'documents.manage',
            'documents.verify',
            'agents.view',
            'agents.manage',
            'agents.verify',
            'payments.view_any',
            'payments.manage',
            'reviews.view',
            'reviews.manage',
            'profiles.view_own',
            'profiles.update_own',
        ]);

        // CUSTOMER - Own tasks, own documents, own payments, reviews, own profile
        $customerRole = Role::firstOrCreate([
            'name' => UserRole::CUSTOMER->value,
            'guard_name' => $guardName,
        ]);
        $customerRole->syncPermissions([
            'tasks.view_own',
            'tasks.create',
            'tasks.update_own',
            'tasks.delete_own',
            'documents.view_own',
            'documents.upload',
            'documents.delete_own',
            'payments.view_own',
            'payments.create',
            'reviews.view',
            'reviews.create',
            'profiles.view_own',
            'profiles.update_own',
            'agents.view',
        ]);

        // AGENT - Assigned tasks, assigned documents, proofs, agent profile, reviews
        $agentRole = Role::firstOrCreate([
            'name' => UserRole::AGENT->value,
            'guard_name' => $guardName,
        ]);
        $agentRole->syncPermissions([
            'tasks.view_assigned',
            'tasks.update_assigned',
            'documents.view_assigned',
            'documents.upload',
            'agents.view',
            'agents.update_own',
            'payments.view_assigned',
            'reviews.view',
            'profiles.view_own',
            'profiles.update_own',
        ]);

        // PROFESSIONAL - Specialized assigned tasks and documents
        $professionalRole = Role::firstOrCreate([
            'name' => UserRole::PROFESSIONAL->value,
            'guard_name' => $guardName,
        ]);
        $professionalRole->syncPermissions([
            'tasks.view_assigned',
            'tasks.update_assigned',
            'documents.view_assigned',
            'documents.upload',
            'agents.view',
            'agents.update_own',
            'payments.view_assigned',
            'reviews.view',
            'profiles.view_own',
            'profiles.update_own',
        ]);
    }
}
