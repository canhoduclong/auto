<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyDashboardLayoutTest extends TestCase
{
    use RefreshDatabase;

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
