<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ProcessDefinition;
use App\Models\ProcessRun;
use App\Models\ShipperDispatchHistory;
use App\Models\ShippingExpenseClaim;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\User;
use App\Support\ProcessFiles;
use Illuminate\Support\Facades\DB;

class ShippingExpenseService
{
    public function __construct(public ProcessEngine $engine) {}

    public function assignedOrders(User $user): array
    {
        $map = [];
        foreach (ShipperDispatchHistory::whereNotNull('published_at')->whereNull('revoked_at')->orderByDesc('id')->cursor() as $dispatch) {
            foreach ($dispatch->route_plan ?? [] as $plan) {
                if ((int) ($plan['shipper_id'] ?? 0) !== (int) $user->id) {
                    continue;
                }
                foreach ($plan['routes'] ?? [] as $route) {
                    foreach ($route['orders'] ?? [] as $order) {
                        $id = (int) ($order['order_id'] ?? 0);
                        if ($id && ! isset($map[$id])) {
                            $map[$id] = $dispatch->id;
                        }
                    }
                }
            }
        }

        return $map;
    }

    public function snapshot(ShippingExpenseClaim $claim): array
    {
        return ['total' => $claim->total, 'items' => $claim->items()->get(['order_id', 'dispatch_id', 'amount', 'note'])->toArray()];
    }

    private function total(array $items): string
    {
        return number_format(array_sum(array_map(fn ($i) => round((float) $i['amount'], 2), $items)), 2, '.', '');
    }

    public function submit(User $user, array $data, array $attachments = []): ShippingExpenseClaim
    {
        return ProcessFiles::transaction(function (ProcessFiles $files) use ($user, $data, $attachments) {
            $definition = ProcessDefinition::where('activity', 'shipping_expense')->where('is_active', true)->lockForUpdate()->first();
            abort_unless($definition, 422, 'Chưa có quy trình chi phí ship đang áp dụng.');
            $map = $this->assignedOrders($user);
            $ids = array_column($data['items'], 'order_id');
            $orders = Order::whereIn('id', $ids)->where('shipper_id', $user->id)->whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED])->whereNull('shipping_fee_transaction_id')->orderBy('id')->lockForUpdate()->get();
            abort_unless($orders->count() === count($ids), 422, 'Chỉ được gửi phí cho đơn đã giao thuộc Shipper này và chưa tạo phiếu thanh toán.');
            foreach ($ids as $id) {
                abort_unless(isset($map[$id]), 422, 'Đơn chưa có lịch sử điều phối giao cho bạn.');
            }
            abort_if(DB::table('shipping_expense_order_locks')->whereIn('order_id', $ids)->exists(), 422, 'Có đơn đã nằm trong yêu cầu khác hoặc đã chốt phí.');
            $run = $this->engine->start($definition, $user);
            $claim = ShippingExpenseClaim::create(['process_run_id' => $run->id, 'shipper_id' => $user->id, 'note' => $data['note'] ?? null, 'total' => $this->total($data['items'])]);
            foreach ($data['items'] as $item) {
                $claim->items()->create(['order_id' => $item['order_id'], 'dispatch_id' => $map[$item['order_id']], 'amount' => $item['amount'], 'note' => $item['note'] ?? null]);
                DB::table('shipping_expense_order_locks')->insert(['order_id' => $item['order_id'], 'claim_id' => $claim->id]);
            }
            $run->subjects()->create(['subject_type' => 'shipping_expense', 'subject_id' => $claim->id]);
            foreach ($ids as $id) {
                $run->subjects()->create(['subject_type' => 'order', 'subject_id' => $id]);
            }
            $record = $this->engine->record($run, $user, 'submit', $claim->note, $this->snapshot($claim));
            $files->attach($record, $user, $attachments);
            $this->engine->notify($run, 'Shipper gửi yêu cầu. '.$run->statusLabel());

            return $claim;
        });
    }

    public function act(ShippingExpenseClaim $claim, User $user, string $action, ?string $note, array $attachments = []): void
    {
        ProcessFiles::transaction(function (ProcessFiles $files) use ($claim, $user, $action, $note, $attachments) {
            $run = ProcessRun::whereKey($claim->process_run_id)->lockForUpdate()->firstOrFail();
            $claim = ShippingExpenseClaim::whereKey($claim->id)->lockForUpdate()->firstOrFail();
            $this->engine->validateAction($run, $user, $action, $note, count($attachments));
            $this->engine->decide($run, $user, $action);
            if ($run->status === 'confirmed') {
                foreach ($claim->items()->orderBy('order_id')->get() as $item) {
                    $order = Order::whereKey($item->order_id)->lockForUpdate()->firstOrFail();
                    abort_if($order->shipping_fee_transaction_id || ! in_array($order->status, [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED], true), 422, 'Đơn không còn đủ điều kiện chốt phí.');
                    $order->update(['shipping_fee' => $item->amount]);
                }
                $claim->update(['confirmed_at' => now(), 'confirmed_by' => $user->id]);
            }
            if ($run->status === 'rejected') {
                DB::table('shipping_expense_order_locks')->where('claim_id', $claim->id)->delete();
            }
            $event = $run->status === 'confirmed' ? 'confirm' : $action;
            $record = $this->engine->record($run, $user, $event, $note, $this->snapshot($claim));
            $files->attach($record, $user, $attachments);
            $this->engine->notify($run, match ($action) {
                'reject' => 'Yêu cầu bị từ chối. ','revise' => 'Shipper cần điều chỉnh: ',default => 'Đã xác nhận bước. '
            }.($note ?? '').' · '.$run->statusLabel());
        });
    }

    public function revise(ShippingExpenseClaim $claim, User $user, array $data, array $attachments = []): void
    {
        ProcessFiles::transaction(function (ProcessFiles $files) use ($claim, $user, $data, $attachments) {
            $run = ProcessRun::whereKey($claim->process_run_id)->lockForUpdate()->firstOrFail();
            $claim = ShippingExpenseClaim::whereKey($claim->id)->lockForUpdate()->firstOrFail();
            abort_unless($run->status === 'revision' && (int) $claim->shipper_id === (int) $user->id, 403);
            $existing = $claim->items()->get()->keyBy('order_id');
            $ids = array_map('intval', array_column($data['items'], 'order_id'));
            sort($ids);
            $expected = $existing->keys()->map(fn ($id) => (int) $id)->sort()->values()->all();
            abort_unless($ids === $expected, 422, 'Chỉ điều chỉnh chi phí các đơn trong yêu cầu, không thêm hoặc bỏ đơn.');
            foreach ($data['items'] as $item) {
                $existing[$item['order_id']]->update(['amount' => $item['amount'], 'note' => $item['note'] ?? null]);
            }
            $claim->update(['total' => $this->total($data['items']), 'note' => $data['note'] ?? null]);
            $this->engine->resubmit($run, $user);
            $record = $this->engine->record($run, $user, 'resubmit', $claim->note, $this->snapshot($claim));
            $files->attach($record, $user, $attachments);
            $this->engine->notify($run, 'Shipper đã điều chỉnh, gửi trực tiếp về bước yêu cầu sửa. '.$run->statusLabel());
        });
    }

    public function payment(ShippingExpenseClaim $claim, User $user): Transaction
    {
        return DB::transaction(function () use ($claim, $user) {
            ProcessRun::whereKey($claim->process_run_id)->lockForUpdate()->firstOrFail();
            $claim = ShippingExpenseClaim::with('run', 'items.order')->whereKey($claim->id)->lockForUpdate()->firstOrFail();
            abort_unless((int) $claim->shipper_id === (int) $user->id, 403);
            abort_unless($claim->run->status === 'confirmed' && $claim->confirmed_at, 422, 'Kế toán chưa chốt chi phí.');
            abort_if($claim->payment_transaction_id, 422, 'Đã gửi yêu cầu thanh toán.');
            abort_unless((float) $claim->total > 0, 422, 'Chi phí bằng 0 không cần tạo yêu cầu thanh toán.');
            $category = TransactionCategory::firstOrCreate(['code' => 'SHIP_FEE'], ['name' => 'Chi phí giao hàng', 'flow_direction' => 'out', 'sort_order' => 100, 'is_active' => true]);
            $items = $claim->items->values()->map(fn ($i, $index) => ['stt' => $index + 1, 'content' => 'Đơn '.$i->order->code, 'unit' => 'đơn', 'quantity' => 1, 'unit_price' => $i->amount, 'line_total' => $i->amount])->all();
            $transaction = Transaction::create(['amount' => $claim->total, 'type' => 'extra_expense', 'payee_user_id' => $user->id, 'transaction_category_id' => $category->id, 'status' => Transaction::STATUS_PENDING_APPROVAL, 'submitted_by' => $user->id, 'request_source' => 'shipper', 'request_department' => 'Shipper', 'request_form_type' => Transaction::REQUEST_FORM_PAYMENT, 'request_title' => 'Thanh toán chi phí ship #'.$claim->id, 'request_items' => $items, 'request_subtotal' => $claim->total, 'request_total' => $claim->total, 'request_vat' => 0, 'note' => 'Chi phí đã chốt theo quy trình #'.$claim->run->id]);
            foreach ($claim->items->sortBy('order_id') as $item) {
                $order = Order::whereKey($item->order_id)->lockForUpdate()->firstOrFail();
                abort_if($order->shipping_fee_transaction_id, 422, 'Đơn đã có yêu cầu thanh toán.');
                $order->update(['shipping_fee_transaction_id' => $transaction->id]);
            }
            app(ApprovalService::class)->initTransactionApproval($transaction);
            $claim->update(['payment_transaction_id' => $transaction->id]);
            $this->engine->record($claim->run, $user, 'payment', 'Phiếu thanh toán #'.$transaction->id, $this->snapshot($claim));
            $this->engine->notify($claim->run, 'Shipper gửi yêu cầu thanh toán #'.$transaction->id);

            return $transaction;
        });
    }
}
