<?php

namespace App\Services;

use App\Models\GoogleSheetsOrderSyncRun;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GoogleSheetsOrderReviewService
{
    public function __construct(private readonly GoogleSheetsOrderService $sheets) {}

    public function canUse(User $user): bool
    {
        return $user->hasRole(['admin', 'manager', 'manager_sale', 'director']);
    }

    /** @return array{enabled:bool,needs_sync:bool,last_synced_at:?Carbon,activity_at:?Carbon,order_count:int,deleted_count:int} */
    public function state(User $user, string $date, string $dateField): array
    {
        $dateField = $this->normalizeDateField($dateField);
        if (! $this->canUse($user) || ! $this->sheets->isConfigured()) {
            return ['enabled' => false, 'needs_sync' => false, 'last_synced_at' => null, 'activity_at' => null, 'order_count' => 0, 'deleted_count' => 0];
        }

        $orders = $this->ordersQuery($date, $dateField);
        $orderIds = (clone $orders)->pluck('id');
        $deleted = $this->deletedRecords($date, $dateField);
        $activityAt = $this->latestActivity($orders, $orderIds, $deleted);
        $run = GoogleSheetsOrderSyncRun::query()
            ->whereDate('business_date', $date)
            ->where('date_field', $dateField)
            ->first();
        $hasRows = $orderIds->isNotEmpty() || $deleted->isNotEmpty();

        return [
            'enabled' => true,
            'needs_sync' => $hasRows && (! $run?->synced_activity_at || ($activityAt && $activityAt->gt($run->synced_activity_at))),
            'last_synced_at' => $run?->synced_at,
            'activity_at' => $activityAt,
            'order_count' => $orderIds->count(),
            'deleted_count' => $deleted->count(),
        ];
    }

    /** @return array{orders:int,details:int,deleted:int} */
    public function syncDay(User $user, string $date, string $dateField): array
    {
        abort_unless($this->canUse($user), 403, 'Chỉ Manager được đồng bộ đối soát đơn hàng.');
        $dateField = $this->normalizeDateField($dateField);
        $ordersQuery = $this->ordersQuery($date, $dateField);
        $orders = $ordersQuery->get();
        $orderIds = $orders->pluck('id');
        $deleted = $this->deletedRecords($date, $dateField);
        $activityAt = $this->latestActivity($ordersQuery, $orderIds, $deleted) ?: now();
        $result = $this->sheets->syncReviewOrders($orders, $deleted);

        GoogleSheetsOrderSyncRun::updateOrCreate(
            ['business_date' => $date, 'date_field' => $dateField],
            [
                'synced_by' => $user->id,
                'synced_activity_at' => $activityAt,
                'synced_at' => now(),
                'order_count' => $result['orders'],
                'detail_count' => $result['details'],
                'deleted_count' => $result['deleted'],
            ]
        );

        return $result;
    }

    public function ordersQuery(string $date, string $dateField): Builder
    {
        $query = Order::query()->with([
            'customer:id,name,phone', 'user:id,name,short_name',
            'items.product', 'items.variant.product',
            'adjustments:id,order_id,status,adjustment_note,reject_reason,submitted_at,updated_at',
        ]);
        $dateField = $this->normalizeDateField($dateField);
        if ($dateField === 'created_at') {
            return $query->whereDate('created_at', $date);
        }
        if ($dateField === 'delivery_date') {
            return $query->whereDate('delivery_date', $date);
        }

        return $query->where(function (Builder $dateQuery) use ($date): void {
            $dateQuery->where(function (Builder $regular) use ($date): void {
                $regular->whereNull('accounting_sales_import_batch_id')->whereDate('created_at', $date);
            })->orWhere(function (Builder $imported) use ($date): void {
                $imported->whereNotNull('accounting_sales_import_batch_id')->whereDate('delivery_date', $date);
            });
        });
    }

    public function deletedRecords(string $date, string $dateField): Collection
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('admin_deleted_orders')) {
            return collect();
        }

        return DB::table('admin_deleted_orders')
            ->select(['id', 'order_id', 'order_code', 'reason', 'snapshot', 'deleted_at', 'updated_at'])
            ->get()
            ->filter(fn ($record) => $this->deletedRecordDate($record, $dateField) === $date)
            ->values();
    }

    private function latestActivity(Builder $ordersQuery, Collection $orderIds, Collection $deleted): ?Carbon
    {
        $timestamps = collect([(clone $ordersQuery)->max('orders.updated_at')]);
        if ($orderIds->isNotEmpty()) {
            $timestamps->push(DB::table('order_items')->whereIn('order_id', $orderIds)->max('updated_at'));
            $timestamps->push(DB::table('order_adjustments')->whereIn('order_id', $orderIds)->max('updated_at'));
        }
        $timestamps = $timestamps->concat($deleted->pluck('updated_at'));

        return $timestamps->filter()->map(fn ($value) => Carbon::parse($value))->sortDesc()->first();
    }

    private function deletedRecordDate(object $record, string $dateField): string
    {
        $snapshot = json_decode((string) $record->snapshot, true) ?: [];
        $order = (array) data_get($snapshot, 'order', []);
        $field = $this->normalizeDateField($dateField);
        $value = match ($field) {
            'created_at' => $order['created_at'] ?? null,
            'delivery_date' => $order['delivery_date'] ?? null,
            default => ! empty($order['accounting_sales_import_batch_id'])
                ? ($order['delivery_date'] ?? null)
                : ($order['created_at'] ?? null),
        };

        try {
            return Carbon::parse($value ?: $record->deleted_at)->toDateString();
        } catch (\Throwable) {
            return Carbon::parse($record->deleted_at)->toDateString();
        }
    }

    private function normalizeDateField(string $dateField): string
    {
        return in_array($dateField, ['business_date', 'created_at', 'delivery_date'], true)
            ? $dateField
            : 'business_date';
    }
}
