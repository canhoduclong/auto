<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use App\Notifications\AdminEventRecorded;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OrderUpdateNotificationContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_update_notification_names_customer_sale_and_changes(): void
    {
        Notification::fake();

        $adminRole = Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole);
        $sale = User::factory()->create(['name' => 'Nguyễn Văn Sơn', 'short_name' => 'Sơn']);
        $customer = Customer::create(['name' => 'VQGĐ CÔ BẢY', 'status' => 'active']);
        $order = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $sale->id,
            'code' => 'ORD-TEST-857',
            'status' => Order::STATUS_PENDING_LEADER_APPROVAL,
            'delivery_time' => '06:30 - 07:30',
        ]);

        Notification::fake();

        $order->update([
            'status' => Order::STATUS_APPROVED,
            'delivery_time' => '07:30 - 08:30',
        ]);

        Notification::assertSentTo($admin, AdminEventRecorded::class, function (AdminEventRecorded $notification) use ($admin): bool {
            $data = $notification->toArray($admin);

            $this->assertSame('Cập nhật đơn · VQGĐ CÔ BẢY', $data['title']);
            $this->assertStringContainsString('Đơn ORD-TEST-857 của VQGĐ CÔ BẢY · Sale: Sơn.', $data['message']);
            $this->assertStringContainsString('Trạng thái: Chờ Leader duyệt → Đã duyệt', $data['message']);
            $this->assertStringContainsString('Giờ giao: 06:30 - 07:30 → 07:30 - 08:30', $data['message']);
            $this->assertSame('VQGĐ CÔ BẢY', $data['metadata']['customer_name']);
            $this->assertSame('Sơn', $data['metadata']['sale_name']);

            return true;
        });
    }

    public function test_admin_can_correct_delivery_date_but_cannot_set_it_before_order_creation(): void
    {
        $adminRole = Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole);
        $customer = Customer::create(['name' => 'Khách sửa ngày giao', 'status' => 'active']);
        $order = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $admin->id,
            'code' => 'ORD-FIX-DELIVERY-DATE',
            'status' => Order::STATUS_APPROVED,
            'delivery_date' => '2026-07-22',
        ]);
        $order->forceFill(['created_at' => '2026-09-07 10:00:00'])->saveQuietly();

        $payload = [
            'customer_id' => $customer->id,
            'user_id' => $admin->id,
            'status' => Order::STATUS_APPROVED,
            'delivery_date' => '2026-09-08',
        ];

        $this->actingAs($admin)
            ->put(route('orders.update', $order), $payload)
            ->assertRedirect(route('orders.edit', $order));
        $this->assertSame('2026-09-08', $order->fresh()->delivery_date->toDateString());

        $this->actingAs($admin)
            ->put(route('orders.update', $order), array_merge($payload, [
                'delivery_date' => '2026-09-06',
            ]))
            ->assertSessionHasErrors('delivery_date');
        $this->assertSame('2026-09-08', $order->fresh()->delivery_date->toDateString());
    }
}
