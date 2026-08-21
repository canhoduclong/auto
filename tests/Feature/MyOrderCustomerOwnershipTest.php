<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyOrderCustomerOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_picker_uses_current_owner_instead_of_stale_assignment(): void
    {
        $saleRole = Role::query()->create(['name' => 'sale']);
        $sale = User::factory()->create(['name' => 'Sale hiện tại']);
        $sale->roles()->attach($saleRole);
        $otherSale = User::factory()->create(['name' => 'Sale cũ']);
        $otherSale->roles()->attach($saleRole);

        Customer::query()->create([
            'name' => 'ahfg đúng sale',
            'user_id' => $otherSale->id,
            'assigned_to' => $otherSale->id,
            'current_owner_sale_id' => $sale->id,
            'customer_status' => 'active',
        ]);

        Customer::query()->create([
            'name' => 'ahfg sai sale',
            'user_id' => $sale->id,
            'assigned_to' => $sale->id,
            'current_owner_sale_id' => $otherSale->id,
            'customer_status' => 'active',
        ]);

        $response = $this->actingAs($sale)
            ->withSession(['active_role' => 'sale'])
            ->getJson(route('site.orders.customers.ajax', [
                'mode' => 'single',
                'q' => 'ahfg',
            ]));

        $response->assertOk()->assertJsonFragment(['total' => 1]);

        $html = (string) $response->json('html');
        $this->assertStringContainsString('ahfg đúng sale', $html);
        $this->assertStringContainsString('Sale hiện tại', $html);
        $this->assertStringNotContainsString('ahfg sai sale', $html);
    }

    public function test_customer_picker_keeps_legacy_assignment_fallback_without_current_owner(): void
    {
        $saleRole = Role::query()->create(['name' => 'sale']);
        $sale = User::factory()->create(['name' => 'Sale dữ liệu cũ']);
        $sale->roles()->attach($saleRole);

        Customer::query()->create([
            'name' => 'Khách phân công cũ',
            'assigned_to' => $sale->id,
            'current_owner_sale_id' => null,
            'customer_status' => 'active',
        ]);

        $response = $this->actingAs($sale)
            ->withSession(['active_role' => 'sale'])
            ->getJson(route('site.orders.customers.ajax', ['mode' => 'single']));

        $response->assertOk()->assertJsonFragment(['total' => 1]);

        $html = (string) $response->json('html');
        $this->assertStringContainsString('Khách phân công cũ', $html);
        $this->assertStringContainsString('Sale dữ liệu cũ', $html);
    }
}
