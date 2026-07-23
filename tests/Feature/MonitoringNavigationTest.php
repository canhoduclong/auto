<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_monitoring_menu_has_dashboard_and_does_not_render_fake_sequence_slots(): void
    {
        $saleRole = Role::query()->create(['name' => 'sale']);
        $sale = User::factory()->create();
        $sale->roles()->attach($saleRole);

        $response = $this->actingAs($sale)
            ->withSession(['active_role' => 'sale'])
            ->get(route('pages.my_orders.monitoring', ['tab' => 'today']));

        $response->assertOk()
            ->assertSee('Bảng điều khiển')
            ->assertSee(route('pages.my_dashboard'), false)
            ->assertDontSee('aria-label="Điều hướng nhanh theo số thứ tự đơn"', false);
    }
}
