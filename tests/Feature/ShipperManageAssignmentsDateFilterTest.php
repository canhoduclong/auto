<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipperManageAssignmentsDateFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_manage_assignments_only_loads_orders_created_on_selected_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-26 10:00:00', 'Asia/Bangkok'));

        $managerRole = Role::create(['name' => 'manager_shipper']);
        $manager = User::factory()->create(['name' => 'Quản lý điều phối']);
        $manager->roles()->attach($managerRole);
        $customer = Customer::create([
            'name' => 'Khách lọc ngày',
            'status' => 'active',
        ]);

        $selectedDateOrder = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $manager->id,
            'code' => 'ORDER-ON-SELECTED-DATE',
            'status' => Order::STATUS_READY_TO_PACK,
        ]);
        $selectedDateOrder->forceFill(['created_at' => '2026-08-26 08:00:00'])->saveQuietly();

        $oldRestoredOrder = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $manager->id,
            'code' => 'OLD-RESTORED-ORDER',
            'status' => Order::STATUS_READY_TO_PACK,
            'skip_auto_cancel' => true,
        ]);
        $oldRestoredOrder->forceFill(['created_at' => '2026-08-25 08:00:00'])->saveQuietly();

        $futureOrder = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $manager->id,
            'code' => 'FUTURE-ORDER',
            'status' => Order::STATUS_READY_TO_PACK,
        ]);
        $futureOrder->forceFill(['created_at' => '2026-08-27 08:00:00'])->saveQuietly();

        $this->actingAs($manager)
            ->get(route('shipper.manage-assignments', ['date' => '2026-08-26']))
            ->assertOk()
            ->assertSee('ORDER-ON-SELECTED-DATE')
            ->assertDontSee('OLD-RESTORED-ORDER')
            ->assertDontSee('FUTURE-ORDER')
            ->assertSee('Chỉ hiển thị các đơn được tạo ngày 26/08/2026');
    }
}
