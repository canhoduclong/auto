<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Notifications\DepartmentBroadcastNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminNotificationManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_center_lists_broadcasts_from_every_sender_but_department_manager_only_manages_own(): void
    {
        $admin = $this->userWithRole('admin');
        $firstManager = $this->userWithRole('manager');
        $secondManager = $this->userWithRole('manager');
        $recipient = $this->userWithRole('sale');
        $broadcastId = (string) Str::uuid();

        Notification::send($recipient, new DepartmentBroadcastNotification(
            'Thông báo liên phòng ban',
            'Nội dung cần theo dõi',
            ['sale'],
            ['sale'],
            null,
            $broadcastId,
            null,
            $firstManager->id,
        ));

        $this->actingAs($admin)
            ->get(route('admin.notifications.index'))
            ->assertOk()
            ->assertSee('Quản trị thông báo')
            ->assertSee('Thông báo liên phòng ban')
            ->assertSee($firstManager->name);

        $this->actingAs($secondManager)
            ->delete(route('department-notifications.destroy', $broadcastId))
            ->assertNotFound();

        $this->assertDatabaseHas('notifications', ['data->broadcast_id' => $broadcastId]);
    }

    public function test_only_admin_can_open_the_global_notification_center(): void
    {
        $manager = $this->userWithRole('manager');

        $this->actingAs($manager)
            ->get(route('admin.notifications.index'))
            ->assertRedirect();
    }

    public function test_admin_can_schedule_priority_notification_for_roles_and_specific_users_without_duplicates(): void
    {
        Carbon::setTestNow('2026-08-28 09:00:00');
        $admin = $this->userWithRole('admin');
        $sale = $this->userWithRole('sale');
        $specificUser = $this->userWithRole('warehouse');

        $this->actingAs($admin)
            ->post(route('admin.notifications.department_broadcast'), [
                'title' => 'Kế hoạch bảo trì',
                'message' => 'Hệ thống bảo trì ngoài giờ làm việc.',
                'priority' => 'danger',
                'target_roles' => ['sale'],
                'target_user_ids' => [$sale->id, $specificUser->id],
                'scheduled_at' => '2026-08-28 10:00:00',
                'expires_at' => '2026-08-29 10:00:00',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(1, $sale->notifications()->count());
        $this->assertSame(1, $specificUser->notifications()->count());
        $notification = $specificUser->notifications()->firstOrFail();
        $this->assertSame('danger', $notification->data['priority']);
        $this->assertSame('2026-08-28 10:00:00', $notification->data['scheduled_at']);

        $this->actingAs($specificUser)
            ->get(route('department-notifications.show', ['notificationId' => $notification->id, 'layout' => 'warehouse']))
            ->assertNotFound();

        Carbon::setTestNow('2026-08-28 10:00:01');
        $this->actingAs($specificUser)
            ->get(route('department-notifications.show', ['notificationId' => $notification->id, 'layout' => 'warehouse']))
            ->assertOk()
            ->assertSee('Kế hoạch bảo trì');
    }

    public function test_admin_can_delete_a_notification_from_own_inbox_only(): void
    {
        $admin = $this->userWithRole('admin');
        $otherAdmin = $this->userWithRole('admin');
        $broadcastId = (string) Str::uuid();

        Notification::send([$admin, $otherAdmin], new DepartmentBroadcastNotification(
            'Thông báo cần xóa',
            'Nội dung thông báo',
            ['admin'],
            ['admin'],
            null,
            $broadcastId,
            null,
            $admin->id,
        ));

        $ownNotification = $admin->notifications()->firstOrFail();
        $otherNotification = $otherAdmin->notifications()->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.notifications.index'))
            ->assertOk()
            ->assertSee(route('admin.notifications.notification.destroy', $ownNotification->id), false)
            ->assertSee('Xóa thông báo');

        $this->actingAs($admin)
            ->delete(route('admin.notifications.notification.destroy', $ownNotification->id))
            ->assertRedirect()
            ->assertSessionHas('success', 'Đã xóa thông báo khỏi hộp thư.');

        $this->assertDatabaseMissing('notifications', ['id' => $ownNotification->id]);
        $this->assertDatabaseHas('notifications', ['id' => $otherNotification->id]);

        $this->actingAs($admin)
            ->delete(route('admin.notifications.notification.destroy', $otherNotification->id))
            ->assertNotFound();
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::query()->firstOrCreate(['name' => $roleName]);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }
}
