# Task Assignment Feature Enhancement - Implementation Guide

## Overview
This comprehensive enhancement extends the task assignment feature to support multiple roles with permission-based menus, completion tracking, and verification workflows.

## Changes Made

### 1. Database Schema Changes

#### New Tables
- **task_completion_images**: Store uploaded images during task completion
- **task_status_logs**: Track all status changes with timeline
- **task_permissions**: Define which roles can perform task operations

#### Table Modifications
- **task_assignments**: Added completion tracking columns
  - `completion_content` - Required completion details
  - `completion_notes` - Optional additional notes
  - `completion_verified_at` - Verification timestamp
  - `completion_verified_by` - User ID of verifier
  - `rejected_reason` - Reason for rejection

### 2. New Models

#### TaskCompletionImage
- Manages uploaded proof/evidence images
- Relations: `belongsTo(TaskAssignment)`
- Methods: `getImageUrl()`, `scopeOrdered()`

#### TaskStatusLog
- Records all task status transitions
- Relations: `belongsTo(TaskAssignment)`, `belongsTo(User)`
- Static method: `log()` for automatic logging

#### TaskPermission
- Defines role-based task permissions
- Permission slugs:
  - `assign_task` - Can assign tasks
  - `complete_task` - Can complete assigned tasks
  - `verify_completion` - Can verify task completion
  - `view_task_history` - Can view task history
  - `track_progress` - Can track task progress

### 3. Status Constants Update

**Old Status**: draft, pending, in_progress, completed, rejected, cancelled

**New Status**:
- `pending` - Chờ thực hiện (Waiting)
- `processing` - Đang thực hiện (In Progress)
- `completed` - Chờ xác nhận hoàn thành (Awaiting Confirmation)
- `done` - Đã hoàn thành (Completed)
- `rejected` - Yêu cầu làm lại (Request Redo)
- `cancelled` - Hủy (Cancelled)

**Status Color Mapping**:
- pending → secondary (gray)
- processing → primary (blue)
- completed → warning (yellow/orange)
- done → success (green)
- rejected → danger (red)
- cancelled → secondary (gray)

### 4. Services

#### TaskMenuService
Determines which menu items to display based on user permissions.

**Key Methods**:
- `getMenuItems(User)` - Get flat menu items
- `getGroupedMenuItems(User)` - Get grouped menu with categories
- `canAssignTasks(User)` - Check assign permission
- `canCompleteTasks(User)` - Check complete permission
- `canVerifyCompletion(User)` - Check verify permission
- `canTrackProgress(User)` - Check track permission

### 5. Controller Enhancements

#### TaskAssignmentController - New Methods

**Completion Workflow**:
- `completeWithContent()` - Complete task with content and images
- `verifyCompletion()` - Verify/approve completed task
- `rejectCompletion()` - Request redo of task

**Listing/Filtering**:
- `assignedToMe()` - View tasks assigned to current user
- `assignedByMe()` - View tasks assigned by current user
- `inProgress()` - View tasks in progress
- `awaitingVerification()` - View tasks awaiting verification
- `verifyList()` - List tasks to verify
- `history()` - View task history
- `tracking()` - Track task progress (same as verifyList)

### 6. Views

#### New Views
- **complete.blade.php** - Form for completing task with content and images
- **verify.blade.php** - Form for verifying/rejecting task completion

#### Components
- **task-status-badge.blade.php** - Reusable status badge with colors
  - Supports: bootstrap, pill, dot styles
- **task-menu.blade.php** - Sidebar menu component
  - Shows different menu items based on user permissions
  - Two sections: "Người giao việc" and "Người nhận việc"

### 7. Routes

New routes added:
```php
// Completion actions
POST /task-assignments/{id}/complete-content
POST /task-assignments/{id}/verify-completion
POST /task-assignments/{id}/reject-completion

// Listing views
GET /task-assignments/assigned/to-me
GET /task-assignments/assigned/by-me
GET /task-assignments/in-progress
GET /task-assignments/awaiting-verification
GET /task-assignments/verify-list
GET /task-assignments/tracking
GET /task-assignments/history
```

## Installation Steps

### 1. Run Migrations
```bash
php artisan migrate
```

This will create:
- task_completion_images table
- task_status_logs table
- task_permissions table
- Add columns to task_assignments table

### 2. Run Seeders
```bash
php artisan db:seed --class=TaskPermissionSeeder
```

This will assign default task permissions to roles.

### 3. Update Layouts (Optional)

Add the task menu component to your sidebar/layout:
```blade
<x-task-menu />
```

## Usage

### For Task Assigners (Managers, Leaders)

1. **Create Task**: Navigate to "Giao việc" (Assign Task)
2. **View Assigned**: "Danh sách công việc đã giao" shows all your assigned tasks
3. **Track Progress**: "Theo dõi tiến độ" shows real-time task status
4. **Verify Completion**: "Xác nhận hoàn thành" reviews completed tasks
   - Can approve or request redo
   - Add verification notes

### For Task Completers (Sales, Staff, Accounts)

1. **View Tasks**: "Công việc được giao" shows assigned tasks
2. **Start Task**: Update status to "Đang thực hiện"
3. **Complete Task**: Navigate to task and click "Hoàn thành"
   - Enter completion content (required)
   - Add notes (optional)
   - Upload proof images (optional)
4. **Check Status**: "Công việc chờ xác nhận" shows awaiting verification
5. **View History**: "Lịch sử công việc" shows all completed tasks

## Permission System

### Default Role Permissions

| Role | Assign | Complete | Verify | Track | History |
|------|--------|----------|--------|-------|---------|
| Admin | ✓ | ✓ | ✓ | ✓ | ✓ |
| CEO | ✓ | ✓ | ✓ | ✓ | ✓ |
| Manager | ✓ | ✗ | ✓ | ✓ | ✓ |
| Leader | ✓ | ✓ | ✗ | ✓ | ✗ |
| Sale | ✗ | ✓ | ✗ | ✗ | ✓ |
| Account | ✗ | ✓ | ✗ | ✗ | ✓ |
| Staff | ✗ | ✓ | ✗ | ✗ | ✓ |
| Warehouse | ✗ | ✓ | ✗ | ✗ | ✓ |

### Custom Permissions

To add custom permissions:
```php
$role = Role::find($roleId);
TaskPermission::assignPermissionToRole($role, TaskPermission::ASSIGN_TASK);
```

## UI Components

### Status Badge
```blade
<x-task-status-badge status="pending" />
<x-task-status-badge status="completed" style="pill" />
<x-task-status-badge status="done" style="dot" />
```

### Task Menu
```blade
<x-task-menu />
```

## API Integration

### Complete Task with Images
```php
POST /task-assignments/{id}/complete-content
Content-Type: multipart/form-data

Parameters:
- completion_content (required, string, min:10)
- completion_notes (optional, string, max:2000)
- images[] (optional, array of images, max:5MB each)
```

### Verify Completion
```php
POST /task-assignments/{id}/verify-completion
Content-Type: application/json

Parameters:
- verification_notes (optional, string, max:2000)
```

### Reject Completion
```php
POST /task-assignments/{id}/reject-completion
Content-Type: application/json

Parameters:
- rejected_reason (required, string, min:10, max:2000)
```

## Notifications (Optional)

To add notifications for task events, implement:
```php
// In TaskAssignmentController::completeWithContent()
// Notification::send($taskAssignment->creator, new TaskCompletedNotification($taskAssignment, $user));

// Similarly for verification and rejection
```

## Timeline/Activity Log

Task status changes are automatically logged in `task_status_logs` with:
- Old status
- New status
- Changed by user
- Reason/notes
- Timestamp

View timeline in the verification form.

## Images and Storage

- Uploaded images stored in: `storage/app/public/task-completions/{taskId}/{date}/`
- Access via: `asset('storage/path-to-image')`
- Multiple images supported per task
- Sorted by sort_order for display

## Best Practices

1. **Always provide completion content** - Don't leave it blank
2. **Use images as proof** - Especially for customer-facing tasks
3. **Add notes for context** - Help others understand the work
4. **Review before approval** - Check image quality and content completeness
5. **Document rejections** - Provide clear feedback for redo

## Migration from Old System

If migrating from old task system:
1. Update existing status values using script
2. Run migrations to add new columns
3. Seed task permissions
4. Test with test data
5. Update views to use new components

## Troubleshooting

### Menu not showing
- Check: User has required role with task permissions
- Check: Role is in default permission seeder
- Run: `php artisan db:seed --class=TaskPermissionSeeder`

### Images not uploading
- Check: disk configuration in config/filesystems.php
- Check: Directory permissions: `chmod 775 storage/app/public`
- Check: Symbolic link: `php artisan storage:link`

### Status transitions not logging
- Check: Database migration ran successfully
- Check: task_status_logs table exists
- Check: No errors in logs

## Files Modified/Created

**Created Files**:
- `/database/migrations/2025_05_09_000001_enhance_task_assignments_for_completion.php`
- `/database/migrations/2025_05_09_000002_create_task_completion_images_table.php`
- `/database/migrations/2025_05_09_000003_create_task_status_logs_table.php`
- `/database/migrations/2025_05_09_000004_create_task_permissions_table.php`
- `/app/Models/TaskCompletionImage.php`
- `/app/Models/TaskStatusLog.php`
- `/app/Models/TaskPermission.php`
- `/app/Services/TaskMenuService.php`
- `/resources/views/task_assignments/complete.blade.php`
- `/resources/views/task_assignments/verify.blade.php`
- `/resources/views/components/task-status-badge.blade.php`
- `/resources/views/components/task-menu.blade.php`
- `/database/seeders/TaskPermissionSeeder.php`

**Modified Files**:
- `/app/Models/TaskAssignment.php`
- `/app/Models/Role.php`
- `/app/Http/Controllers/TaskAssignmentController.php`
- `/routes/web.php`

## Next Steps

1. Run migrations: `php artisan migrate`
2. Seed permissions: `php artisan db:seed --class=TaskPermissionSeeder`
3. Add menu to layouts: `<x-task-menu />`
4. Test with different roles
5. Customize permissions as needed
6. Add notifications for events
7. Create admin panel for permission management

## Support

For issues or questions about the implementation, refer to:
- TaskMenuService documentation
- TaskPermission model methods
- View component code comments
