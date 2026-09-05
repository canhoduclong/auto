<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Order;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyDashboardLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_all_scoped_pending_warehouse_requests_and_allows_rejection(): void
    {
        $role = Role::query()->create(['name' => 'sale']);
        $sale = User::factory()->create();
        $sale->roles()->attach($role);
        $customer = Customer::create(['name' => 'Khách xác nhận', 'status' => 'active']);
        $attributes = [
            'customer_id' => $customer->id,
            'user_id' => $sale->id,
            'warehouse_adjustment_status' => Order::WAREHOUSE_ADJUSTMENT_STATUS_PENDING_SALE_CONFIRMATION,
            'warehouse_adjustment_requested_at' => now()->subDays(3),
            'warehouse_adjustment_note' => 'Thiếu hàng cần đổi số lượng',
            'warehouse_adjustment_changes' => [[
                'product_name' => 'Sản phẩm cần xác nhận', 'old_quantity' => 10, 'new_quantity' => 8,
            ]],
        ];
        for ($i = 1; $i <= 11; $i++) {
            $order = Order::create(array_merge($attributes, ['code' => 'WH-REQUEST-'.$i]));
        }
        $other = Order::create(array_merge($attributes, [
            'code' => 'OTHER-SALE-REQUEST', 'user_id' => User::factory()->create()->id,
        ]));
        Order::create(array_merge($attributes, [
            'code' => 'ALREADY-CONFIRMED',
            'warehouse_adjustment_status' => Order::WAREHOUSE_ADJUSTMENT_STATUS_SALE_CONFIRMED,
        ]));

        $this->actingAs($sale)->withSession(['active_role' => 'sale'])
            ->get(route('pages.my_dashboard'))
            ->assertOk()
            ->assertViewHas('pendingWarehouseAdjustments', fn ($requests) => $requests->count() === 11)
            ->assertSee('Sản phẩm cần xác nhận')
            ->assertSee('Thiếu hàng cần đổi số lượng')
            ->assertSee(route('pages.my_dashboard.order_adjustments.confirm', $order), false)
            ->assertDontSee('OTHER-SALE-REQUEST')
            ->assertDontSee('ALREADY-CONFIRMED');

        $this->post(route('pages.my_dashboard.order_adjustments.reject', $other), ['reject_reason' => 'Không đồng ý'])
            ->assertForbidden();
        $this->from(route('pages.my_dashboard'))
            ->post(route('pages.my_dashboard.order_adjustments.reject', $order), ['reject_reason' => 'Giữ số lượng đã đặt'])
            ->assertRedirect(route('pages.my_dashboard'));
        $this->assertSame(Order::WAREHOUSE_ADJUSTMENT_STATUS_SALE_REJECTED, $order->fresh()->warehouse_adjustment_status);
        $this->get(route('pages.my_dashboard'))
            ->assertViewHas('pendingWarehouseAdjustments', fn ($requests) => $requests->count() === 10);
    }

    public function test_sales_dashboard_uses_the_new_three_column_layout(): void
    {
        $saleRole = Role::query()->create(['name' => 'sale']);
        $sale = User::factory()->create();
        $sale->roles()->attach($saleRole);

        $response = $this->actingAs($sale)
            ->withSession(['active_role' => 'sale'])
            ->get(route('pages.my_dashboard'));

        $response->assertOk()
            ->assertSee('dashboard-shell', false)
            ->assertSee('Bảng điều khiển')
            ->assertSee('Đơn hôm nay')
            ->assertSee('Đơn hàng Mẫu')
            ->assertSee('Đơn của tôi')
            ->assertSee('Danh sách khách hàng')
            ->assertSee('Sản lượng khách hàng lấy trong tuần')
            ->assertSee('Sản lượng khách hàng lấy hàng theo 7 ngày trong tuần')
            ->assertSee('Đơn hàng theo lịch')
            ->assertSee('Đơn hàng tự động')
            ->assertSee('Chúc mừng nhận hoa hồng')
            ->assertSee('Biểu đồ doanh số')
            ->assertSee('Thông báo phòng ban')
            ->assertSee('Bảng báo giá sản phẩm')
            ->assertSee(route('pages.my_orders.monitoring', ['tab' => 'automatic']), false);
    }

    public function test_manager_dashboard_is_rendered_in_the_existing_middle_column(): void
    {
        $managerRole = Role::query()->create(['name' => 'manager']);
        $manager = User::factory()->create();
        $manager->roles()->attach($managerRole);

        $response = $this->actingAs($manager)
            ->withSession(['active_role' => 'manager'])
            ->get(route('pages.my_dashboard', ['from' => now()->startOfWeek()->toDateString(), 'to' => now()->endOfWeek()->toDateString()]));

        $response->assertOk()
            ->assertSee('dashboard-shell', false)
            ->assertSee('dashboard-main', false)
            ->assertSee('manager-board', false)
            ->assertSee('Bảng điều hành phòng kinh doanh')
            ->assertSee('Doanh thu bán hàng')
            ->assertSee('Sản lượng bán theo size')
            ->assertSee('Danh sách mặt hàng bán chạy')
            ->assertSee('Top khách hàng')
            ->assertSee('Xếp hạng sale bán nhiều')
            ->assertSee('KPI tổng hợp')
            ->assertSee('Bảng báo giá sản phẩm');
    }
}
