<?php

namespace Tests\Feature;

use App\Http\Middleware\TrackUserOnlineStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WarehouseShippingLabelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(TrackUserOnlineStatus::class);
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->timestamp('shipping_label_printed_at')->nullable();
            $table->timestamps();
        });
    }

    private function orderForLabel(): Order
    {
        DB::table('orders')->insert(['id' => 91]);
        $order = Order::findOrFail(91);
        $order->setRawAttributes(array_merge($order->getAttributes(), [
            'warehouse_id' => 3, 'code' => 'TEST-91', 'use_truck_station' => true,
            'recipient_name' => 'VỊT QUAY DA GIÒN MINH DIỆU', 'recipient_phone' => '0944.995.312',
            'recipient_address' => '6CJM+WG5, Khóm 5, Giá Rai, Bạc Liêu',
            'truck_station_name' => 'Hộ Phòng, Tỉnh Cà Mau', 'truck_station_phone' => '0913845010',
            'truck_station_address' => 'Cà Mau',
        ]), true);
        $customer = new Customer(['name' => 'Tên khách hiện tại', 'use_truck_station' => false]);
        $customer->setRelation('truckStation', null);
        $order->setRelation('customer', $customer)->setRelation('truckStation', null);
        Route::bind('order', fn () => $order);

        return $order;
    }

    private function warehouseUser(?int $warehouseId = 3, string $role = 'warehouse'): User
    {
        $user = User::factory()->make(['id' => 1, 'warehouse_id' => $warehouseId]);
        $user->setRelation('roles', collect([new Role(['name' => $role])]));

        return $user;
    }

    public function test_preview_uses_order_snapshot_and_does_not_mark_printed(): void
    {
        $this->orderForLabel();
        $this->actingAs($this->warehouseUser())->get('/warehouse/orders/91/shipping-label')
            ->assertOk()->assertSee('0944.995.312')->assertSee('Hộ Phòng, Tỉnh Cà Mau')->assertSee('Chưa in');
        $this->assertNull(DB::table('orders')->value('shipping_label_printed_at'));
    }

    public function test_print_confirmation_persists(): void
    {
        $this->orderForLabel();
        $this->actingAs($this->warehouseUser())->post('/warehouse/orders/91/shipping-label', ['printed' => 1])
            ->assertRedirect('/warehouse/orders/91/shipping-label');
        $this->assertNotNull(DB::table('orders')->value('shipping_label_printed_at'));
    }

    public function test_can_reset_print_status(): void
    {
        $order = $this->orderForLabel();
        $order->shipping_label_printed_at = now();
        $order->saveQuietly();
        $this->actingAs($this->warehouseUser())->post('/warehouse/orders/91/shipping-label', ['printed' => 0])->assertRedirect();
        $this->assertNull(DB::table('orders')->value('shipping_label_printed_at'));
    }

    public function test_other_warehouse_cannot_mark_printed(): void
    {
        $this->orderForLabel();
        $this->actingAs($this->warehouseUser(4))->postJson('/warehouse/orders/91/shipping-label', ['printed' => 1])->assertForbidden();
        $this->assertNull(DB::table('orders')->value('shipping_label_printed_at'));
    }

    public function test_unassigned_warehouse_cannot_print(): void
    {
        $this->orderForLabel();
        $this->actingAs($this->warehouseUser(null))->getJson('/warehouse/orders/91/shipping-label')->assertForbidden();
    }

    public function test_invalid_print_status_is_rejected(): void
    {
        $this->orderForLabel();
        $this->actingAs($this->warehouseUser())->postJson('/warehouse/orders/91/shipping-label', ['printed' => 'invalid'])
            ->assertUnprocessable()->assertJsonValidationErrors('printed');
    }

    public function test_non_truck_order_has_no_label(): void
    {
        $order = $this->orderForLabel();
        $order->use_truck_station = false;
        $this->actingAs($this->warehouseUser())->getJson('/warehouse/orders/91/shipping-label')->assertNotFound();
    }

    public function test_legacy_order_uses_customer_transport_setting(): void
    {
        $order = $this->orderForLabel();
        $order->use_truck_station = null;
        $order->customer->use_truck_station = true;
        $this->assertTrue($order->shippingLabelData()['enabled']);
        $order->use_truck_station = false;
        $this->assertFalse($order->shippingLabelData()['enabled']);
    }

    public function test_guest_is_sent_to_login(): void
    {
        $this->get('/warehouse/orders/91/shipping-label')->assertRedirect('/login');
    }

    public function test_sales_role_cannot_print(): void
    {
        $this->orderForLabel();
        $this->actingAs($this->warehouseUser(3, 'sale'))->getJson('/warehouse/orders/91/shipping-label')->assertForbidden();
    }

    public function test_admin_can_print_and_customer_text_is_escaped(): void
    {
        $order = $this->orderForLabel();
        $order->recipient_name = '<script>alert(1)</script>';
        $this->actingAs($this->warehouseUser(null, 'admin'))->get('/warehouse/orders/91/shipping-label')
            ->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    }
}
