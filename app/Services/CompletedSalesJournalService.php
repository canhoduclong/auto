<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderAdjustment;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CompletedSalesJournalService
{
    public function __construct(private readonly AccountingSalesLedgerService $ledgerService) {}

    /**
     * Build ledger-shaped rows directly from valid sales orders.
     * These rows are a report only and do not create accounting_sales_entries.
     *
     * @return array{rows: LengthAwarePaginator, summary: array{rows:int, orders:int, quantity:float, amount:float}}
     */
    public function paginate(
        string $fromDate,
        string $toDate,
        int $saleId,
        int $customerId,
        string $sort,
        int $perPage,
        int $page,
        string $path,
        array $query = []
    ): array {
        $rows = $this->all($fromDate, $toDate, $saleId, $customerId, $sort);
        $page = max(1, $page);

        return [
            'rows' => new LengthAwarePaginator(
                $rows->forPage($page, $perPage)->values(),
                $rows->count(),
                $perPage,
                $page,
                ['path' => $path, 'query' => $query]
            ),
            'summary' => [
                'rows' => $rows->count(),
                'orders' => $rows->pluck('order_id')->unique()->count(),
                'quantity' => (float) $rows->sum('total_quantity'),
                'amount' => (float) $rows->sum('total_amount'),
            ],
        ];
    }

    /**
     * Return every journal row matching the filters, without pagination.
     */
    public function all(
        string $fromDate,
        string $toDate,
        int $saleId = 0,
        int $customerId = 0,
        string $sort = 'date_desc'
    ): Collection {
        // The journal is grouped by the day the order was entered, but an
        // order only becomes journal-eligible after delivery is completed.
        $dateExpression = 'DATE(orders.created_at)';

        $orders = Order::query()
            ->with([
                'customer:id,name,customer_code',
                'user:id,name',
                'items.product',
                'items.variant.product',
                'additionalFees:id,order_id,order_fee_type_id,fee_code,fee_name,calculation_type,direction,rate,base_amount,amount',
                'adjustments' => fn ($adjustments) => $adjustments
                    ->where('status', OrderAdjustment::STATUS_APPROVED)
                    ->latest('id')
                    ->with('items'),
            ])
            // A partial return does not defer recognition of the quantity
            // already delivered to the customer. Warehouse receipt only
            // completes the returned-stock workflow.
            ->whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED])
            ->whereRaw("{$dateExpression} BETWEEN ? AND ?", [$fromDate, $toDate])
            ->when($saleId > 0, fn ($orders) => $orders->where('user_id', $saleId))
            ->when($customerId > 0, fn ($orders) => $orders->where('customer_id', $customerId))
            ->orderByRaw("{$dateExpression}")
            ->orderBy('id')
            ->get();

        $rows = $orders->flatMap(fn (Order $order) => $this->rowsForOrder($order));

        return $this->sortRows($rows, $sort)->values();
    }

    private function rowsForOrder(Order $order): Collection
    {
        $entryDate = $order->created_at->toDateString();
        $adjustments = $order->adjustments
            ->flatMap->items
            ->filter(fn ($item) => $item->order_item_id)
            ->groupBy('order_item_id')
            ->map->first();
        $base = [
            'entry_date' => $entryDate,
            'entry_month' => (int) substr($entryDate, 5, 2),
            'customer_code' => (string) ($order->customer?->customer_code ?? ''),
            'customer_name' => (string) ($order->customer?->name ?: $order->recipient_name ?: 'Khách hàng'),
            'sale_name' => (string) ($order->user?->name ?? ''),
            'order_id' => (int) $order->id,
            'order_code' => (string) ($order->code ?: ('#'.$order->id)),
        ];
        $rows = collect();

        foreach ($order->items as $item) {
            $adjustment = $adjustments->get($item->id);
            $quantity = (float) ($adjustment?->adjusted_quantity ?? $item->quantity ?? 0);
            $totalQuantity = (float) ($adjustment?->adjusted_weight
                ?? $item->actual_weight
                ?? $item->packed_weight
                ?? $item->total_weight
                ?? $item->display_total_value);
            $pricedByKg = (bool) $item->effective_priced_by_kg;
            if (! $pricedByKg) {
                $totalQuantity = $quantity;
            }
            $unitWeight = $pricedByKg && abs($quantity) > 0.0001
                ? $totalQuantity / $quantity
                : 1.0;
            $unitPrice = (float) ($adjustment?->adjusted_price ?? $item->price ?? 0);
            $calculatedAmount = ($pricedByKg ? $totalQuantity : $quantity) * $unitPrice;
            $product = $item->product ?: $item->variant?->product;

            $rows->push((object) array_merge($base, [
                'row_key' => 'item:'.$item->id,
                'unit' => $this->ledgerService->ledgerUnit($product?->name ?: $item->display_name, $product?->unit_label),
                'quantity' => $quantity,
                'unit_weight' => $unitWeight,
                'total_quantity' => $totalQuantity,
                'unit_price' => $unitPrice,
                // The journal must use the quantity/weight actually delivered.
                // order_items.total can still contain the ordered or warehouse
                // amount when the customer weighs the goods again at delivery.
                'total_amount' => $calculatedAmount,
                'entry_type' => 'product',
                'direction' => null,
            ]));
        }

        $productTotalQuantity = (float) $rows->sum('total_quantity');

        if ((bool) $order->charge_vat) {
            $vatAmount = (float) ($order->vat_amount ?? 0);
            $rows->push((object) array_merge($base, [
                'row_key' => 'vat',
                'unit' => 'Phí VAT',
                'quantity' => $productTotalQuantity,
                'unit_weight' => 1.0,
                'total_quantity' => $productTotalQuantity,
                'unit_price' => $productTotalQuantity != 0.0 && $vatAmount != 0.0
                    ? $vatAmount / $productTotalQuantity
                    : null,
                'total_amount' => $vatAmount,
                'entry_type' => 'fee',
                'direction' => 'charge',
            ]));
        }

        if ((bool) $order->charge_shipping_fee && (float) ($order->shipping_fee ?? 0) > 0) {
            $shippingFee = (float) $order->shipping_fee;
            $rows->push((object) array_merge($base, [
                'row_key' => 'shipping',
                'unit' => 'Phí Ship',
                'quantity' => 1.0,
                'unit_weight' => 0.0,
                'total_quantity' => 0.0,
                'unit_price' => $shippingFee,
                'total_amount' => $shippingFee,
                'entry_type' => 'fee',
                'direction' => 'charge',
            ]));
        }

        if ((float) ($order->extra_discount_total ?? 0) > 0) {
            $discountAmount = -1 * (float) $order->extra_discount_total;
            $rows->push((object) array_merge($base, [
                'row_key' => 'discount',
                'unit' => 'Chiết khấu đơn',
                'quantity' => 1.0,
                'unit_weight' => 0.0,
                'total_quantity' => 0.0,
                'unit_price' => $discountAmount,
                'total_amount' => $discountAmount,
                'entry_type' => 'fee',
                'direction' => 'discount',
            ]));
        }

        if ((bool) $order->charge_foam_box_fee || (float) ($order->foam_box_price ?? 0) > 0) {
            $foamBoxPrice = (float) ($order->foam_box_price ?? 0);
            $rows->push((object) array_merge($base, [
                'row_key' => 'foam-box',
                'unit' => 'Phí thùng xốp',
                'quantity' => 1.0,
                'unit_weight' => 1.0,
                'total_quantity' => 1.0,
                'unit_price' => $foamBoxPrice,
                'total_amount' => $foamBoxPrice,
                'entry_type' => 'fee',
                'direction' => 'charge',
            ]));
        }

        foreach ($order->additionalFees->whereNotIn('fee_code', ['vat', 'shipping', 'discount', 'foam_box']) as $fee) {
            $isDiscount = (string) $fee->direction === 'discount';
            $amount = ($isDiscount ? -1 : 1) * abs((float) $fee->amount);
            $rows->push((object) array_merge($base, [
                'row_key' => 'fee:'.$fee->id,
                'unit' => (string) ($fee->fee_name ?: $fee->fee_code),
                'quantity' => 1.0,
                'unit_weight' => 0.0,
                'total_quantity' => 0.0,
                'unit_price' => $amount,
                'total_amount' => $amount,
                'entry_type' => 'fee',
                'direction' => $isDiscount ? 'discount' : 'charge',
            ]));
        }

        return $rows;
    }

    private function sortRows(Collection $rows, string $sort): Collection
    {
        return match ($sort) {
            'date_asc' => $rows->sortBy(fn ($row) => [$row->entry_date, $row->order_id, $row->row_key]),
            'product_asc' => $rows->sortBy(fn ($row) => [mb_strtolower($row->unit), $row->entry_date]),
            'product_desc' => $rows->sortByDesc(fn ($row) => [mb_strtolower($row->unit), $row->entry_date]),
            'amount_asc' => $rows->sortBy('total_amount'),
            'amount_desc' => $rows->sortByDesc('total_amount'),
            'qty_asc' => $rows->sortBy('quantity'),
            'qty_desc' => $rows->sortByDesc('quantity'),
            'weight_asc' => $rows->sortBy('total_quantity'),
            'weight_desc' => $rows->sortByDesc('total_quantity'),
            default => $rows->sortByDesc(fn ($row) => [$row->entry_date, $row->order_id])->values(),
        };
    }
}
