<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\OrderFee;
use App\Services\AccountingSalesLedgerService;
use App\Services\CompletedSalesJournalService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class CompletedSalesJournalFeeRowsTest extends TestCase
{
    public function test_journal_outputs_system_and_managed_fees_with_the_correct_direction(): void
    {
        $order = new Order([
            'code' => 'TEST-FEES',
            'charge_vat' => true,
            'vat_amount' => 10000,
            'charge_shipping_fee' => true,
            'shipping_fee' => 30000,
            'extra_discount_total' => 5000,
            'charge_foam_box_fee' => true,
            'foam_box_price' => 12000,
        ]);
        $order->setDateFormat('Y-m-d H:i:s');
        $order->setAttribute('id', 99);
        $order->setAttribute('created_at', Carbon::parse('2026-08-23 08:00:00'));
        $order->setRelation('customer', null);
        $order->setRelation('user', null);
        $order->setRelation('items', collect());
        $order->setRelation('adjustments', collect());
        $order->setRelation('returnRecords', collect());
        $order->setRelation('additionalFees', collect([
            $this->fee(1, 'special_packing', 'Phí đóng gói', 'charge', 20000),
            $this->fee(2, 'vip_discount', 'Ưu đãi VIP', 'discount', 7000),
        ]));

        $method = new ReflectionMethod(CompletedSalesJournalService::class, 'rowsForOrder');
        $method->setAccessible(true);
        $rows = $method->invoke(new CompletedSalesJournalService(new AccountingSalesLedgerService()), $order);

        $this->assertCount(6, $rows);
        $this->assertSame(30000.0, (float) $rows->firstWhere('row_key', 'shipping')->total_amount);
        $this->assertSame(-5000.0, (float) $rows->firstWhere('row_key', 'discount')->total_amount);
        $this->assertSame('charge', $rows->firstWhere('row_key', 'fee:1')->direction);
        $this->assertSame(20000.0, (float) $rows->firstWhere('row_key', 'fee:1')->total_amount);
        $this->assertSame('discount', $rows->firstWhere('row_key', 'fee:2')->direction);
        $this->assertSame(-7000.0, (float) $rows->firstWhere('row_key', 'fee:2')->total_amount);
    }

    private function fee(int $id, string $code, string $name, string $direction, float $amount): OrderFee
    {
        $fee = new OrderFee([
            'fee_code' => $code,
            'fee_name' => $name,
            'calculation_type' => 'fixed',
            'direction' => $direction,
            'rate' => $amount,
            'base_amount' => 100000,
            'amount' => $amount,
        ]);
        $fee->setAttribute('id', $id);

        return $fee;
    }
}
