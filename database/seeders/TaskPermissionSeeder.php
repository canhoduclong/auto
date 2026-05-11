<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\TaskPermission;
use Illuminate\Database\Seeder;

class TaskPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Define which permissions each role should have
        $rolePermissions = [
            // Admin: all permissions
            'admin' => [
                TaskPermission::ASSIGN_TASK,
                TaskPermission::COMPLETE_TASK,
                TaskPermission::VERIFY_COMPLETION,
                TaskPermission::VIEW_TASK_HISTORY,
                TaskPermission::TRACK_PROGRESS,
            ],
            
            // CEO: all permissions
            'CEO' => [
                TaskPermission::ASSIGN_TASK,
                TaskPermission::COMPLETE_TASK,
                TaskPermission::VERIFY_COMPLETION,
                TaskPermission::VIEW_TASK_HISTORY,
                TaskPermission::TRACK_PROGRESS,
            ],
            
            // Manager: can assign, verify, track
            'manager' => [
                TaskPermission::ASSIGN_TASK,
                TaskPermission::VERIFY_COMPLETION,
                TaskPermission::VIEW_TASK_HISTORY,
                TaskPermission::TRACK_PROGRESS,
            ],
            
            'manager_sale' => [
                TaskPermission::ASSIGN_TASK,
                TaskPermission::VERIFY_COMPLETION,
                TaskPermission::TRACK_PROGRESS,
            ],
            
            // Leader: can assign and track
            'leader' => [
                TaskPermission::ASSIGN_TASK,
                TaskPermission::TRACK_PROGRESS,
                TaskPermission::COMPLETE_TASK,
            ],
            
            'leader_sale' => [
                TaskPermission::ASSIGN_TASK,
                TaskPermission::TRACK_PROGRESS,
                TaskPermission::COMPLETE_TASK,
            ],
            
            'sale_manager' => [
                TaskPermission::ASSIGN_TASK,
                TaskPermission::TRACK_PROGRESS,
            ],
            
            // Sale: can complete tasks
            'sale' => [
                TaskPermission::COMPLETE_TASK,
                TaskPermission::VIEW_TASK_HISTORY,
            ],
            
            // Account: can complete tasks
            'account' => [
                TaskPermission::COMPLETE_TASK,
                TaskPermission::VIEW_TASK_HISTORY,
            ],
            
            // Staff: can complete tasks
            'staff' => [
                TaskPermission::COMPLETE_TASK,
                TaskPermission::VIEW_TASK_HISTORY,
            ],
            
            // Warehouse: can complete tasks
            'warehouse' => [
                TaskPermission::COMPLETE_TASK,
                TaskPermission::VIEW_TASK_HISTORY,
            ],
        ];

        foreach ($rolePermissions as $roleName => $permissions) {
            $role = Role::where('name', $roleName)->first();
            
            if (!$role) {
                // Create role if it doesn't exist
                $role = Role::create([
                    'name' => $roleName,
                    'description' => "Role $roleName with task permissions",
                ]);
            }

            // Add permissions to the role
            foreach ($permissions as $permissionSlug) {
                TaskPermission::firstOrCreate([
                    'role_id' => $role->id,
                    'permission_slug' => $permissionSlug,
                ]);
            }
        }

        $this->command->info('Task permissions seeded successfully!');
    }
}
