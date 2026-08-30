<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\GoogleSheetsOrderService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use ReflectionMethod;
use Tests\TestCase;

class GoogleSheetsOrderServiceTest extends TestCase
{
    public function test_it_maps_an_approved_order_without_writing_to_manual_confirmation_column(): void
    {
        $order = $this->order(Order::STATUS_APPROVED);

        $values = $this->invoke('orderValues', $order);
        $details = $this->invoke('detailValues', $order);

        $this->assertSame('ORD-SHEET-001', $values[0]);
        $this->assertSame('30/08/2026', $values[1]);
        $this->assertSame('08:15', $values[2]);
        $this->assertSame('NV An', $values[3]);
        $this->assertSame('Khách Sheet', $values[4]);
        $this->assertSame('0909000111', $values[5]);
        $this->assertSame('31/08/2026 09:30', $values[6]);
        $this->assertSame('Đơn mới', $values[7]);
        $this->assertSame('', $values[8], 'Cột xác nhận nhận hàng phải được để cho nhân sự nhập trên Sheet.');
        $this->assertSame('Giao hàng lạnh', $values[9]);

        $this->assertCount(1, $details);
        $this->assertSame([
            'ORD-SHEET-001', 1, 'Cá hồi', '2.5', 3.0, 7.5, '', '', 'Giao hàng lạnh',
        ], $details[0]);
    }

    public function test_it_maps_cancelled_status_and_reason_in_red_status_payload(): void
    {
        $order = $this->order(Order::STATUS_CANCELLED);
        $order->cancel_reason = 'Khách đổi kế hoạch';

        $values = $this->invoke('orderValues', $order);

        $this->assertSame('Hủy đơn', $values[7]);
        $this->assertSame('Hủy đơn — Khách đổi kế hoạch — Giao hàng lạnh', $values[9]);
    }

    private function order(string $status): Order
    {
        $customer = new Customer(['name' => 'Khách Sheet', 'phone' => '0909000222']);
        $sale = new User(['name' => 'Nguyễn Văn An', 'short_name' => 'NV An']);
        $product = new Product(['name' => 'Cá hồi']);
        $variant = new ProductVariant(['size' => '2.5', 'kg' => 2.5, 'is_priced_by_kg' => true]);
        $variant->setRelation('product', $product);
        $item = new OrderItem([
            'quantity' => 3,
            'unit_weight' => 2.5,
            'total_weight' => 7.5,
            'is_priced_by_kg' => true,
        ]);
        $item->setRelation('variant', $variant);
        $item->setRelation('product', $product);

        $order = new Order([
            'code' => 'ORD-SHEET-001',
            'status' => $status,
            'recipient_phone' => '0909000111',
            'delivery_date' => '2026-08-31',
            'delivery_time' => '09:30',
            'note' => 'Giao hàng lạnh',
        ]);
        $order->created_at = Carbon::create(2026, 8, 30, 8, 15, 0, config('app.timezone'));
        $order->setRelation('customer', $customer);
        $order->setRelation('user', $sale);
        $order->setRelation('items', new Collection([$item]));

        return $order;
    }

    private function invoke(string $method, Order $order): array
    {
        $reflection = new ReflectionMethod(GoogleSheetsOrderService::class, $method);

        return $reflection->invoke(new GoogleSheetsOrderService, $order);
    }
}
