<?php

namespace App\Services;

use App\Models\User;
use App\Models\TaskPermission;

class TaskMenuService
{
    /**
     * Get available menu items for a user based on their permissions
     */
    public static function getMenuItems(User $user): array
    {
        $items = [];
        $myTasksRoute = $user->isSalesFlowRole()
            ? 'my-tasks'
            : 'tasks.my-tasks';

        // Check if user can assign tasks
        if (self::canAssignTasks($user)) {
            $items['assign_task'] = [
                'label' => 'Giao việc',
                'icon' => 'assignment',
                'route' => 'task-assignments.create',
                'permission' => TaskPermission::ASSIGN_TASK,
            ];
            $items['task_list_assigned'] = [
                'label' => 'Danh sách công việc đã giao',
                'icon' => 'list',
                'route' => 'tasks.assigned',
                'permission' => TaskPermission::ASSIGN_TASK,
            ];
            $items['task_tracking'] = [
                'label' => 'Theo dõi tiến độ công việc',
                'icon' => 'track_changes',
                'route' => 'task-assignments.tracking',
                'permission' => TaskPermission::TRACK_PROGRESS,
            ];
            $items['task_verify'] = [
                'label' => 'Xác nhận hoàn thành công việc',
                'icon' => 'check_circle',
                'route' => 'task-assignments.verify',
                'permission' => TaskPermission::VERIFY_COMPLETION,
            ];
        }

        // Check if user can complete tasks
        if (self::canCompleteTasks($user)) {
            $items['task_assigned_to_me'] = [
                'label' => 'Công việc được giao',
                'icon' => 'assignment_ind',
                'route' => $myTasksRoute,
                'permission' => TaskPermission::COMPLETE_TASK,
            ];
            $items['task_in_progress'] = [
                'label' => 'Công việc đang thực hiện',
                'icon' => 'play_circle',
                'route' => 'task-assignments.in-progress',
                'permission' => TaskPermission::COMPLETE_TASK,
            ];
            $items['task_awaiting_verification'] = [
                'label' => 'Công việc chờ xác nhận hoàn thành',
                'icon' => 'schedule',
                'route' => 'task-assignments.awaiting-verification',
                'permission' => TaskPermission::COMPLETE_TASK,
            ];
            $items['task_history'] = [
                'label' => 'Lịch sử công việc',
                'icon' => 'history',
                'route' => 'task-assignments.history',
                'permission' => TaskPermission::VIEW_TASK_HISTORY,
            ];
        }

        return $items;
    }

    /**
     * Get grouped menu items organized by category
     */
    public static function getGroupedMenuItems(User $user): array
    {
        $canAssign = self::canAssignTasks($user);
        $canComplete = self::canCompleteTasks($user);
        $myTasksRoute = $user->isSalesFlowRole()
            ? 'my-tasks'
            : 'tasks.my-tasks';

        $groups = [];

        if ($canAssign) {
            $groups['assign'] = [
                'title' => 'Người giao việc',
                'items' => [
                    'assign_task' => [
                        'label' => 'Giao việc',
                        'icon' => 'assignment',
                        'route' => 'task-assignments.create',
                    ],
                    'task_list_assigned' => [
                        'label' => 'Danh sách công việc đã giao',
                        'icon' => 'list',
                        'route' => 'tasks.assigned',
                    ],
                    'task_tracking' => [
                        'label' => 'Theo dõi tiến độ công việc',
                        'icon' => 'track_changes',
                        'route' => 'task-assignments.tracking',
                    ],
                    'task_verify' => [
                        'label' => 'Xác nhận hoàn thành công việc',
                        'icon' => 'check_circle',
                        'route' => 'task-assignments.verify',
                    ],
                ],
            ];
        }

        if ($canComplete) {
            $groups['complete'] = [
                'title' => 'Người nhận việc',
                'items' => [
                    'task_assigned_to_me' => [
                        'label' => 'Công việc được giao',
                        'icon' => 'assignment_ind',
                        'route' => $myTasksRoute,
                    ],
                    'task_in_progress' => [
                        'label' => 'Công việc đang thực hiện',
                        'icon' => 'play_circle',
                        'route' => 'task-assignments.in-progress',
                    ],
                    'task_awaiting_verification' => [
                        'label' => 'Công việc chờ xác nhận hoàn thành',
                        'icon' => 'schedule',
                        'route' => 'task-assignments.awaiting-verification',
                    ],
                    'task_history' => [
                        'label' => 'Lịch sử công việc',
                        'icon' => 'history',
                        'route' => 'task-assignments.history',
                    ],
                ],
            ];
        }

        return $groups;
    }

    /**
     * Check if user can assign tasks
     */
    public static function canAssignTasks(User $user): bool
    {
        // Check if user has task assignment permission through role
        $hasPermission = TaskPermission::userHasPermission($user, TaskPermission::ASSIGN_TASK);

        if (!$hasPermission) {
            $hasPermission = $user->hasPermission('task.create') || $user->hasPermission('task.assign');
        }
        
        // Or check legacy: Admin, CEO, or Manager roles
        $hasRole = $user->hasRole('admin') || $user->hasRole('CEO') || $user->hasRole('manager') 
                || $user->hasRole('leader') || $user->hasRole('sale_manager');

        return $hasPermission || $hasRole;
    }

    /**
     * Check if user can complete assigned tasks
     */
    public static function canCompleteTasks(User $user): bool
    {
        // Check if user has task completion permission through role
        $hasPermission = TaskPermission::userHasPermission($user, TaskPermission::COMPLETE_TASK);

        if (!$hasPermission) {
            $hasPermission = $user->hasPermission('task.complete') || $user->hasPermission('task.view');
        }
        
        // Or check legacy: non-admin roles
        $hasRole = !$user->hasRole('admin') && !$user->hasRole('CEO');

        return $hasPermission || $hasRole;
    }

    /**
     * Check if user can verify task completion
     */
    public static function canVerifyCompletion(User $user): bool
    {
        return TaskPermission::userHasPermission($user, TaskPermission::VERIFY_COMPLETION)
            || $user->hasRole('admin') || $user->hasRole('CEO') || $user->hasRole('manager');
    }

    /**
     * Check if user can view task history
     */
    public static function canViewTaskHistory(User $user): bool
    {
        return TaskPermission::userHasPermission($user, TaskPermission::VIEW_TASK_HISTORY)
            || $user->hasRole('admin');
    }

    /**
     * Check if user can track task progress
     */
    public static function canTrackProgress(User $user): bool
    {
        return TaskPermission::userHasPermission($user, TaskPermission::TRACK_PROGRESS)
            || $user->hasRole('admin') || $user->hasRole('CEO') || $user->hasRole('manager');
    }
}
