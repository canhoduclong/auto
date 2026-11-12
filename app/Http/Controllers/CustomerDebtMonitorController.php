<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use App\Services\CustomerDebtBalanceService;
use App\Services\CustomerDebtPolicyService;
use App\Services\CustomerDebtSheetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CustomerDebtMonitorController extends Controller
{
    public function index(Request $request, CustomerDebtSheetService $service)
    {
        $data = $request->validate(['from' => 'nullable|date_format:Y-m-d', 'to' => 'nullable|date_format:Y-m-d', 'tab' => 'nullable|in:orders,all', 'sort' => 'nullable|in:overdue_days', 'direction' => 'nullable|in:asc,desc', 'search' => 'nullable|string|max:150', 'sale' => ['nullable', 'string', 'regex:/^(user:[0-9]+|sheet:[a-f0-9]{16}|unassigned)$/'], 'alert' => 'nullable|in:overdue,remind,medium,high,very_high', 'customer' => 'nullable|string|size:64', 'system_customer' => 'nullable|integer|min:1', 'window' => 'nullable|in:recent,all,custom', 'report_from' => 'nullable|required_with:report_to|date_format:Y-m-d', 'report_to' => 'nullable|required_with:report_from|date_format:Y-m-d|after_or_equal:report_from', 'purchase_page' => 'nullable|integer|min:1', 'payment_page' => 'nullable|integer|min:1', 'adjustment_page' => 'nullable|integer|min:1', 'detail_page' => 'nullable|integer|min:1', 'debt_page' => 'nullable|integer|min:1', 'issue_page' => 'nullable|integer|min:1']);
        $from = $data['from'] ?? now()->toDateString();
        $to = $data['to'] ?? now()->toDateString();
        Validator::make(['from' => $from, 'to' => $to], ['to' => 'after_or_equal:from'])->validate();
        ['canLoad' => $canLoad,'customers' => $customers,'allowedIds' => $allowedIds,'snapshot' => $snapshot,'groups' => $groups,'customerDebts' => $customerDebts,'canAdjust' => $canAdjust,'policyService' => $policyService,'policies' => $policies] = $this->debtContext($request, $service);
        $byId = $customers->keyBy('id');
        $orders = Order::query()->with('customer:id,name,customer_code')
            ->whereDate('delivery_date', '>=', $from)->whereDate('delivery_date', '<=', $to)
            ->when(! $canLoad, fn ($q) => $q->whereIn('customer_id', $allowedIds))
            ->orderBy('delivery_date')->orderBy('id')->paginate(30)->withQueryString();
        $orderCustomerIds = Order::query()->whereDate('delivery_date', '>=', $from)->whereDate('delivery_date', '<=', $to)
            ->when(! $canLoad, fn ($q) => $q->whereIn('customer_id', $allowedIds))->distinct()->pluck('customer_id');
        $selected = isset($data['customer']) ? ($groups[$data['customer']] ?? null) : null;
        abort_if(isset($data['customer']) && ! $selected, 404);
        if (isset($data['system_customer'])) {
            $selected = $customerDebts[(int) $data['system_customer']] ?? null;
            abort_unless($selected, 404);
        }
        // A mapped customer's report includes every source linked to that same customer.
        if ($selected && $selected['customer_id']) {
            $selected = $customerDebts[$selected['customer_id']];
        }
        $history = $selected['entries'] ?? [];
        usort($history, fn ($a, $b) => [$a['date'], $a['type'], $a['row']] <=> [$b['date'], $b['type'], $b['row']]);
        $today = now()->toDateString();
        $recent = ($data['window'] ?? 'recent') === 'recent';
        $reportMode = ! empty($data['report_from']) ? 'custom' : ($recent ? 'recent' : 'all');
        $reportFrom = $data['report_from'] ?? ($recent ? now()->subDays(13)->toDateString() : min($today, $history[0]['date'] ?? $today));
        $reportTo = $data['report_to'] ?? $today;
        $opening = 0;
        $periodEntries = [];
        $reportHistory = [];
        foreach ($history as $entry) {
            if ($entry['date'] > $reportTo) {
                continue;
            }
            $reportHistory[] = $entry;
            if ($entry['date'] < $reportFrom) {
                $opening += $service->movement($entry);
            } else {
                $periodEntries[] = $entry;
            }
        }
        $saleEntries = collect($periodEntries)->where('type', 'sale')->values();
        $paymentEntries = collect($periodEntries)->where('type', 'payment')->values();
        $detailSales = $saleEntries->sum('amount');
        $detailPayments = $paymentEntries->sum('amount');
        $detailGifts = $saleEntries->sum('gift');
        $adjustmentEntries = collect($periodEntries)->where('type', 'adjustment')->values();
        $detailAdjustments = $adjustmentEntries->sum('amount');
        $closing = $opening + $detailSales - $detailGifts - $detailPayments + $detailAdjustments;
        $adjustmentPage = (int) ($data['adjustment_page'] ?? 1);
        $balanceAdjustments = new \Illuminate\Pagination\LengthAwarePaginator($adjustmentEntries->forPage($adjustmentPage, 50)->values(), $adjustmentEntries->count(), 50, $adjustmentPage, ['path' => $request->url(), 'pageName' => 'adjustment_page', 'query' => $request->except('adjustment_page'), 'fragment' => 'debt-detail']);
        $reportAlerts = $selected ? $policyService->evaluate($reportHistory, $selected['policy'], $reportTo) : null;
        $reportLastPurchase = collect($reportHistory)->where('type', 'sale')->where('amount', '>', 0)->max('date');
        $reportLastPayment = collect($reportHistory)->where('type', 'payment')->where('amount', '>', 0)->max('date');
        $purchasePage = (int) ($data['purchase_page'] ?? $data['detail_page'] ?? 1);
        $paymentPage = (int) ($data['payment_page'] ?? 1);
        $purchases = new \Illuminate\Pagination\LengthAwarePaginator($saleEntries->forPage($purchasePage, 100)->values(), $saleEntries->count(), 100, $purchasePage, ['path' => $request->url(), 'pageName' => 'purchase_page', 'query' => $request->except(['purchase_page', 'detail_page']), 'fragment' => 'debt-detail']);
        $payments = new \Illuminate\Pagination\LengthAwarePaginator($paymentEntries->forPage($paymentPage, 100)->values(), $paymentEntries->count(), 100, $paymentPage, ['path' => $request->url(), 'pageName' => 'payment_page', 'query' => $request->except('payment_page'), 'fragment' => 'debt-detail']);
        $issues = collect($snapshot['issues'] ?? []);
        $issueTotal = $issues->sum('amount');
        $issuePage = (int) ($data['issue_page'] ?? 1);
        $issueRows = new \Illuminate\Pagination\LengthAwarePaginator($issues->forPage($issuePage, 30)->values(), $issues->count(), 30, $issuePage, ['path' => $request->url(), 'pageName' => 'issue_page', 'query' => $request->except('issue_page'), 'fragment' => 'debt-issues']);
        $tab = $data['tab'] ?? 'orders';
        $rows = collect($groups);
        if ($tab === 'orders') {
            // Include every customer with an order, even when no Sheet source has been linked yet.
            $rows = $orderCustomerIds->filter(fn ($id) => isset($byId[$id]))->map(function ($id) use ($byId, $customerDebts, $groups, $policyService, $policies) {
                $customer = $byId[$id];
                $debt = $customerDebts[$id] ?? null;
                $source = collect($groups)->firstWhere('customer_id', $id);

                return ($debt ?? ['name' => $customer->name, 'code' => $customer->customer_code, 'customer_id' => $id, 'alerts' => null, 'policy' => $policyService->effective(['customer_id' => $id], $policies)]) + [
                    'key' => $source['key'] ?? null, 'system_customer' => $customer, 'data_available' => $debt !== null,
                ] + $this->saleInfo($customer, [], []);
            });
        }
        $saleOptions = $rows->map(fn ($row) => ['key' => $row['sale_key'], 'label' => $row['sale_label']])->unique('key')->sortBy('label')->values();
        $visible = $rows->filter(function ($g) use ($data, $service) {
            if (! empty($data['sale']) && $g['sale_key'] !== $data['sale']) {
                return false;
            }
            $filter = $data['alert'] ?? '';
            if ($filter === 'overdue' && ! ($g['alerts']['overdue'] ?? false)) {
                return false;
            }
            if ($filter === 'remind' && ! ($g['alerts']['remind'] ?? false)) {
                return false;
            }
            if (in_array($filter, ['medium', 'high', 'very_high'], true) && ($g['alerts']['risk'] ?? null) !== $filter) {
                return false;
            }
            $search = $service->normalize($data['search'] ?? '');

            return $search === '' || str_contains($service->normalize($g['name'].' '.$g['code'].' '.($g['system_customer']?->name ?? '').' '.($g['system_customer']?->customer_code ?? '')), $search);
        });
        if (($data['sort'] ?? null) === 'overdue_days') {
            $direction = ($data['direction'] ?? 'desc') === 'asc' ? 1 : -1;
            $visible = $visible->sort(function ($a, $b) use ($direction) {
                $aDays = $a['alerts']['overdue_days'] ?? null;
                $bDays = $b['alerts']['overdue_days'] ?? null;
                // Unknown Sheet balances remain last in either sort direction.
                if ($aDays === null || $bDays === null) {
                    return ($aDays === null ? 1 : 0) <=> ($bDays === null ? 1 : 0);
                }

                return (($aDays <=> $bDays) * $direction) ?: strcmp($a['name'], $b['name']) ?: strcmp((string) ($a['key'] ?? ''), (string) ($b['key'] ?? ''));
            });
        }
        $page = max(1, (int) $request->input('debt_page', 1));
        $debts = new \Illuminate\Pagination\LengthAwarePaginator($visible->forPage($page, 30)->values(), $visible->count(), 30, $page, ['path' => $request->url(), 'pageName' => 'debt_page', 'query' => $request->except('debt_page')]);

        $connectionEmail = $canLoad ? $service->connectionEmail() : null;

        return view('customer-debt.index', compact('orders', 'groups', 'debts', 'snapshot', 'canLoad', 'from', 'to', 'tab', 'selected', 'opening', 'reportFrom', 'reportTo', 'reportMode', 'reportAlerts', 'reportLastPurchase', 'reportLastPayment', 'purchases', 'payments', 'detailSales', 'detailGifts', 'detailPayments', 'closing', 'issueRows', 'issueTotal', 'customerDebts', 'saleOptions', 'canAdjust', 'balanceAdjustments', 'detailAdjustments', 'connectionEmail'));
    }

    public function adjustBalance(Request $request, CustomerDebtSheetService $service, CustomerDebtBalanceService $balances)
    {
        abort_unless($balances->canAdjust($request->user()), 403);
        $amount = preg_replace('/[\s\x{00a0}]/u', '', trim((string) $request->input('new_balance', '')));
        if (preg_match('/^-?\d{1,3}(?:\.\d{3})+$/', $amount)) {
            $amount = str_replace('.', '', $amount);
        }
        $request->merge(['new_balance' => $amount, 'reason' => trim((string) $request->input('reason', ''))]);
        $data = $request->validate([
            'source_key' => 'required|string|size:64|regex:/^[a-f0-9]{64}$/',
            'new_balance' => 'required|integer|between:-999999999999999,999999999999999',
            'reason' => 'required|string|min:3|max:1000', 'request_id' => 'required|uuid',
            'revision' => 'required|string|size:64|regex:/^[a-f0-9]{64}$/',
        ]);
        $record = $balances->setActual($request->user(), $data, function () use ($request, $service, $data) {
            $context = $this->debtContext($request, $service);
            abort_unless($context['snapshot'], 422, 'Cần tải dữ liệu Sheet trước khi điều chỉnh công nợ.');
            $group = $context['groups'][$data['source_key']] ?? null;
            abort_unless($group, 404);

            return $group['customer_id'] ? $context['customerDebts'][$group['customer_id']] : $group;
        });
        $request->session()->flash('success', 'Đã cập nhật số công nợ thực tế và lưu lịch sử điều chỉnh.');

        return response()->json(['message' => 'Đã cập nhật công nợ.', 'adjustment_id' => $record->id, 'new_balance' => (int) $record->new_balance, 'delta' => (int) $record->delta]);
    }

    private function debtContext(Request $request, CustomerDebtSheetService $service): array
    {
        ['canLoad' => $canLoad, 'customers' => $customers, 'allowedIds' => $allowedIds, 'snapshot' => $snapshot, 'groups' => $groups] = $this->resolveGroups($request, $service);
        $byId = $customers->keyBy('id');
        $balanceService = app(CustomerDebtBalanceService::class);
        $groups = $balanceService->apply($groups, $customers, $allowedIds, $canLoad);
        $snapshotVersion = $balanceService->snapshotVersion($snapshot);
        $canAdjust = $balanceService->canAdjust($request->user());
        $policyService = app(CustomerDebtPolicyService::class);
        $policies = $policyService->policies();
        $customerDebts = [];
        foreach ($groups as $group) {
            if (! $group['customer_id']) {
                continue;
            }
            $id = $group['customer_id'];
            $customerDebts[$id] ??= ['name' => $byId[$id]->name, 'code' => $byId[$id]->customer_code, 'customer_id' => $id, 'sales' => 0, 'gifts' => 0, 'payments' => 0, 'balance' => 0, 'adjustment_total' => 0, 'sheet_balance' => 0, 'source_keys' => [], 'last_purchase_date' => null, 'last_payment_date' => null, 'sale_key' => $group['sale_key'], 'sale_label' => $group['sale_label'], 'sale_origin' => $group['sale_origin'], 'entries' => []];
            foreach (['sales', 'gifts', 'payments', 'balance', 'adjustment_total', 'sheet_balance'] as $field) {
                $customerDebts[$id][$field] += $group[$field];
            }
            foreach (['last_purchase_date', 'last_payment_date'] as $field) {
                $customerDebts[$id][$field] = max($customerDebts[$id][$field] ?? '', $group[$field] ?? '') ?: null;
            }
            $customerDebts[$id]['source_keys'][] = $group['key'];
            $customerDebts[$id]['entries'] = array_merge($customerDebts[$id]['entries'], $group['entries']);
        }
        foreach ($customerDebts as &$debt) {
            $debt['balance_revision'] = $balanceService->revision($debt, $snapshotVersion);
            $debt['policy'] = $policyService->effective($debt, $policies);
            $debt['alerts'] = $policyService->evaluate($debt['entries'], $debt['policy']);
        }
        unset($debt);
        foreach ($groups as &$group) {
            $aggregate = $group['customer_id'] ? $customerDebts[$group['customer_id']] : null;
            $group['policy'] = $aggregate['policy'] ?? $policyService->effective($group, $policies);
            $group['alerts'] = $aggregate['alerts'] ?? $policyService->evaluate($group['entries'], $group['policy']);
            $target = $aggregate ?? $group;
            $group['balance_revision'] = $aggregate['balance_revision'] ?? $balanceService->revision($group, $snapshotVersion);
            $group['display_balance'] = $target['balance'];
            $group['display_adjustment_total'] = $target['adjustment_total'];
            $group['balance_target_name'] = $target['name'];
            $group['balance_is_aggregate'] = count($aggregate['source_keys'] ?? []) > 1;
        }
        unset($group);
        $groups = collect($groups)->sortByDesc('display_balance')->all();

        return compact('canLoad', 'customers', 'allowedIds', 'snapshot', 'groups', 'customerDebts', 'canAdjust', 'policyService', 'policies');
    }

    private function resolveGroups(Request $request, CustomerDebtSheetService $service): array
    {
        $canLoad = $request->user()->hasRole(['admin', 'account', 'accountant', 'accounting', 'director', 'ceo']);
        $customers = Customer::query()->with(['currentOwner:id,name,short_name', 'assignedTo:id,name,short_name'])->get(['id', 'name', 'customer_code', 'current_owner_sale_id', 'assigned_to']);
        $allowedIds = $canLoad ? $customers->pluck('id') : Customer::query()->managedBySale($request->user()->id)->pluck('id');
        $snapshot = $service->snapshot();
        $groups = $service->summarize($snapshot['entries'] ?? []);
        $mappings = $service->mappings();
        $byId = $customers->keyBy('id');
        $byCode = $customers->filter(fn ($c) => trim((string) $c->customer_code) !== '')->groupBy(fn ($c) => mb_strtoupper(trim($c->customer_code)));
        $byName = $customers->groupBy(fn ($c) => $service->customerName($c->name));
        $staffNames = [];
        foreach (User::query()->get(['id', 'name', 'short_name']) as $staff) {
            foreach (array_unique([$service->customerName($staff->name), $service->customerName($staff->short_name ?? '')]) as $name) {
                if ($name !== '') {
                    $staffNames[$name][$staff->id] = $staff;
                }
            }
        }
        $sourceNames = collect($groups)->groupBy(fn ($g) => $service->customerName($g['name']));
        foreach ($groups as $key => &$group) {
            $matches = $group['code'] !== '' ? ($byCode[$group['code']] ?? collect()) : collect();
            $group['matched_by'] = 'code';
            $nameKey = $service->customerName($group['name']);
            if ($matches->isEmpty() && ($sourceNames[$nameKey] ?? collect())->count() === 1) {
                $matches = $byName[$nameKey] ?? collect();
                $group['matched_by'] = 'name';
            }
            $group['customer_id'] = $matches->count() === 1 ? $matches->first()->id : null;
            $group['manual_mapping'] = isset($mappings[$key]);
            if ($group['manual_mapping']) {
                // A saved link overrides automatic matching; deleted targets must be remapped.
                $target = $byId[$mappings[$key]['customer_id']] ?? null;
                $group['customer_id'] = $target?->id;
                $group['matched_by'] = 'manual';
            }
            $group['system_customer'] = $byId[$group['customer_id']] ?? null;
            $group += $this->saleInfo($group['system_customer'], $group, $staffNames);

            if (! $canLoad && ! $allowedIds->contains($group['customer_id'])) {
                unset($groups[$key]);
            }
        }
        unset($group);

        return compact('canLoad', 'customers', 'allowedIds', 'snapshot', 'groups');
    }

    private function saleInfo(?Customer $customer, array $group, array $staffNames): array
    {
        $owner = $customer ? ($customer->current_owner_sale_id !== null ? $customer->currentOwner : $customer->assignedTo) : null;
        if ($owner) {
            return ['sale_key' => 'user:'.$owner->id, 'sale_label' => trim((string) $owner->short_name) ?: trim($owner->name), 'sale_origin' => 'system'];
        }
        $name = trim($group['sale_name'] ?? '');
        if ($name !== '') {
            $matches = $staffNames[Customer::normalizeName($name)] ?? [];
            if (count($matches) === 1) {
                $staff = array_values($matches)[0];

                return ['sale_key' => 'user:'.$staff->id, 'sale_label' => trim((string) $staff->short_name) ?: trim($staff->name), 'sale_origin' => 'sheet'];
            }

            return ['sale_key' => 'sheet:'.substr(hash('sha256', Customer::normalizeName($name)), 0, 16), 'sale_label' => $name, 'sale_origin' => 'sheet'];
        }

        return ['sale_key' => 'unassigned', 'sale_label' => 'Chưa xác định', 'sale_origin' => null];
    }

    public function updatePolicy(Request $request, CustomerDebtSheetService $service)
    {
        $limit = trim((string) $request->input('credit_limit', ''));
        if (preg_match('/^\d{1,3}(?:\.\d{3})+$/', $limit)) {
            $limit = str_replace('.', '', $limit);
        }
        $request->merge([
            'credit_limit' => $limit === '' ? null : $limit,
            'allowed_orders' => $request->filled('allowed_orders') ? $request->input('allowed_orders') : null,
            'due_days' => $request->filled('due_days') ? $request->input('due_days') : null,
        ]);
        $data = $request->validate([
            'source_key' => 'required|string|size:64|regex:/^[a-f0-9]{64}$/',
            'policy_version' => 'required|integer|min:0',
            'allowed_orders' => 'nullable|integer|min:0|max:100000',
            'credit_limit' => 'nullable|integer|min:0|max:999999999999999',
            'due_days' => 'nullable|integer|min:0|max:3650',
        ]);
        $context = $this->resolveGroups($request, $service);
        $group = $context['groups'][$data['source_key']] ?? null;
        abort_unless($group, 404);
        app(CustomerDebtPolicyService::class)->save($group, $data, $request->user()->id, $request->user()->isAdmin(), (int) $data['policy_version'], $request->user()->isSalesFlowRole() || $request->user()->isAdmin() ? $request->user() : null);

        return back()->with('success', 'Đã cập nhật tiêu chí công nợ và tính lại cảnh báo khách hàng.');
    }

    public function unlockPolicy(Request $request, CustomerDebtSheetService $service)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $data = $request->validate(['source_key' => 'required|string|size:64|regex:/^[a-f0-9]{64}$/', 'policy_version' => 'required|integer|min:0']);
        $context = $this->resolveGroups($request, $service);
        $group = $context['groups'][$data['source_key']] ?? null;
        abort_unless($group, 404);
        app(CustomerDebtPolicyService::class)->unlock($group, $request->user(), (int) $data['policy_version']);

        return back()->with('success', 'Đã mở khóa tiêu chí. Nhân viên được lưu thêm một lần rồi tự khóa lại.');
    }

    public function customers(Request $request)
    {
        abort_unless($request->user()->hasRole(['admin', 'account', 'accountant', 'accounting', 'director', 'ceo']), 403);
        $data = $request->validate(['search' => 'nullable|string|max:150', 'page' => 'nullable|integer|min:1']);
        $search = trim($data['search'] ?? '');
        $customers = Customer::query()
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('customer_code', 'like', '%'.$search.'%')
                ->orWhere('phone', 'like', '%'.$search.'%')))
            ->orderBy('name')->orderBy('id')
            ->paginate(10, ['id', 'customer_code', 'name', 'phone'], 'page', (int) ($data['page'] ?? 1));

        return response()->json([
            'customers' => $customers->items(),
            'pagination' => ['current_page' => $customers->currentPage(), 'last_page' => $customers->lastPage(), 'total' => $customers->total(), 'from' => $customers->firstItem(), 'to' => $customers->lastItem()],
        ])->header('Cache-Control', 'no-store');
    }

    public function map(Request $request, CustomerDebtSheetService $service)
    {
        abort_unless($request->user()->hasRole(['admin', 'account', 'accountant', 'accounting', 'director', 'ceo']), 403);
        $data = $request->validate([
            'source_key' => 'required|string|size:64|regex:/^[a-f0-9]{64}$/',
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')->whereNull('deleted_at')],
        ]);
        try {
            $service->mapCustomer($data['source_key'], (int) $data['customer_id'], $request->user()->id);
            app(CustomerDebtPolicyService::class)->promoteSource($data['source_key'], (int) $data['customer_id']);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã ghép khách nguồn với khách hệ thống. Liên kết được giữ khi tải lại dữ liệu.');
    }

    public function unmap(Request $request, CustomerDebtSheetService $service)
    {
        abort_unless($request->user()->hasRole(['admin', 'account', 'accountant', 'accounting', 'director', 'ceo']), 403);
        $data = $request->validate(['source_key' => 'required|string|size:64|regex:/^[a-f0-9]{64}$/']);
        $service->unmapCustomer($data['source_key']);

        return back()->with('success', 'Đã bỏ liên kết ghép thủ công. Hệ thống sẽ kiểm tra ghép tự động theo mã / tên.');
    }

    public function load(Request $request, CustomerDebtSheetService $service)
    {
        abort_unless($request->user()->hasRole(['admin', 'account', 'accountant', 'accounting', 'director', 'ceo']), 403);
        $data = $request->validate(['source' => 'required|in:google,paste,excel', 'sales_text' => 'required_if:source,paste|nullable|string|max:15000000', 'payments_text' => 'required_if:source,paste|nullable|string|max:15000000', 'workbook' => 'required_if:source,excel|nullable|file|mimes:xlsx|max:20480']);
        try {
            [$sales, $payments] = match ($data['source']) {
                'google' => $service->fetch(),
                'excel' => $service->workbook($request->file('workbook')->getRealPath()),
                default => [$service->textValues($data['sales_text']), $service->textValues($data['payments_text'])],
            };
            $service->save($sales, $payments, $request->user()->id, $data['source']);
        } catch (\Throwable $e) {
            report($e);
            $message = $e instanceof \Google\Service\Exception ? 'Không đọc được Google Sheet. Kiểm tra quyền đọc của tài khoản dịch vụ và hai tab nguồn.' : ($e instanceof \RuntimeException ? $e->getMessage() : 'Không tải được dữ liệu. Vui lòng kiểm tra nguồn và thử lại.');

            return back()->with('error', $message);
        }

        return back()->with('success', 'Đã cập nhật bán hàng, thanh toán và tính lại công nợ từ cả hai tab. Liên kết khách và tiêu chí cảnh báo được giữ nguyên; không cộng trùng lần tải trước.');
    }
}
