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
            ->assertSee('Đơn hàng theo lịch')
            ->assertSee('Đơn hàng tự động')
            ->assertSee('Chúc mừng nhận hoa hồng')
            ->assertSee('Biểu đồ doanh số')
            ->assertSee('Thông báo phòng ban')
            ->assertSee('Bảng báo giá sản phẩm')
            ->assertSee(route('pages.my_orders.monitoring', ['tab' => 'automatic']), false);
    }
}
