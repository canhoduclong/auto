<?php

namespace App\Services;

use App\Models\AdminEvent;
use App\Models\OrderHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class UserActivitySummaryService
{
    public const LABELS = [
        'login' => 'Đăng nhập', 'confirmed' => 'Xác nhận đơn / lộ trình',
        'pickup' => 'Lấy hàng', 'shipping' => 'Bắt đầu giao',
        'delivered' => 'Đã giao hàng', 'completed' => 'Hoàn tất đơn',
        'rejected' => 'Từ chối', 'cancelled' => 'Hủy đơn', 'edited' => 'Chỉnh sửa đơn',
        'other' => 'Thao tác khác',
    ];

    public function recordLogin(User $user, Request $request, string $source, ?int $tokenId = null): void
    {
        if (! Schema::hasTable('admin_events')) {
            return;
        }
        AdminEvent::create([
            'actor_id' => $user->id, 'event_type' => 'auth', 'action' => 'login',
            'title' => $source === 'api' ? 'Đăng nhập ứng dụng' : 'Đăng nhập website',
            'metadata' => ['source' => $source, 'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000), 'mobile_token_id' => $tokenId],
        ]);
    }

    public function forUser(User $user, Carbon $from): array
    {
        $until = now();
        $events = Schema::hasTable('admin_events') ? AdminEvent::where('actor_id', $user->id)
            ->whereBetween('created_at', [$from, $until])->get() : collect();
        $history = Schema::hasTable('order_histories') ? OrderHistory::with('order:id,code,customer_id', 'order.customer:id,name')
            ->where('user_id', $user->id)->whereBetween('created_at', [$from, $until])->get() : collect();
        $actions = $history->map(function (OrderHistory $h) {
            [$category, $label] = $this->describe($h->action, $h->status_after);

            return ['at' => $h->created_at, 'category' => $category, 'label' => $label,
                'order_id' => $h->order_id, 'order_code' => $h->order?->code ?: '#'.$h->order_id,
                'customer' => $h->order?->customer?->name, 'source' => $this->source($h->source),
                'history_id' => $h->id, 'action' => $h->action, 'status' => $h->status_after, 'ip' => $h->ip_address, 'note' => $h->note, 'origin' => 'history'];
        });
        // The observer and order history often describe the same status transition.
        $historyByOrder = $actions->groupBy('order_id');
        foreach ($events as $event) {
            $metadata = $event->metadata ?? [];
            $isOrder = $event->event_type === 'order';
            $orderId = $isOrder ? ($metadata['order_id'] ?? $event->subject_id) : null;
            $status = data_get($metadata, 'changes.status');
            if (is_array($status)) {
                $status = $status['after'] ?? $status['new'] ?? null;
            }
            [$category, $label] = $this->describe($event->action, $isOrder ? $status : null);
            if (in_array($event->event_type, ['auth', 'login', 'session'], true) && in_array($event->action, ['login', 'logged_in', 'authenticated'], true)) {
                [$category, $label] = ['login', $event->title ?: 'Đăng nhập'];
            } elseif ($isOrder && $status === null && in_array($event->action, ['updated', 'status_changed', 'update'], true)) {
                [$category, $label] = ['edited', 'Chỉnh sửa đơn hàng'];
            } elseif (! $isOrder || $category === 'other') {
                [$category, $label] = ['other', $event->title ?: 'Thao tác hệ thống'];
            }
            $matchingHistory = $orderId ? ($historyByOrder[$orderId] ?? collect())->first(fn ($h) => abs($h['at']->timestamp - $event->created_at->timestamp) <= 5
                && (($category !== 'other' && $category !== 'edited' && ($h['category'] === $category || ($status !== null && $h['status'] === $status)))
                    || ($event->action === 'created' && $h['action'] === 'create_order'))) : null;
            if ($matchingHistory) {
                $index = $actions->search(fn ($item) => ($item['history_id'] ?? null) === $matchingHistory['history_id']);
                if ($index !== false && $event->message) {
                    $item = $actions[$index];
                    $item['note'] = trim(($item['note'] ?? '')."\n".$event->message);
                    $actions->put($index, $item);
                }

                continue;
            }
            $actions->push(['at' => $event->created_at, 'category' => $category, 'label' => $label,
                'order_id' => $orderId, 'order_code' => $metadata['order_code'] ?? $metadata['code'] ?? ($orderId ? '#'.$orderId : null),
                'customer' => $metadata['customer_name'] ?? null, 'source' => $this->source($metadata['source'] ?? null),
                'ip' => $metadata['ip_address'] ?? null, 'note' => $event->message, 'origin' => 'audit']);
        }
        // Older mobile sign-ins can still be recovered from retained token creation dates.
        if (Schema::hasTable('mobile_api_tokens')) {
            $loggedTokens = $events->where('event_type', 'auth')->pluck('metadata.mobile_token_id')->filter()->all();
            foreach ($user->mobileApiTokens()->whereIn('name', ['flutter-mobile', 'flutter-mobile-google'])->whereBetween('created_at', [$from, $until])->get() as $token) {
                if (in_array($token->id, $loggedTokens)) {
                    continue;
                }
                $actions->push(['at' => $token->created_at, 'category' => 'login', 'label' => 'Đăng nhập ứng dụng',
                    'order_id' => null, 'order_code' => null, 'customer' => null, 'source' => 'Ứng dụng',
                    'ip' => $token->ip_address, 'note' => $token->device_name, 'origin' => 'token']);
            }
        }

        return $this->summarize($actions, $from);
    }

    public function summarize(Collection $actions, Carbon $from): array
    {
        $actions = $actions->sortByDesc('at')->values();
        $days = [];
        for ($date = now()->startOfDay(); $date->gte($from->copy()->startOfDay()); $date->subDay()) {
            $items = $actions->filter(fn ($a) => $a['at']->toDateString() === $date->toDateString())->values();
            $counts = array_fill_keys(array_keys(self::LABELS), 0);
            foreach ($items as $item) {
                $counts[$item['category']]++;
            }
            $days[] = ['date' => $date->copy(), 'counts' => $counts, 'total' => $items->count(),
                'items' => $items->take(100), 'remaining' => max(0, $items->count() - 100)];
        }

        return ['actions' => $actions, 'labels' => self::LABELS, 'counts' => array_replace(array_fill_keys(array_keys(self::LABELS), 0), $actions->countBy('category')->all()),
            'total' => $actions->count(), 'days' => $days];
    }

    public function describe(string $action, ?string $status = null): array
    {
        $explicit = [
            'cancel_order' => ['cancelled', 'Hủy đơn hàng'],
            'auto_cancel_overdue' => ['cancelled', 'Hủy đơn hàng'],
            'reject_order' => ['rejected', 'Từ chối đơn hàng'],
            'shipper_rejected' => ['rejected', 'Từ chối đơn hàng'],
            'shipper_reject' => ['rejected', 'Từ chối đơn hàng'],
            'updated' => ['edited', 'Chỉnh sửa đơn hàng'],
            'update_order' => ['edited', 'Chỉnh sửa đơn hàng'],
            'approve_order' => ['confirmed', 'Duyệt đơn hàng'],
            'shipper_accepted' => ['confirmed', 'Xác nhận nhận đơn'],
            'schedule_confirmed' => ['confirmed', 'Xác nhận lộ trình giao hàng'],
            'shipper_pickup' => ['pickup', 'Lấy hàng'],
            'shipper_pickup_warehouse_transfer' => ['pickup', 'Lấy hàng chuyển kho'],
            'shipper_start_shipping' => ['shipping', 'Bắt đầu giao hàng'],
            'shipper_deliver_warehouse_transfer' => ['delivered', 'Giao hàng chuyển kho'],
            'delivered' => ['delivered', 'Xác nhận đã giao hàng'],
            'shipper_delivered' => ['delivered', 'Xác nhận đã giao hàng'],
            'shipper_complete_payment' => ['completed', 'Hoàn tất đơn và thu tiền'],
            'manual_complete' => ['completed', 'Hoàn tất đơn hàng'],
            'create_order' => ['other', 'Tạo đơn hàng'],
            'mobile_complete_delivery' => ['completed', 'Hoàn tất giao đơn'],
            'undo_complete_delivery' => ['other', 'Hoàn tác hoàn tất đơn'],
            'shipper_acceptance_rolled_back' => ['other', 'Hủy xác nhận nhận đơn'],
            'schedule_revoked' => ['other', 'Thu hồi xác nhận lộ trình'],
            'schedule_rejected' => ['rejected', 'Từ chối lộ trình'],
            'update_delivery_time' => ['other', 'Cập nhật giờ giao hàng'],
            'shipper_refund_request' => ['other', 'Yêu cầu hoàn trả'],
            'return_request' => ['other', 'Yêu cầu trả hàng'],
            'shipper_assigned' => ['other', 'Phân công shipper'],
            'shipper_reassigned' => ['other', 'Đổi shipper'],
            'shipper_unassigned' => ['other', 'Bỏ phân công shipper'],
            'created' => ['other', 'Tạo đơn hàng'],
        ];
        if (isset($explicit[$action])) {
            return $explicit[$action];
        }
        // Status is evidence only for actual status transitions, not unrelated edits.
        if (in_array($action, ['status_changed', 'mobile_update_delivery_status', 'status_updated'], true)) {
            return match ($status) {
                'confirmed', 'approved' => ['confirmed', 'Xác nhận đơn hàng'],
                'picked_up' => ['pickup', 'Lấy hàng'],
                'shipping', 'delivering' => ['shipping', 'Bắt đầu giao hàng'],
                'delivered' => ['delivered', 'Xác nhận đã giao hàng'],
                'completed' => ['completed', 'Hoàn tất đơn hàng'],
                'returning', 'returned', 'returned_completed' => ['other', 'Cập nhật trả hàng'],
                'cancelled' => ['cancelled', 'Hủy đơn hàng'],
                'rejected' => ['rejected', 'Từ chối đơn hàng'],
                default => ['other', 'Cập nhật trạng thái đơn hàng'],
            };
        }

        return ['other', match ($action) {
            'warehouse_complete_packing', 'complete_packing' => 'Hoàn tất đóng gói',
            'warehouse_confirm_pack', 'start_packing' => 'Bắt đầu đóng gói',
            'warehouse_delivery_note_printed' => 'In phiếu giao hàng',
            'warehouse_update_logistics' => 'Cập nhật thông tin giao nhận',
            'warehouse_transfer_received' => 'Xác nhận nhận hàng chuyển kho',
            'schedule_created' => 'Tạo lộ trình giao hàng',
            'shipper_auto_assigned' => 'Tự động phân công shipper',
            'restore_cancelled_order' => 'Khôi phục đơn đã hủy',
            'cancel_order', 'auto_cancel_overdue' => 'Hủy đơn hàng',
            'manager_assign_packing_warehouse' => 'Phân công kho đóng hàng',
            'mark_overdue_delivery' => 'Đánh dấu giao hàng quá hạn',
            'resume_overdue_delivery' => 'Tiếp tục giao đơn quá hạn',
            'warehouse_update_packing_size_mix' => 'Cập nhật quy cách đóng hàng',
            'sale_changed_after_packing' => 'Sửa đơn sau khi đóng hàng',
            'delivery_note_printed' => 'In phiếu giao hàng',
            'shipping_fee_updated' => 'Cập nhật phí giao hàng',
            default => 'Cập nhật đơn hàng',
        }];
    }

    private function source(?string $source): string
    {
        return match ($source) {
            'api', 'mobile' => 'Ứng dụng', 'system' => 'Hệ thống', default => 'Website',
        };
    }
}
