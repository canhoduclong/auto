<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingDailySalesPriorityTest extends TestCase
{
    use RefreshDatabase;

    public function test_date_sort_uses_daily_order_priority(): void
    {
        $accounting = User::factory()->create();
        $accounting->roles()->attach(Role::create(['name' => 'accounting']));
        $sale = User::factory()->create();
        $customer = Customer::create(['name' => 'Khách kiểm tra ưu tiên', 'status' => 'active']);
        $product = Product::create([
            'user_id' => $sale->id,
            'name' => 'Sản phẩm kiểm tra ưu tiên',
            'unit' => 'con',
            'status' => true,
        ]);
        $createdAt = now()->startOfDay()->addHours(9);

        $secondPriority = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $sale->id,
            'code' => 'PRIORITY-2',
            'daily_sequence' => 2,
            'status' => Order::STATUS_COMPLETED,
            'total' => 100000,
            'created_at' => $createdAt,
        ]);
        $secondPriority->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 100000,
            'total' => 100000,
        ]);

        $firstPriority = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $sale->id,
            'code' => 'PRIORITY-1',
            'daily_sequence' => 1,
            'status' => Order::STATUS_COMPLETED,
            'total' => 100000,
            'created_at' => $createdAt,
        ]);
        $firstPriority->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 100000,
            'total' => 100000,
        ]);

        $this->actingAs($accounting)
            ->get(route('accounting.daily-sales', [
                'from_date' => $createdAt->toDateString(),
                'to_date' => $createdAt->toDateString(),
                'sort' => 'date_desc',
            ]))
            ->assertOk()
            ->assertViewHas('items', function ($items) use ($firstPriority): bool {
                return (int) $items->first()->order_id_val === (int) $firstPriority->id;
            })
            ->assertSee('Ưu tiên');
    }
}
