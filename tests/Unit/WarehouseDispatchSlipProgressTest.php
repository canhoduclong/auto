<?php

namespace Tests\Unit;

use App\Models\WarehouseDispatchSlip;
use App\Models\WarehouseDispatchSlipEntry;
use App\Models\WarehouseTransfer;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class WarehouseDispatchSlipProgressTest extends TestCase
{
    public static function states(): array
    {
        return [
            [['pending_shipper_pickup'], 'pending', 0],
            [['in_transit'], 'transit', 0],
            [['delivered_waiting_receive'], 'waiting', 0],
            [['received_completed'], 'completed', 1],
            [['received_completed', 'pending_shipper_pickup'], 'transit', 1],
            [['received_completed', 'delivered_waiting_receive'], 'waiting', 1],
            [['received_completed', 'cancelled'], 'completed', 1],
            [['cancelled'], 'cancelled', 0],
            [[], 'pending', 0],
        ];
    }

    #[DataProvider('states')]
    public function test_progress_uses_transport_status_even_when_slip_is_finalized(array $statuses, string $expected, int $completed): void
    {
        $slip = new WarehouseDispatchSlip(['status' => 'finalized']);
        $slip->setRelation('entries', new Collection(array_map(function ($status) {
            $entry = new WarehouseDispatchSlipEntry(['warehouse_transfer_id' => 1]);
            return $entry->setRelation('warehouseTransfer', new WarehouseTransfer(['status' => $status]));
        }, $statuses)));

        $this->assertSame($expected, $slip->transportProgress()['key']);
        $this->assertSame($completed, $slip->transportProgress()['completed']);
    }
}
