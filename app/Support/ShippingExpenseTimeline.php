<?php

namespace App\Support;

use App\Models\ShippingExpenseClaim;

class ShippingExpenseTimeline
{
    public static function nodes(ShippingExpenseClaim $claim, $users): array
    {
        $run = $claim->run;
        $steps = $run->configuration['steps'];
        $shipperKey = 'user:'.$claim->shipper_id;
        $nodes = [];
        $stepKeys = [];
        $nodes[$shipperKey] = ['label' => 'Shipper', 'state' => 'done', 'person' => $claim->shipper?->name, 'time' => null, 'description' => 'Đã gửi chi phí', 'entries' => []];
        foreach ($steps as $i => $step) {
            $group = ($step['assignment_mode'] ?? 'user_role') === 'role';
            $key = $group ? 'role:'.strtolower($step['role']) : 'user:'.$step['user_id'];
            $stepKeys[$i] = $key;
            $role = ['manager_shipper' => 'Điều phối', 'accountant' => 'Kế toán', 'account' => 'Kế toán', 'accounting' => 'Kế toán'][$step['role'] ?? ''] ?? ($step['role'] ?? '');
            if (! isset($nodes[$key])) {
                $nodes[$key] = ['label' => $step['name'], 'state' => 'pending', 'person' => $group ? 'Nhóm '.$role : ($users->get($step['user_id'] ?? '')?->name ?? 'Người dùng đã xóa'), 'time' => null, 'description' => 'Chưa tới bước này', 'entries' => []];
            } elseif ($key !== $shipperKey) {
                $nodes[$key]['label'] .= ' / '.$step['name'];
            }
        }
        $pointer = 0;
        foreach ($run->events as $event) {
            $action = $event->action;
            $key = in_array($action, ['submit', 'resubmit', 'payment']) ? $shipperKey : ($stepKeys[$pointer] ?? end($stepKeys));
            $label = match ($action) {
                'submit' => 'Đã gửi chi phí','resubmit' => 'Đã bổ sung và nộp lại','payment' => 'Đã gửi yêu cầu thanh toán','revise' => 'Đã yêu cầu bổ sung','reject' => 'Từ chối yêu cầu','confirm' => 'Xác nhận và chốt chi phí','approve' => 'Xác nhận · Chuyển bước tiếp theo',default => $event->actionLabel()
            };
            $nodes[$key]['entries'][] = ['label' => $label, 'note' => $event->note, 'time' => $event->created_at];
            if ($action === 'approve') {
                $pointer++;
            } elseif ($action === 'resubmit' && ($steps[$pointer]['revision_resume'] ?? 'current') === 'first') {
                $pointer = 0;
            }
        }
        if (! $nodes[$shipperKey]['entries']) {
            $nodes[$shipperKey]['entries'][] = ['label' => 'Đã gửi chi phí', 'note' => null, 'time' => $claim->created_at];
        }
        foreach ($stepKeys as $i => $key) {
            if ($run->status === 'confirmed' || $i < $run->current_step) {
                $nodes[$key]['state'] = 'done';
                $nodes[$key]['description'] = 'Đã xác nhận';
            }
        }
        $currentKey = $stepKeys[$run->current_step];
        if ($run->status === 'revision') {
            $nodes[$currentKey]['state'] = 'requested';
            $nodes[$currentKey]['description'] = 'Đã yêu cầu bổ sung · Chờ Shipper nộp lại';
            $nodes[$shipperKey]['state'] = 'revision';
            $nodes[$shipperKey]['description'] = 'Đang thực hiện bổ sung · Chờ nộp lại';
            $nodes[$shipperKey]['note'] = 'Nộp lại cho: '.$steps[$run->resume_step]['name'];
        } elseif ($run->status === 'running') {
            $nodes[$currentKey]['state'] = 'current';
            $nodes[$currentKey]['description'] = 'Đang chờ xử lý';
        } elseif ($run->status === 'rejected') {
            $nodes[$currentKey]['state'] = 'rejected';
            $nodes[$currentKey]['description'] = 'Đã từ chối yêu cầu';
            foreach ($nodes as &$node) {
                if ($node['state'] === 'pending') {
                    $node['state'] = 'stopped';
                    $node['description'] = 'Không thực hiện';
                }
            }unset($node);
        } elseif ($run->status === 'confirmed') {
            $lastKey = end($stepKeys);
            $nodes[$lastKey]['description'] = 'Đã xác nhận và chốt chi phí';
            $nodes[$shipperKey]['state'] = $claim->payment_transaction_id ? 'done' : 'current';
            $nodes[$shipperKey]['description'] = $claim->payment_transaction_id ? 'Đã gửi phiếu thanh toán #'.$claim->payment_transaction_id : 'Chi phí đã chốt · Có thể gửi thanh toán';
        }

        return array_values($nodes);
    }
}
