<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Role;
use App\Models\TruckRoute;
use App\Models\TruckStation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyCustomerTruckStationTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_chooses_a_station_directly_and_removes_the_route_link(): void
    {
        $saleRole = Role::query()->create(['name' => 'sale']);
        $sale = User::factory()->create();
        $sale->roles()->attach($saleRole);

        $oldRoute = TruckRoute::query()->create([
            'name' => 'Tuyến cũ',
            'is_active' => true,
        ]);
        $station = TruckStation::query()->create([
            'name' => 'Trạm xe Miền Đông',
            'address' => '123 Quốc lộ 13',
            'phone' => '0909000111',
            'is_active' => true,
        ]);
        $customer = Customer::query()->create([
            'user_id' => $sale->id,
            'assigned_to' => $sale->id,
            'name' => 'Khách hàng thử nghiệm',
            'truck_route_id' => $oldRoute->id,
        ]);

        $this->actingAs($sale)
            ->withSession(['active_role' => 'sale'])
            ->get(route('my_customer.edit', $customer))
            ->assertOk()
            ->assertSee('Trạm xe giao hàng')
            ->assertSee('Trạm xe Miền Đông')
            ->assertDontSee('Load tuyến vận chuyển');

        $this->actingAs($sale)
            ->withSession(['active_role' => 'sale'])
            ->put(route('my_customer.update', $customer), [
                'name' => $customer->name,
                'use_truck_station' => '1',
                'truck_station_id' => $station->id,
                'truck_route_id' => '',
                'truck_station_address' => $station->address,
                'truck_station_phone' => $station->phone,
            ])
            ->assertSessionHasNoErrors();

        $customer->refresh();
        $this->assertTrue((bool) $customer->use_truck_station);
        $this->assertSame($station->id, $customer->truck_station_id);
        $this->assertNull($customer->truck_route_id);
        $this->assertSame($station->address, $customer->truck_station_address);
        $this->assertSame($station->phone, $customer->truck_station_phone);
    }
}
