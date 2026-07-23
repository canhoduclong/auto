<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringCustomersTabTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_tab_supports_default_and_compact_views(): void
    {
        $saleRole = Role::query()->create(['name' => 'sale']);
        $sale = User::factory()->create();
        $sale->roles()->attach($saleRole);
        Customer::query()->create([
            'user_id' => $sale->id,
            'assigned_to' => $sale->id,
            'current_owner_sale_id' => $sale->id,
            'name' => 'Nguyễn Đình Huy',
            'phone' => '0904049522',
            'email' => 'hoalamreal@gmail.com',
            'address' => '716/13 Tân Kỳ Tân Quý',
            'status' => 'active',
        ]);

        $defaultView = $this->actingAs($sale)
            ->withSession(['active_role' => 'sale'])
            ->get(route('pages.my_orders.monitoring', ['tab' => 'customers', 'view' => 'default']));

        $defaultView->assertOk()
            ->assertSee('Khách hàng')
            ->assertSee('Danh sách khách hàng')
            ->assertSee('Nguyễn Đình Huy')
            ->assertSee('Mã KH:')
            ->assertSee('mcl-details', false)
            ->assertSee('data-customer-more', false)
            ->assertSee('data-customer-actions', false)
            ->assertSee('is-actions-open', false)
            ->assertSee('Sửa')
            ->assertSee('Chi tiết')
            ->assertSee('Thanh toán')
            ->assertSee('Lên đơn');

        $compactView = $this->actingAs($sale)
            ->withSession(['active_role' => 'sale'])
            ->get(route('pages.my_orders.monitoring', ['tab' => 'customers', 'view' => 'compact']));

        $compactView->assertOk()
            ->assertSee('Nguyễn Đình Huy')
            ->assertSee('mcl-row is-compact', false)
            ->assertDontSee('Mã KH:');
    }
}
