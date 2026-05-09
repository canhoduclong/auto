# Quick Start Guide - Task Assignment Enhancement

## Installation (5 minutes)

### Step 1: Run Migrations
```bash
php artisan migrate
```

This creates:
- `task_completion_images` - Image storage table
- `task_status_logs` - Status history table
- `task_permissions` - Role-based permissions
- Adds columns to `task_assignments` table

### Step 2: Seed Default Permissions
```bash
php artisan db:seed --class=TaskPermissionSeeder
```

Automatically assigns task permissions to roles:
- Admin, CEO: All permissions
- Manager: Assign, Verify, Track
- Leader: Assign, Track, Complete
- Sale, Account, Staff: Complete, View History
- Warehouse: Complete, View History

### Step 3: Add Menu Component (Optional)
Add this to your layout sidebar:
```blade
<x-task-menu />
```

## Testing the Feature

### Test Scenario 1: Create and Complete Task

**User 1 (Manager/Leader):**
1. Go to: `/task-assignments/create`
2. Fill in:
   - Title: "Test task"
   - Description: "Description"
   - Priority: High
   - Assignees: Select staff member
   - Due Date: Tomorrow
3. Click: "Giao việc"

**User 2 (Staff):**
1. Go to: `/task-assignments/assigned/to-me`
2. Click task → "Hoàn thành"
3. Fill completion form:
   - Content: "Task completed successfully..."
   - Notes: Any notes
   - Images: Upload proof images
4. Click: "Gửi hoàn thành"

**User 1 (Back):**
1. Go to: `/task-assignments/verify-list`
2. View completed task
3. Click: "Xem chi tiết & Xác nhận"
4. Either approve or reject

## Menu Structure

```
📋 Task Assignment Menu
├─ 👤 Người giao việc (if canAssignTasks)
│  ├─ ➕ Giao việc
│  ├─ 📝 Công việc đã giao
│  ├─ 📊 Theo dõi tiến độ
│  └─ ✅ Xác nhận hoàn thành
│
└─ 🏷️ Người nhận việc (if canCompleteTasks)
   ├─ 📥 Công việc được giao
   ├─ ▶️ Đang thực hiện
   ├─ ⏳ Chờ xác nhận
   └─ 📜 Lịch sử công việc
```

## Key URLs

| Feature | URL | Role |
|---------|-----|------|
| Create Task | `/task-assignments/create` | Assigner |
| My Tasks | `/task-assignments/assigned/to-me` | Completer |
| My Assignments | `/task-assignments/assigned/by-me` | Assigner |
| In Progress | `/task-assignments/in-progress` | Completer |
| To Verify | `/task-assignments/verify-list` | Assigner |
| History | `/task-assignments/history` | Completer |
| Task Details | `/task-assignments/{id}` | Any |

## Task Workflow

```
┌─────────────────────────────────────────────────┐
│  PENDING (Chờ thực hiện)                        │
│  Assigned but not started                       │
│  → Start Work                                   │
└──────────────┬──────────────────────────────────┘
               │
┌──────────────▼──────────────────────────────────┐
│  PROCESSING (Đang thực hiện)                    │
│  User is working on the task                    │
│  → Complete with content                        │
└──────────────┬──────────────────────────────────┘
               │
┌──────────────▼──────────────────────────────────┐
│  COMPLETED (Chờ xác nhận hoàn thành)            │
│  User submitted completion with content/images  │
│  → Verify/Approve OR Reject                     │
└──────────────┬──────────────────────────────────┘
               │
        ┌──────┴──────┐
        │             │
┌───────▼──────────┐ ┌───────▼──────────────────┐
│  DONE (Hoàn      │ │  REJECTED (Yêu cầu      │
│  thành) ✓        │ │  làm lại) ✗             │
│  Final status    │ │  → Back to PROCESSING   │
└──────────────────┘ └────────────────────────┘
```

## Status Badge Colors

```
pending     → Secondary (Gray)    ⚪
processing  → Primary (Blue)      🔵
completed   → Warning (Orange)    🟠
done        → Success (Green)     ✅
rejected    → Danger (Red)        ❌
cancelled   → Secondary (Gray)    ⚪
```

## API Endpoints (For AJAX)

### Complete Task
```javascript
POST /task-assignments/{id}/complete-content
Content-Type: multipart/form-data

{
  completion_content: "string, min 10 chars",
  completion_notes: "string, optional, max 2000",
  images[]: File[] optional
}

Response: Redirect to show page with success
```

### Verify Completion
```javascript
POST /task-assignments/{id}/verify-completion
Content-Type: application/json

{
  verification_notes: "string, optional, max 2000"
}

Response: Changes status to 'done'
```

### Reject Completion
```javascript
POST /task-assignments/{id}/reject-completion
Content-Type: application/json

{
  rejected_reason: "string, min 10, max 2000, required"
}

Response: Changes status to 'rejected'
```

## Components Usage

### Status Badge
```blade
{{-- Inline badge --}}
<x-task-status-badge :status="'pending'" />

{{-- Pill style --}}
<x-task-status-badge :status="'done'" style="pill" />

{{-- Dot style --}}
<x-task-status-badge :status="'processing'" style="dot" />
```

### Task Menu
```blade
{{-- Shows permission-based menu --}}
<x-task-menu />
```

## Common Issues & Solutions

### Menu not showing
```bash
# Run seeder again
php artisan db:seed --class=TaskPermissionSeeder

# Check user's roles
SELECT * FROM role_user WHERE user_id = {userId};
```

### Images not uploading
```bash
# Create storage link
php artisan storage:link

# Fix permissions
chmod -R 775 storage/app/public
```

### Completion form not submitting
- Verify completion_content has at least 10 characters
- Check file sizes are under 5MB
- Ensure images are valid format (jpg, png, gif, webp)

## Database Tables Reference

### task_assignments (Updated columns)
```
- completion_content: TEXT - Required completion details
- completion_notes: TEXT - Optional notes
- completion_verified_at: TIMESTAMP - When verified
- completion_verified_by: BIGINT - Verifier user ID
- rejected_reason: TEXT - Rejection reason
```

### task_completion_images (New)
```
- id: BIGINT
- task_id: BIGINT (FK)
- image_path: STRING
- original_filename: STRING
- sort_order: INT
- created_at, updated_at: TIMESTAMP
```

### task_status_logs (New)
```
- id: BIGINT
- task_id: BIGINT (FK)
- from_status: STRING
- to_status: STRING
- changed_by: BIGINT (FK)
- reason: TEXT
- created_at: TIMESTAMP
```

### task_permissions (New)
```
- id: BIGINT
- role_id: BIGINT (FK)
- permission_slug: STRING ('assign_task', 'complete_task', etc)
- created_at, updated_at: TIMESTAMP
```

## Customization

### Add Custom Role Permissions
```php
$role = Role::find(1);
TaskPermission::assignPermissionToRole($role, TaskPermission::ASSIGN_TASK);
```

### Change Status Colors
Edit in `TaskAssignment.php`:
```php
public const STATUS_COLORS = [
    self::STATUS_PENDING => 'secondary',  // Change color here
    // ...
];
```

### Customize Menu Items
Edit `TaskMenuService.php` or override in service provider:
```php
app(TaskMenuService::class)->getMenuItems($user);
```

## Environment Notes

- Migrations: Run in order (2025_05_09_000001 → 000004)
- Storage: Images stored in `storage/app/public/task-completions/`
- Queue: Status logs are synchronous
- Transactions: Used for data consistency

## Next Steps

1. ✅ Run migrations
2. ✅ Seed permissions
3. ✅ Add menu to layout
4. ✅ Test with different users
5. ⭐ Consider adding email notifications
6. ⭐ Add dashboard widgets for tasks
7. ⭐ Create admin panel for role permissions

## Support & Documentation

- Full guide: `/TASK_ENHANCEMENT_GUIDE.md`
- Database design: `/database/migrations/2025_05_09_*`
- Service logic: `/app/Services/TaskMenuService.php`
- Views: `/resources/views/task_assignments/`
- Components: `/resources/views/components/task-*`

---
**Implementation Date:** May 9, 2026
**Version:** 1.0
**Status:** Production Ready ✅
