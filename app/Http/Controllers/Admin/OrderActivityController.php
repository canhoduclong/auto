<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderHistory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class OrderActivityController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->input('date', now()->toDateString());

        return view('admin.order-activity.index', compact('date'));
    }

    public function export(Request $request)
    {
        $validated = $request->validate(['date' => ['required', 'date']]);
        $date = Carbon::parse($validated['date'])->toDateString();

        $histories = OrderHistory::query()
            ->with(['order.customer:id,name', 'user:id,name'])
            ->whereDate('order_histories.created_at', $date)
            ->join('orders', 'orders.id', '=', 'order_histories.order_id')
            ->select('order_histories.*')
            ->orderBy('order_histories.created_at')
            ->orderBy('order_histories.id')
            ->get();

        return response()->streamDownload(function () use ($histories): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\\xEF\\xBB\\xBF");
            fputcsv($handle, [
                'activity_id', 'activity_at', 'order_code', 'customer_name', 'action',
                'role', 'actor_name', 'status_before', 'status_after', 'note',
                'order_created_at', 'delivery_date',
            ]);

            foreach ($histories as $history) {
                fputcsv($handle, [
                    $history->id,
                    optional($history->created_at)->format('Y-m-d H:i:s'),
                    $history->order?->code ?: '#'.$history->order_id,
                    $history->order?->customer?->name ?: $history->order?->recipient_name,
                    $history->action,
                    $history->role,
                    $history->user?->name,
                    $history->status_before,
                    $history->status_after,
                    $history->note,
                    optional($history->order?->created_at)->format('Y-m-d H:i:s'),
                    optional($history->order?->delivery_date)->toDateString(),
                ]);
            }

            fclose($handle);
        }, 'order-activities-'.$date.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportFull(Request $request)
    {
        $validated = $request->validate(['date' => ['required', 'date']]);
        $date = Carbon::parse($validated['date'])->toDateString();

        $historyOrderIds = OrderHistory::query()->whereDate('created_at', $date)->pluck('order_id');
        $transferOrderIds = DB::table('warehouse_transfers')->where(function ($query) use ($date): void {
            $query->whereDate('created_at', $date)->orWhereDate('picked_up_at', $date)->orWhereDate('delivered_at', $date)->orWhereDate('received_at', $date);
        })->pluck('order_id');
        $orderIds = Order::query()
            ->where(function ($query) use ($date): void {
                $query->whereDate('created_at', $date)->orWhereDate('delivery_date', $date);
            })
            ->pluck('id')
            ->merge($historyOrderIds)
            ->merge($transferOrderIds)
            ->unique()
            ->values();

        $orders = DB::table('orders')->whereIn('id', $orderIds)->get();
        $orderItems = DB::table('order_items')->whereIn('order_id', $orderIds)->get();
        $itemIds = $orderItems->pluck('id');
        $histories = DB::table('order_histories')->whereIn('order_id', $orderIds)->get();
        $warehouseTransfers = DB::table('warehouse_transfers')->whereIn('order_id', $orderIds)->get();
        $reservations = DB::table('inventory_reservations')->whereIn('order_item_id', $itemIds)->get();
        $movementRows = DB::table('inventory_movements')->where(function ($query) use ($date): void {
            $query->whereDate('created_at', $date)->orWhereDate('updated_at', $date);
        })->get();
        $inventoryIds = $reservations->pluck('inventory_id')->merge($movementRows->pluck('inventory_id'))->unique()->values();
        $inventories = DB::table('inventories')->whereIn('id', $inventoryIds)->get();

        $payload = [
            'schema_version' => 1,
            'exported_at' => now()->toIso8601String(),
            'activity_date' => $date,
            'tables' => [
                'orders' => $orders,
                'order_items' => $orderItems,
                'order_histories' => $histories,
                'warehouse_transfers' => $warehouseTransfers,
                'inventory_reservations' => $reservations,
                'inventories' => $inventories,
                'inventory_movements' => $movementRows,
            ],
        ];

        return response()->streamDownload(function () use ($payload): void {
            echo "\xEF\xBB\xBF".json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        }, 'server-activity-backup-'.$date.'.json', ['Content-Type' => 'application/json; charset=UTF-8']);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:20480'],
        ]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $headers = fgetcsv($handle);
        $requiredHeaders = ['activity_id', 'activity_at', 'order_code', 'action', 'status_before', 'status_after'];
        if (!$headers || array_diff($requiredHeaders, $headers)) {
            fclose($handle);
            throw ValidationException::withMessages(['file' => 'File không đúng định dạng export hoạt động đơn hàng.']);
        }

        $columns = array_flip($headers);
        $imported = 0;
        $skipped = 0;
        $missingOrders = [];

        DB::transaction(function () use ($handle, $columns, &$imported, &$skipped, &$missingOrders): void {
            while (($row = fgetcsv($handle)) !== false) {
                $get = static fn (string $key): string => trim((string) ($row[$columns[$key]] ?? ''));
                $orderCode = $get('order_code');
                $activityAt = $get('activity_at');
                $action = $get('action');
                if ($orderCode === '' || $activityAt === '' || $action === '') {
                    $skipped++;
                    continue;
                }

                $order = Order::query()
                    ->where('code', $orderCode)
                    ->orWhere(function ($query) use ($orderCode): void {
                        if (str_starts_with($orderCode, '#')) {
                            $query->whereKey((int) substr($orderCode, 1));
                        } else {
                            $query->whereRaw('1 = 0');
                        }
                    })
                    ->first();
                if (!$order) {
                    $missingOrders[$orderCode] = true;
                    $skipped++;
                    continue;
                }

                try {
                    $timestamp = Carbon::parse($activityAt);
                } catch (\Throwable) {
                    $skipped++;
                    continue;
                }

                $duplicate = OrderHistory::query()
                    ->where('order_id', $order->id)
                    ->where('action', $action)
                    ->where('created_at', $timestamp)
                    ->where('note', $get('note'))
                    ->exists();
                if ($duplicate) {
                    $skipped++;
                    continue;
                }

                $history = new OrderHistory([
                    'order_id' => $order->id,
                    'action' => $action,
                    'role' => $get('role'),
                    'status_before' => $get('status_before'),
                    'status_after' => $get('status_after'),
                    'note' => $get('note'),
                ]);
                $history->created_at = $timestamp;
                $history->updated_at = $timestamp;
                $history->saveQuietly();
                $imported++;
            }
        });
        fclose($handle);

        $message = "Đã import {$imported} hoạt động. Bỏ qua {$skipped} dòng.";
        if ($missingOrders !== []) {
            $message .= ' Không tìm thấy mã đơn: '.implode(', ', array_keys($missingOrders)).'.';
        }

        return back()->with('success', $message);
    }

    public function importFull(Request $request)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:json,txt', 'max:51200']]);
        $json = (string) file_get_contents($request->file('file')->getRealPath());
        $payload = json_decode(ltrim($json, "\xEF\xBB\xBF\xEF\xBB\xBF"), true);
        if (!is_array($payload) || (int) ($payload['schema_version'] ?? 0) !== 1 || !is_array($payload['tables'] ?? null)) {
            throw ValidationException::withMessages(['file' => 'File backup không đúng định dạng hoặc sai phiên bản.']);
        }

        $tables = $payload['tables'];
        $allowedTables = ['inventories', 'orders', 'order_items', 'order_histories', 'warehouse_transfers', 'inventory_reservations', 'inventory_movements'];
        $counts = [];

        DB::transaction(function () use ($tables, $allowedTables, &$counts): void {
            $idMap = ['orders' => [], 'order_items' => [], 'inventories' => []];
            foreach (['inventories', 'orders'] as $table) {
                foreach ((array) ($tables[$table] ?? []) as $row) {
                    $row = (array) $row;
                    $oldId = (int) ($row['id'] ?? 0);
                    if ($oldId <= 0) continue;
                    $existing = $table === 'orders'
                        ? DB::table('orders')->where('code', $row['code'] ?? null)->first()
                        : DB::table('inventories')
                            ->where('product_variant_id', $row['product_variant_id'] ?? 0)
                            ->where('warehouse_id', $row['warehouse_id'] ?? 0)
                            ->first();
                    if ($existing) {
                        $localId = (int) $existing->id;
                        DB::table($table)->where('id', $localId)->update($this->importableColumns($table, $row, true));
                        $idMap[$table][$oldId] = $localId;
                    } else {
                        $insertRow = $this->importableColumns($table, $row);
                        unset($insertRow['id']);
                        DB::table($table)->insert($insertRow);
                        $idMap[$table][$oldId] = (int) DB::getPdo()->lastInsertId();
                    }
                }
                $counts[$table] = count((array) ($tables[$table] ?? []));
            }

            foreach ($allowedTables as $table) {
                if (in_array($table, ['inventories', 'orders'], true)) continue;
                $count = 0;
                foreach ((array) ($tables[$table] ?? []) as $row) {
                    $row = (array) $row;
                    $oldItemId = (int) ($row['id'] ?? 0);
                    if ($table === 'order_items' && isset($row['order_id'])) $row['order_id'] = $idMap['orders'][(int) $row['order_id']] ?? $row['order_id'];
                    if ($table === 'order_histories' && isset($row['order_id'])) $row['order_id'] = $idMap['orders'][(int) $row['order_id']] ?? $row['order_id'];
                    if ($table === 'warehouse_transfers' && isset($row['order_id'])) $row['order_id'] = $idMap['orders'][(int) $row['order_id']] ?? $row['order_id'];
                    if ($table === 'inventory_reservations') {
                        $row['order_item_id'] = $idMap['order_items'][(int) ($row['order_item_id'] ?? 0)] ?? $row['order_item_id'] ?? null;
                        $row['inventory_id'] = $idMap['inventories'][(int) ($row['inventory_id'] ?? 0)] ?? $row['inventory_id'] ?? null;
                    }
                    if ($table === 'inventory_movements') $row['inventory_id'] = $idMap['inventories'][(int) ($row['inventory_id'] ?? 0)] ?? $row['inventory_id'] ?? null;
                    if ($table === 'order_items') {
                        $existingItem = DB::table($table)->where('id', $oldItemId)->where('order_id', $row['order_id'] ?? 0)->first();
                        if ($existingItem) {
                            DB::table($table)->where('id', $existingItem->id)->update($this->importableColumns($table, $row, true));
                            $idMap['order_items'][$oldItemId] = (int) $existingItem->id;
                            $count++;
                            continue;
                        }
                        unset($row['id']);
                    }
                    $existingId = null;
                    if ($table === 'order_histories') {
                        $existingId = DB::table($table)->where('order_id', $row['order_id'] ?? 0)->where('action', $row['action'] ?? '')->where('created_at', $row['created_at'] ?? null)->value('id');
                    } elseif ($table === 'warehouse_transfers') {
                        $existingId = DB::table($table)->where('order_id', $row['order_id'] ?? 0)->where('created_at', $row['created_at'] ?? null)->value('id');
                    } elseif ($table === 'inventory_reservations') {
                        $existingId = DB::table($table)->where('order_item_id', $row['order_item_id'] ?? 0)->where('inventory_id', $row['inventory_id'] ?? 0)->value('id');
                    } elseif ($table === 'inventory_movements') {
                        $existingId = DB::table($table)->where('inventory_id', $row['inventory_id'] ?? 0)->where('type', $row['type'] ?? '')->where('created_at', $row['created_at'] ?? null)->value('id');
                    }
                    if ($existingId) {
                        DB::table($table)->where('id', $existingId)->update($this->importableColumns($table, $row, true));
                    } else {
                        $insertRow = $this->importableColumns($table, $row);
                        unset($insertRow['id']);
                        DB::table($table)->insert($insertRow);
                    }
                    if ($table === 'order_items') $idMap['order_items'][$oldItemId] = (int) DB::getPdo()->lastInsertId();
                    $count++;
                }
                $counts[$table] = $count;
            }
        });

        return back()->with('success', 'Đã import backup vận hành: '.collect($counts)->map(fn ($count, $table) => $table.' '.$count)->implode(', ').'.');
    }

    private function importableColumns(string $table, array $row, bool $update = false): array
    {
        $columns = Schema::getColumnListing($table);
        $data = array_intersect_key($row, array_flip($columns));
        if ($update) unset($data['id'], $data['created_at']);

        return $data;
    }
}
