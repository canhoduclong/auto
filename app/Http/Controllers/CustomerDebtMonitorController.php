<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Services\CustomerDebtBalanceService;
use App\Services\CustomerDebtBillReconciliation;
use App\Services\CustomerDebtCycleDocuments;
use App\Services\CustomerDebtCycleStatementService;
use App\Services\CustomerDebtManualAllocationService;
use App\Services\CustomerDebtPaymentAllocation;
use App\Services\CustomerDebtPolicyService;
use App\Services\CustomerDebtSheetService;
use App\Services\CustomerDebtVouchers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CustomerDebtMonitorController extends Controller
{
    public function index(Request $request, CustomerDebtSheetService $service)
    {
        $purchasePageSizes = [20, 50, 100, 150, 200];
        $data = $request->validate(['presentation' => 'nullable|in:customer,payment-request,receipt', 'from' => 'nullable|date_format:Y-m-d', 'to' => 'nullable|date_format:Y-m-d', 'tab' => 'nullable|in:orders,all', 'sort' => 'nullable|in:overdue_days', 'direction' => 'nullable|in:asc,desc', 'search' => 'nullable|string|max:150', 'sale' => ['nullable', 'string', 'regex:/^(user:[0-9]+|sheet:[a-f0-9]{16}|unassigned)$/'], 'recovery' => 'nullable|in:normal,difficult,unrecoverable,flagged,all', 'alert' => 'nullable|in:overdue,remind,medium,high,very_high', 'customer' => 'nullable|string|size:64', 'system_customer' => 'nullable|integer|min:1', 'window' => 'nullable|in:recent,all,custom', 'report_from' => 'nullable|required_with:report_to|date_format:Y-m-d', 'report_to' => 'nullable|required_with:report_from|date_format:Y-m-d|after_or_equal:report_from', 'debt_section' => 'nullable|in:cycles,bill,mixed,batch,vouchers', 'voucher_page' => 'nullable|integer|min:1', 'voucher_purchases_page' => 'nullable|integer|min:1', 'voucher_purchases_size' => 'nullable|in:20,50,100', 'voucher_selected' => 'nullable|array|list|max:100', 'voucher_selected.*' => 'required|date_format:Y-m-d|distinct', 'edit_voucher' => 'nullable|uuid', 'new_voucher' => 'nullable|in:receipt,payment-request', 'cycle_date' => 'nullable|date_format:Y-m-d', 'cycle_from' => 'nullable|date_format:Y-m-d', 'transition_preview' => 'nullable|date_format:Y-m-d', 'transition_method' => 'nullable|required_with:transition_preview|in:batch,bill,mixed,cycle', 'transition_end' => 'nullable|date_format:Y-m-d|after_or_equal:transition_preview', 'allocate' => 'nullable|string|max:160', 'purchase_page' => 'nullable|integer|min:1', 'purchase_per_page' => 'nullable|in:'.implode(',', $purchasePageSizes), 'payment_page' => 'nullable|integer|min:1', 'adjustment_page' => 'nullable|integer|min:1', 'detail_page' => 'nullable|integer|min:1', 'debt_page' => 'nullable|integer|min:1', 'issue_page' => 'nullable|integer|min:1']);
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
        if (isset($data['customer']) && ! $selected) {
            $legacy = collect($groups)->firstWhere('legacy_key', $data['customer']);
            if ($legacy) {
                return redirect()->to($request->fullUrlWithQuery(['customer' => $legacy['key']]).'#debt-detail');
            }
            abort(404);
        }
        if (isset($data['system_customer'])) {
            $selected = $customerDebts[(int) $data['system_customer']] ?? null;
            abort_unless($selected, 404);
        }
        // A mapped customer's report includes every source linked to that same customer.
        if ($selected && $selected['customer_id']) {
            $selected = $customerDebts[$selected['customer_id']];
        }
        $purchaseReviews = collect($selected['purchase_reviews'] ?? [])->sortByDesc('date')->values();
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
        $saleEntries = collect($periodEntries)->where('type', 'sale')
            ->sort(fn ($a, $b) => strcmp($b['date'], $a['date']) ?: ($b['row'] <=> $a['row']))->values();
        $paymentEntries = collect($periodEntries)->where('type', 'payment')
            ->sort(fn ($a, $b) => strcmp($b['date'], $a['date']) ?: strcmp($a['source_sheet'] ?? 'Dongtienhoatdong', $b['source_sheet'] ?? 'Dongtienhoatdong') ?: ($b['row'] <=> $a['row']))->values();
        $detailSales = $saleEntries->sum('amount');
        $detailPayments = $paymentEntries->sum('amount');
        $detailSalesPayments = $paymentEntries->where('source_sheet', 'nhatkybanhang')->sum('amount');
        $detailGifts = $saleEntries->sum('gift');
        $adjustmentEntries = collect($periodEntries)->where('type', 'adjustment')->values();
        $detailAdjustments = $adjustmentEntries->sum('amount');
        $closing = $opening + $detailSales - $detailGifts - $detailPayments + $detailAdjustments;
        $adjustmentPage = (int) ($data['adjustment_page'] ?? 1);
        $balanceAdjustments = new \Illuminate\Pagination\LengthAwarePaginator($adjustmentEntries->forPage($adjustmentPage, 50)->values(), $adjustmentEntries->count(), 50, $adjustmentPage, ['path' => $request->url(), 'pageName' => 'adjustment_page', 'query' => $request->except('adjustment_page'), 'fragment' => 'debt-detail']);
        $reportAlerts = $selected ? $policyService->evaluate($reportHistory, $selected['policy'], $reportTo) : null;
        $reportLastPurchase = collect($reportHistory)->where('type', 'sale')->where('amount', '>', 0)->max('date');
        $reportLastPayment = collect($reportHistory)->where('type', 'payment')->where('amount', '>', 0)->max('date');
        $settlementMethod = $selected['policy']['settlement_method'] ?? 'batch';
        $allocationService = app(CustomerDebtPaymentAllocation::class);
        $manualService = app(CustomerDebtManualAllocationService::class);
        $settlementChanges = $selected['policy']['settlement_changes'] ?? [];
        $changesInReport = array_filter($settlementChanges, fn ($date) => $date <= $reportTo, ARRAY_FILTER_USE_KEY);
        $changeDate = array_key_last($changesInReport);
        $currentSettlementMethod = $selected ? CustomerDebtPolicyService::currentSettlementMethod($selected['policy'], $reportTo) : $settlementMethod;
        $manualRecords = $selected ? $manualService->records($selected) : [];
        $confirmedPaymentKeys = collect($manualRecords)->filter(fn ($record) => isset($record['confirmed_group']))
            ->mapWithKeys(fn ($record, $key) => [($record['payment_key'] ?? $key) => true]);
        $allocation = $selected ? $allocationService->reconcile($reportHistory, $settlementMethod, $selected['customer_id'], $changesInReport, $manualRecords) : null;
        if (in_array($data['presentation'] ?? null, ['payment-request', 'receipt'], true)) {
            abort_unless($selected, 404);
            $sales = $saleEntries->map(fn ($entry) => $allocation['lines'][$allocationService->key($entry)]);

            return $this->voucher($request, $data['presentation'], $selected, $sales, $reportFrom, $reportTo);
        }
        $canManageAllocation = $selected && $snapshot && $request->user()->canManageCustomerDebt()
            && ! $request->attributes->get('own_customer_debt', false);
        $canManageVouchers = $selected && $request->user()->canManageCustomerDebt()
            && ! $request->attributes->get('own_customer_debt', false);
        $voucherRegister = null;
        if (($data['debt_section'] ?? null) === 'vouchers') {
            abort_unless($canManageVouchers, 403);
            $vouchers = app(CustomerDebtVouchers::class);
            $rows = $vouchers->rows($selected);
            $page = (int) ($data['voucher_page'] ?? 1);
            $editing = isset($data['edit_voucher']) ? $rows->get($data['edit_voucher']) : null;
            abort_if(isset($data['edit_voucher']) && ! $editing, 404);
            $latest = $rows->last();
            $editPurchases = $editing && ($editing['by_purchase_days'] ?? !empty($editing['purchase_days']));
            $purchaseDays = $vouchers->purchaseDays($selected, $editPurchases ? $rows->except($editing['id']) : $rows);
            if ($editPurchases) {
                foreach ($editing['purchase_days'] as $date => $amount) {
                    if ($day = $purchaseDays->get($date)) {
                        $purchaseDays->put($date, array_replace($day, ['covered' => false, 'selectable' => true, 'remaining' => $amount]));
                    }
                }
            }
            $purchaseSize = (int) ($data['voucher_purchases_size'] ?? 20);
            $purchasePage = (int) ($data['voucher_purchases_page'] ?? 1);
            $selectedDates = array_filter((array) $request->old('purchase_dates', $data['voucher_selected'] ?? array_keys($editing['purchase_days'] ?? [])), 'is_string');
            if ($editPurchases && $request->old('edit_purchase_days')) {
                $selectedDates = array_filter((array) $request->old('purchase_dates', []), 'is_string');
            }
            $voucherRegister = [
                'rows' => new \Illuminate\Pagination\LengthAwarePaginator(
                    $rows->reverse()->forPage($page, 15)->values(), $rows->count(), 15, $page,
                    ['path' => $request->url(), 'pageName' => 'voucher_page', 'query' => $request->except(['voucher_page', 'edit_voucher', 'new_voucher']), 'fragment' => 'debt-vouchers']
                ),
                'latest' => $latest,
                'suggested' => $vouchers->suggestedAmount($selected, $rows, $today),
                'purchases' => new \Illuminate\Pagination\LengthAwarePaginator(
                    $purchaseDays->forPage($purchasePage, $purchaseSize)->values(), $purchaseDays->count(), $purchaseSize, $purchasePage,
                    ['path' => $request->url(), 'pageName' => 'voucher_purchases_page', 'query' => $request->except('voucher_purchases_page'), 'fragment' => 'debt-voucher-purchases']
                ),
                'editPurchases' => $editPurchases,
                'transferDifferences' => $manualService->transferDifferences($selected),
                'purchaseChoices' => $editing ? [] : $purchaseDays->where('selectable', true)
                    ->map(fn ($day) => array_intersect_key($day, array_flip(['date', 'count', 'remaining'])))->values(),
                'selectedPurchases' => $purchaseDays->only($selectedDates)->where('selectable', true)->pluck('remaining', 'date')->union($editPurchases ? array_intersect_key($editing['purchase_days'], array_flip($selectedDates)) : []),
                'editing' => $editing,
                'revision' => $vouchers->revision($selected, $rows),
            ];
        }
        $allocationRevision = $canManageAllocation ? $manualService->revision($selected, $manualRecords) : null;
        $settlementPreview = $allocationForm = null;
        $missingAllocationKeys = [];
        if ($canManageAllocation && $manualRecords) {
            $receiptKeys = collect($history)->where('type', 'payment')->map(fn ($entry) => $allocationService->key($entry))->flip()->all();
            $missingAllocationKeys = array_keys(array_filter(array_diff_key($manualRecords, $receiptKeys), fn ($record) => ! isset($record['confirmed_group']) && ! isset($record['transfer_moves'])));
        }
        $latestChangeDate = array_key_last($settlementChanges);
        $nextSettlementMethod = $settlementChanges[$latestChangeDate] ?? ($selected ? CustomerDebtPolicyService::basePaymentForm($selected['policy']) : $settlementMethod);
        $paymentPeriods = $selected ? CustomerDebtPolicyService::settlementPeriods($selected['policy'], $history[0]['date'] ?? null) : [];
        $billPeriods = $billGroups = [];
        if ($selected && ($data['debt_section'] ?? null) === 'bill') {
            $billEntries = array_values(array_filter($history, fn ($entry) => $entry['date'] <= $today));
            $billChanges = array_filter($settlementChanges, fn ($date) => $date <= $today, ARRAY_FILTER_USE_KEY);
            $billAllocation = $reportTo === $today ? $allocation
                : $allocationService->reconcile($billEntries, $settlementMethod, $selected['customer_id'], $billChanges, $manualRecords);
            $billPeriods = app(CustomerDebtBillReconciliation::class)->periods($billEntries, $selected['policy'], $billAllocation, $manualRecords);
            $billGroups = $manualService->billGroups($manualRecords)->map(function ($entry) use ($billAllocation) {
                $members = $entry['members'];
                $batch = $entry['batch'];
                $labels = $members->pluck('items')->collapse()->pluck('target')->unique()->map(function ($key) use ($billAllocation) {
                    $line = $billAllocation['lines'][$key] ?? null;

                    return $line && $line['code'] !== '' ? $line['code'].' ('.$line['date'].')' : $key;
                })->unique();

                return $batch + [
                    'amount' => $members->sum(fn ($record) => array_sum(array_column($record['items'], 'amount'))),
                    'recorded_at' => $members->first()['updated_at'] ?? null,
                    'payment_keys' => $members->map(fn ($record, $key) => $record['payment_key'] ?? $key)->values()->all(),
                    'bill_labels' => $labels->values()->all(),
                    'valid' => $members->keys()->every(fn ($key) => isset($billAllocation['confirmedApplied'][$key])),
                ];
            });
        }
        $settlementReturnDate = $selected['policy']['settlement_return_date'] ?? null;
        if ($canManageAllocation && isset($data['transition_preview'])) {
            $date = $data['transition_preview'];
            $method = $data['transition_method'];
            $settlementPreview = ['date' => $date, 'method' => $method, 'end' => $data['transition_end'] ?? null];
            if (($latestChangeDate && $date <= $latestChangeDate && $date !== $settlementReturnDate) || $method === $nextSettlementMethod) {
                $settlementPreview['error'] = 'Chọn hình thức khác và ngày sau mốc chuyển gần nhất.';
            } else {
                $before = array_values(array_filter($history, fn ($entry) => $entry['date'] < $date && $entry['date'] <= $today));
                $changesBefore = array_filter($settlementChanges, fn ($start) => $start < $date && $start <= $today, ARRAY_FILTER_USE_KEY);
                $preview = $allocationService->reconcile($before, $settlementMethod, $selected['customer_id'], $changesBefore, $manualRecords);
                $settlementPreview['remaining'] = array_sum(array_column($preview['lines'], 'remaining'));
                $settlementPreview['pending'] = collect($preview['unmatched'])->where('amount', '>', 0)->sum('amount') + max(0, $preview['credit']);
            }
        }
        if ($canManageAllocation && isset($data['allocate'])) {
            $key = $data['allocate'];
            $entries = array_values(array_filter($history, fn ($entry) => $entry['date'] <= $today));
            $receipt = collect($entries)->first(fn ($entry) => $entry['type'] === 'payment' && $allocationService->key($entry) === $key);
            $record = $manualRecords[$key] ?? null;

            $changesToday = array_filter($settlementChanges, fn ($date) => $date <= $today, ARRAY_FILTER_USE_KEY);
            $baseline = $allocationService->reconcile($entries, $settlementMethod, $selected['customer_id'], $changesToday, array_diff_key($manualRecords, [$key => true]));
            $available = max(0, $baseline['unmatched'][$key]['amount'] ?? 0);
            if (! $confirmedPaymentKeys->has($key) && (($receipt && $available > 0) || $record)) {
                $allocationForm = [
                    'key' => $key, 'receipt' => $receipt, 'record' => $record, 'available' => $available,
                    'targets' => collect($baseline['lines'])->where('remaining', '>', 0)->reverse(),
                    'old_debt_dates' => array_keys($changesToday),
                ];
            }
        }
        $saleEntries = $saleEntries->map(fn ($entry) => $entry + ['allocation' => $allocation['lines'][$allocationService->key($entry)] ?? null]);
        $paymentEntries = $paymentEntries->map(function ($entry) use ($allocation, $allocationService, $manualRecords, $confirmedPaymentKeys) {
            $key = $allocationService->key($entry);

            return $entry + [
                'allocation_key' => $key,
                'allocation_issue' => $allocation['unmatched'][$key] ?? null,
                'allocation_invalid' => $allocation['invalidManual'][$key] ?? null,
                'allocation_automatic' => $allocation['automatic'][$key] ?? [],
                'allocation_manual' => $allocation['manualApplied'][$key] ?? [],
                'allocation_saved' => isset($manualRecords[$key]),
                'allocation_confirmed' => $confirmedPaymentKeys->has($key),
            ];
        });
        $giftEntries = $saleEntries->filter(fn ($entry) => ($entry['gift'] ?? 0) != 0)
            ->map(fn ($entry) => [
                'type' => 'gift', 'date' => $entry['date'], 'row' => $entry['row'], 'amount' => $entry['gift'],
                'source_sheet' => $entry['source_sheet'] ?? 'nhatkybanhang',
                'note' => ($entry['gift_note'] ?? '') ?: ($entry['note'] ?? ''),
            ]);
        $settlementEntries = $paymentEntries->concat($giftEntries)
            ->sort(fn ($a, $b) => strcmp($b['date'], $a['date']) ?: strcmp($a['source_sheet'] ?? 'Dongtienhoatdong', $b['source_sheet'] ?? 'Dongtienhoatdong') ?: ($b['row'] <=> $a['row']) ?: strcmp($b['type'], $a['type']))->values();
        $detailSettlementTotal = $detailPayments + $detailGifts;
        $purchasePage = (int) ($data['purchase_page'] ?? $data['detail_page'] ?? 1);
        $purchasePerPage = (int) ($data['purchase_per_page'] ?? $purchasePageSizes[0]);
        $paymentPage = (int) ($data['payment_page'] ?? 1);
        $purchases = new \Illuminate\Pagination\LengthAwarePaginator($saleEntries->forPage($purchasePage, $purchasePerPage)->values(), $saleEntries->count(), $purchasePerPage, $purchasePage, ['path' => $request->url(), 'pageName' => 'purchase_page', 'query' => $request->except(['purchase_page', 'detail_page']), 'fragment' => 'debt-detail']);
        $payments = new \Illuminate\Pagination\LengthAwarePaginator($settlementEntries->forPage($paymentPage, 100)->values(), $settlementEntries->count(), 100, $paymentPage, ['path' => $request->url(), 'pageName' => 'payment_page', 'query' => $request->except('payment_page'), 'fragment' => 'debt-detail']);
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
        $visible = $rows->filter(function ($g) use ($data, $service, $tab) {
            if ($tab === 'all') {
                $recoveryFilter = $data['recovery'] ?? 'normal';
                $status = $g['recovery']['status'];
                if ($recoveryFilter === 'flagged' ? $status === 'normal' : ($recoveryFilter !== 'all' && $status !== $recoveryFilter)) {
                    return false;
                }
            }
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
        $canCreateQuickCustomer = $canLoad && \Illuminate\Support\Facades\Gate::forUser($request->user())->allows('create', Customer::class);
        $quickCustomerSales = $canCreateQuickCustomer ? $this->quickCustomerSales() : collect();

        if (($data['presentation'] ?? null) === 'customer') {
            abort_unless($selected, 404);
            $statementCustomer = $selected['customer_id'] ? $byId->get($selected['customer_id']) : null;

            return view('customer-debt.customer-statement', compact('selected', 'statementCustomer', 'snapshot', 'reportFrom', 'reportTo', 'opening', 'closing', 'detailSales', 'detailGifts', 'detailPayments', 'detailAdjustments', 'reportLastPurchase', 'reportLastPayment', 'saleEntries', 'paymentEntries'));
        }

        $detailPolicyAction = null;
        $detailPolicyKey = null;
        $reminderHistory = null;
        if ($selected) {
            $customer = $selected['customer_id'] ? $byId->get($selected['customer_id']) : null;
            $source = $groups[$data['customer'] ?? ($selected['source_keys'][0] ?? '')] ?? null;
            $detailPolicyKey = $source['key'] ?? null;
            $actor = $request->user();
            if ($customer && $policyService->canEditCustomer($actor, $customer)) {
                $detailPolicyAction = route('customers.debt-policy.update', $customer);
            } elseif ($canLoad && $source && empty($source['manual_only'])) {
                $detailPolicyAction = route('customer-debt.policy');
            }
            $collections = app(\App\Services\DebtCollectionService::class);
            if ($canLoad && $collections->canRecord($actor)) {
                $reminderHistory = $collections->evaluate($selected, $collections->records());
            }
        }

        $cycleHistory = $cycleDocuments = $cycleArchives = [];
        $cyclePreview = $cycleDraft = null;
        $cycleError = null;
        if ($selected && $canLoad && $request->user()->canManageCustomerDebt()) {
            $cycles = app(CustomerDebtCycleStatementService::class);
            $cycleHistory = $cycles->history($selected);
            if (($data['debt_section'] ?? null) === 'cycles') {
                $ledger = $cycles->ledgerKey($selected);
                $cycleArchives = $cycles->archives($ledger);
                if ($cycleHistory || $cycleArchives) {
                    $cycleDocuments = app(CustomerDebtCycleDocuments::class)->forLedger($ledger);
                }
            }
            $cycleDraft = $cycles->draft($selected);
            if ($snapshot) {
                try {
                    $cycleDate = $cycleDraft['to'] ?? $data['cycle_date'] ?? null;
                    $cycleFrom = $cycleDraft['from'] ?? $data['cycle_from'] ?? null;
                    $cyclePreview = $cycles->preview($selected, $cycleHistory, $cycleDate, $cycleFrom);
                    if ($cycleDraft && $cyclePreview) {
                        $available = $cycles->draftBills($cycleDraft, $cyclePreview);
                        if (empty($cycleDraft['automatic']) && count($available) !== count($cycleDraft['bill_keys'])) {
                            $cycleError = 'Có bill trong bản nháp đã thay đổi hoặc không còn trong nguồn. Vui lòng kiểm tra lại.';
                        }
                        $cyclePreview = $cycles->preview($selected, $cycleHistory, $cycleDate, $cycleFrom, $available, (int) ($cycleDraft['manual_adjustment'] ?? 0), $cycleDraft['adjustment_reason'] ?? null);
                    }
                } catch (\Illuminate\Validation\ValidationException $exception) {
                    $cycleError = $exception->errors()['cycle_date'][0];
                }
            }
        }

        $debtNote = $selected && $canLoad && $request->user()->canManageCustomerDebt()
            ? (string) Setting::get($this->noteKey($selected), '')
            : null;

        return view('customer-debt.index', compact('purchaseReviews', 'orders', 'groups', 'debts', 'snapshot', 'canLoad', 'from', 'to', 'tab', 'selected', 'opening', 'reportFrom', 'reportTo', 'reportMode', 'reportAlerts', 'reportLastPurchase', 'reportLastPayment', 'purchases', 'purchasePageSizes', 'payments', 'detailSales', 'detailGifts', 'detailPayments', 'detailSettlementTotal', 'detailSalesPayments', 'closing', 'issueRows', 'issueTotal', 'customerDebts', 'saleOptions', 'canAdjust', 'balanceAdjustments', 'detailAdjustments', 'connectionEmail', 'canCreateQuickCustomer', 'quickCustomerSales', 'settlementMethod', 'allocation', 'changeDate', 'currentSettlementMethod', 'settlementChanges', 'nextSettlementMethod', 'latestChangeDate', 'paymentPeriods', 'billPeriods', 'billGroups', 'settlementReturnDate', 'canManageAllocation', 'canManageVouchers', 'voucherRegister', 'allocationRevision', 'settlementPreview', 'allocationForm', 'missingAllocationKeys', 'detailPolicyAction', 'detailPolicyKey', 'reminderHistory', 'debtNote', 'cycleHistory', 'cycleDocuments', 'cycleArchives', 'cyclePreview', 'cycleDraft', 'cycleError'));
    }

    public function changeSettlementMethod(Request $request, CustomerDebtSheetService $sheets, CustomerDebtManualAllocationService $manual)
    {
        abort_unless($request->user()->canManageCustomerDebt(), 403);
        $data = $request->validate($this->detailCustomerRules() + ['transition_date' => 'required|date_format:Y-m-d', 'transition_method' => 'required|in:batch,bill,mixed,cycle', 'transition_end' => 'nullable|date_format:Y-m-d|after_or_equal:transition_date']);
        Cache::lock('customer-debt-load', 60)->block(5, function () use ($request, $sheets, $manual, $data) {
            $customer = $this->allocationCustomer($request, $sheets, $data);
            if (! hash_equals($manual->revision($customer, $manual->records($customer)), $data['revision'])) {
                throw \Illuminate\Validation\ValidationException::withMessages(['transition_date' => 'Dữ liệu đã thay đổi. Vui lòng xem trước lại kỳ chuyển.']);
            }
            app(CustomerDebtPolicyService::class)->changeSettlementMethod($customer, $data['transition_date'], $data['transition_method'], $customer['policy']['edit_version'], $request->user(), $data['transition_end'] ?? null);
        });

        return redirect()->to(route('customer-debt.index', $this->allocationDetailQuery($data)).'#debt-detail')->with('success', 'Đã lưu ngày chuyển hình thức thanh toán.');
    }

    public function saveManualAllocation(Request $request, CustomerDebtSheetService $sheets, CustomerDebtManualAllocationService $manual, CustomerDebtPaymentAllocation $allocation)
    {
        abort_unless($request->user()->canManageCustomerDebt(), 403);
        $request->merge(['items' => array_values(array_filter((array) $request->input('items', []), static fn ($item) =>
            ! is_array($item) || ($item['target'] ?? null) !== null || ($item['amount'] ?? null) !== null))]);
        $data = $request->validate($this->detailCustomerRules() + [
            'payment_key' => 'required|string|max:160',
            'debt_section' => 'nullable|in:bill',
            'cancel' => 'nullable|boolean',
            'items' => 'exclude_if:cancel,1|required|array|min:1|max:100',
            'items.*.target' => 'required|string|max:160|distinct',
            'items.*.amount' => 'required|integer|min:1|max:999999999999999',
        ]);
        Cache::lock('customer-debt-load', 60)->block(5, function () use ($request, $sheets, $manual, $allocation, $data) {
            $customer = $this->allocationCustomer($request, $sheets, $data);
            $policies = app(CustomerDebtPolicyService::class);
            $policies->withWriteLock($customer, function () use ($policies, $customer, $manual, $data, $request, $allocation) {
                $customer['policy'] = $policies->effective($customer, $policies->policies());
                DB::transaction(fn () => $manual->save($customer, $data, $request->user(), $allocation));
            });
        });

        $query = $this->allocationDetailQuery($data) + ['debt_section' => $data['debt_section'] ?? null];
        $anchor = isset($data['debt_section']) ? 'debt-payment-periods' : 'debt-detail';

        return redirect()->to(route('customer-debt.index', $query).'#'.$anchor)->with('success',
            ! empty($data['cancel']) ? 'Đã hủy ghép khoản thu.' : 'Đã lưu ghép khoản thu.');
    }

    public function classifyVoucherTransferDifference(Request $request, string $difference, CustomerDebtSheetService $sheets, CustomerDebtManualAllocationService $manual, CustomerDebtPaymentAllocation $allocation)
    {
        abort_unless($request->user()->canManageCustomerDebt(), 403);
        $data = $request->validate(array_diff_key($this->detailCustomerRules(), ['revision' => true]) + [
            'handling' => ['required', Rule::in(array_keys(CustomerDebtManualAllocationService::TRANSFER_DIFFERENCE_TYPES))],
            'bill_key' => 'nullable|required_if:handling,bill|string|max:160',
            'note' => 'nullable|string|max:500',
        ]);
        Cache::lock('customer-debt-load', 60)->block(5, function () use ($request, $difference, $sheets, $manual, $allocation, $data) {
            $customer = $this->allocationCustomer($request, $sheets, $data);
            app(CustomerDebtPolicyService::class)->withWriteLock($customer, fn () =>
                $manual->classifyTransferDifference($customer, $difference, $data, $request->user(), $allocation));
        });

        return redirect()->to(route('customer-debt.index', $this->allocationDetailQuery($data) + [
            'debt_section' => 'vouchers',
        ]).'#debt-voucher-differences')->with('success', 'Đã ghi nhận cách xử lý chênh lệch.');
    }

    public function transferVoucherPayment(Request $request, CustomerDebtSheetService $sheets, CustomerDebtVouchers $vouchers, CustomerDebtManualAllocationService $manual, CustomerDebtPaymentAllocation $allocation)
    {
        abort_unless($request->user()->canManageCustomerDebt(), 403);
        $data = $request->validate($this->detailCustomerRules() + [
            'paid_date' => 'required|date_format:Y-m-d',
            'open_date' => 'required|date_format:Y-m-d|different:paid_date',
        ]);
        Cache::lock('customer-debt-load', 60)->block(5, function () use ($request, $sheets, $vouchers, $manual, $allocation, $data) {
            $customer = $this->allocationCustomer($request, $sheets, $data);
            $policies = app(CustomerDebtPolicyService::class);
            $policies->withWriteLock($customer, function () use ($customer, $policies, $vouchers, $manual, $allocation, $data, $request) {
                $customer['policy'] = $policies->effective($customer, $policies->policies());
                $rows = $vouchers->rows($customer);
                $days = $vouchers->purchaseDays($customer, $rows);
                if (! hash_equals($vouchers->revision($customer, $rows), $data['revision'])
                    || ($days[$data['paid_date']]['covered'] ?? true)
                    || ! ($days[$data['open_date']]['selectable'] ?? false)) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['payment_transfer' => 'Dữ liệu đã thay đổi hoặc ngày mua đã vào phiếu. Vui lòng kiểm tra lại.']);
                }
                DB::transaction(fn () => $manual->transferAutomaticPayment($customer, $data, $request->user(), $allocation));
            });
        });

        return redirect()->to(route('customer-debt.index', $this->allocationDetailQuery($data) + [
            'debt_section' => 'vouchers', 'voucher_selected' => [$data['paid_date']], 'new_voucher' => 'payment-request',
        ]).'#debt-vouchers')->with('success', 'Đã chuyển khoản phân bổ. Ngày mua còn phải thu đã được chọn vào đề nghị thanh toán.');
    }

    public function saveVoucher(Request $request, CustomerDebtSheetService $sheets, CustomerDebtVouchers $vouchers)
    {
        abort_unless($request->user()->canManageCustomerDebt(), 403);
        $data = $request->validate($this->detailCustomerRules() + [
            'voucher_id' => 'nullable|uuid',
            'type' => 'required_without:voucher_id|in:receipt,payment-request',
            'through' => [
                Rule::excludeIf($request->filled('purchase_dates')),
                Rule::requiredIf(! $request->filled('voucher_id') && ! $request->filled('purchase_dates')),
                'nullable', 'date_format:Y-m-d', 'before_or_equal:today',
            ],
            'number' => 'nullable|string|max:50',
            'new_amount' => 'nullable|required_with:voucher_id|integer|min:0|max:999999999999999',
            'edit_purchase_days' => 'nullable|boolean',
            'purchase_dates' => ['nullable', 'array', 'list', 'max:100', Rule::prohibitedIf($request->filled('voucher_id') && ! $request->boolean('edit_purchase_days'))],
            'purchase_dates.*' => 'required|date_format:Y-m-d|distinct',
            'paid' => 'required|integer|min:0|max:999999999999999',
            'method' => 'nullable|in:cash,transfer',
            'note' => 'nullable|string|max:1500',
            'documents' => 'nullable|array|list|max:10',
            'documents.*' => 'required|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
        ]);
        $voucher = Cache::lock('customer-debt-load', 60)->block(5, function () use ($request, $sheets, $vouchers, $data) {
            $customer = $this->detailCustomer($this->debtContext($request, $sheets), $data);

            return app(CustomerDebtPolicyService::class)->withWriteLock($customer, fn () =>
                $vouchers->save($customer, $data, $request->user()->id));
        });

        return redirect()->to(route('customer-debt.index', $this->allocationDetailQuery($data) + [
            'debt_section' => 'vouchers', 'edit_voucher' => $voucher['id'],
        ]).'#debt-vouchers')->with('success', 'Đã lưu phiếu '.$voucher['number'].'.');
    }

    public function voidVoucher(Request $request, string $voucher, CustomerDebtSheetService $sheets, CustomerDebtVouchers $vouchers)
    {
        abort_unless($request->user()->canManageCustomerDebt(), 403);
        $data = $request->validate($this->detailCustomerRules());
        $voided = Cache::lock('customer-debt-load', 60)->block(5, function () use ($request, $sheets, $vouchers, $data, $voucher) {
            $customer = $this->detailCustomer($this->debtContext($request, $sheets), $data);

            return app(CustomerDebtPolicyService::class)->withWriteLock($customer, fn () =>
                $vouchers->void($customer, $voucher, $data['revision'], $request->user()->id));
        });

        return redirect()->to(route('customer-debt.index', $this->allocationDetailQuery($data) + [
            'debt_section' => 'vouchers', 'new_voucher' => $voided['type'],
            'voucher_selected' => array_keys($voided['purchase_days'] ?? []),
        ]).'#debt-voucher-editor')->with('success', 'Đã gỡ phiếu '.$voided['number'].'.');
    }

    public function paymentRequests(Request $request, CustomerDebtSheetService $sheets, CustomerDebtVouchers $vouchers)
    {
        $filters = $request->validate([
            'period' => 'nullable|in:all,yesterday,last3',
            'from' => 'nullable|date_format:Y-m-d',
            'to' => ['nullable', 'date_format:Y-m-d', Rule::when($request->filled('from'), 'after_or_equal:from')],
            'q' => 'nullable|string|max:150',
        ]);
        $timezone = config('app.display_timezone') ?: config('app.timezone');
        $today = today($timezone);
        $period = $filters['period'] ?? 'all';
        [$from, $to] = match ($period) {
            'yesterday' => array_fill(0, 2, $today->copy()->subDay()->toDateString()),
            'last3' => [$today->copy()->subDays(2)->toDateString(), $today->toDateString()],
            default => [$filters['from'] ?? null, $filters['to'] ?? null],
        };
        $search = trim($filters['q'] ?? '');
        $searchTerm = \Illuminate\Support\Str::ascii($sheets->customerName($search));
        $context = $this->resolveGroups($request, $sheets, voucherRead: true);
        $customers = $context['customers']->map(fn ($customer) => ['customer_id' => $customer->id, 'name' => $customer->name])
            ->concat(collect($context['groups'])->map(fn ($group) => array_replace($group, ['customer_id' => null])));
        $matches = $vouchers->paymentRequests($customers)->map(fn ($row) => $row + [
            'issued_date' => \Carbon\Carbon::parse($row['created_at'])->timezone($timezone)->toDateString(),
        ])->filter(fn ($row) => (! $from || $row['issued_date'] >= $from)
            && (! $to || $row['issued_date'] <= $to)
            && ($searchTerm === '' || str_contains(\Illuminate\Support\Str::ascii($sheets->customerName($row['customer_name'])), $searchTerm)))
            ->sortByDesc(fn ($row) => [$row['issued_date'], $row['created_at'], $row['id']])->values();
        $page = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
        $rows = new \Illuminate\Pagination\LengthAwarePaginator(
            $matches->forPage($page, 25), $matches->count(), 25, $page, ['path' => $request->url(), 'query' => $filters]
        );

        return view('customer-debt.payment-requests', compact('rows', 'period', 'from', 'to', 'search'));
    }

    public function printVoucher(Request $request, string $voucher, CustomerDebtSheetService $sheets, CustomerDebtVouchers $vouchers)
    {
        [$selected, $row, $query] = $this->storedVoucher($request, $voucher, $sheets, $vouchers, printing: true);
        $collectionReceipt = $request->routeIs('customer-debt.vouchers.print-receipt');
        $paymentConfirmation = $request->routeIs('customer-debt.vouchers.print-payment-confirmation');
        if ($paymentConfirmation) {
            abort_unless($request->user()->canManageCustomerDebt(), 403);
            abort_unless($row['paid'] > 0, 404);
        }
        if ($collectionReceipt || $paymentConfirmation) {
            abort_unless($row['type'] === 'payment-request', 404);
            $row['request_number'] = $row['number'];
            $row['number'] = $row['collection_receipt_number'] ?? 'PT-'.$row['number'];
            $row['type'] = 'receipt';
        }
        $company = Setting::get('company_legal_name', 'CÔNG TY CỔ PHẦN THỰC PHẨM HOÀNG LONG TNT');

        $customer = isset($selected['customer_id'])
            ? Customer::query()->find($selected['customer_id'], ['id', 'address', 'phone'])
            : null;
        $sourceEntries = collect($selected['entries'])->sortByDesc('date');
        $address = trim((string) $customer?->address)
            ?: trim((string) $customer?->addresses()->where('is_default', true)->value('note'))
            ?: trim((string) ($selected['address'] ?? $sourceEntries->first(fn ($entry) => filled($entry['address'] ?? null))['address'] ?? ''));
        $phone = trim((string) $customer?->phone)
            ?: trim((string) ($selected['phone'] ?? $sourceEntries->first(fn ($entry) => filled($entry['phone'] ?? null))['phone'] ?? ''));

        $creatorId = $paymentConfirmation ? ($row['payment_recorded_by'] ?? $row['updated_by'] ?? $row['created_by']) : ($row['created_by'] ?? null);
        $creator = User::query()->find($creatorId, ['name', 'short_name']);
        $creatorName = trim((string) $creator?->short_name) ?: $creator?->name;

        return response()->view('customer-debt.voucher-print', compact('selected', 'row', 'query', 'company', 'creatorName', 'address', 'phone', 'collectionReceipt', 'paymentConfirmation'))->header('Cache-Control', 'private, no-store');
    }

    public function downloadVoucherDocument(Request $request, string $voucher, string $document, CustomerDebtSheetService $sheets, CustomerDebtVouchers $vouchers)
    {
        [, $row] = $this->storedVoucher($request, $voucher, $sheets, $vouchers);
        $file = collect($row['documents'])->firstWhere('id', $document);
        $disk = \Illuminate\Support\Facades\Storage::disk('local');
        abort_unless($file && $disk->exists($file['path']), 404);

        return $disk->download($file['path'], $file['name'], ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    private function storedVoucher(Request $request, string $id, CustomerDebtSheetService $sheets, CustomerDebtVouchers $vouchers, bool $printing = false): array
    {
        $canManage = $request->user()->canManageCustomerDebt();
        abort_unless($canManage || ($printing && $request->user()->hasRole('warehouse')), 403);
        $query = $request->validate(array_diff_key($this->detailCustomerRules(), ['revision' => true]));
        if ($printing) {
            $context = $this->resolveGroups($request, $sheets, voucherRead: true);
            if (isset($query['system_customer'])) {
                $system = $context['customers']->firstWhere('id', (int) $query['system_customer']);
                abort_unless($system, 404);
                $entries = collect($context['groups'])->where('customer_id', $system->id)->pluck('entries')->collapse()->all();
                $customer = ['customer_id' => $system->id, 'name' => $system->name, 'entries' => $entries];
            } else {
                $source = $context['groups'][$query['customer']] ?? null;
                abort_unless($source, 404);
                $customer = array_replace($source, ['customer_id' => null]);
            }
        } else {
            $customer = $this->detailCustomer($this->debtContext($request, $sheets), $query);
        }
        $voucher = $vouchers->rows($customer)->get($id);
        abort_unless($voucher && ($canManage || $voucher['type'] === 'payment-request'), 404);

        return [$customer, $voucher, $this->allocationDetailQuery($query)];
    }

    public function confirmBillAllocations(Request $request, CustomerDebtSheetService $sheets, CustomerDebtManualAllocationService $manual, CustomerDebtBillReconciliation $bills, CustomerDebtPaymentAllocation $allocation)
    {
        abort_unless($request->user()->canManageCustomerDebt(), 403);
        $data = $request->validate($this->detailCustomerRules() + [
            'cancel_group' => 'nullable|uuid',
            'period_from' => 'required_without:cancel_group|date_format:Y-m-d',
            'match_exact' => 'nullable|boolean',
            'bill_keys' => 'exclude_if:match_exact,1|required_without:cancel_group|array|min:1|max:100',
            'bill_keys.*' => 'required|string|max:160|distinct',
            'payment_keys' => 'exclude_if:match_exact,1|required_without:cancel_group|array|min:1|max:100',
            'payment_keys.*' => 'required|string|max:160|distinct',
        ]);
        $reopen = Cache::lock('customer-debt-load', 60)->block(5, function () use ($request, $sheets, $manual, $bills, $allocation, $data) {
            $customer = $this->allocationCustomer($request, $sheets, $data);
            $policies = app(CustomerDebtPolicyService::class);
            return $policies->withWriteLock($customer, function () use ($policies, $customer, $manual, $data, $request, $bills, $allocation) {
                $customer['policy'] = $policies->effective($customer, $policies->policies());
                return DB::transaction(fn () => $manual->confirmBills($customer, $data, $request->user(), $bills, $allocation));
            });
        });

        return redirect()->to(route('customer-debt.index', $this->allocationDetailQuery($data) + ['debt_section' => 'bill']).($reopen ? '#debt-bill-reopened' : '#debt-payment-periods'))
            ->with('bill_reopened', $reopen)
            ->with('success', isset($data['cancel_group']) ? 'Đã gỡ lần ghép Bill.' : 'Đã lưu phân bổ Bill. Phần thiếu hoặc dư được giữ để ghép tiếp.');
    }

    public function autoCycle(Request $request, CustomerDebtSheetService $sheets, CustomerDebtCycleStatementService $cycles)
    {
        abort_unless($request->user()->canManageCustomerDebt(), 403);
        $data = $request->validate([
            'system_customer' => 'nullable|required_without:customer|integer|min:1',
            'customer' => 'nullable|required_without:system_customer|string|size:64',
        ]);
        Cache::lock('customer-debt-load', 60)->block(5, function () use ($request, $sheets, $cycles, $data) {
            $customer = $this->allocationCustomer($request, $sheets, $data);
            $policies = app(CustomerDebtPolicyService::class);
            $policies->withWriteLock($customer, function () use ($customer, $policies, $cycles) {
                if ($cycles->draft($customer)) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['cycle_date' => 'Khách đã có kỳ nháp. Vui lòng hoàn tất kỳ đó trước.']);
                }
                $customer['policy'] = $policies->effective($customer, $policies->policies());
                $cycles->autoDraft($customer);
            });
        });

        return redirect()->to(route('customer-debt.index', $this->allocationDetailQuery($data) + ['debt_section' => 'cycles']).'#debt-cycles')
            ->with('success', 'Đã tạo kỳ nháp theo lịch và gom bill thuộc kỳ.');
    }

    public function closeCycle(Request $request, CustomerDebtSheetService $sheets, CustomerDebtCycleStatementService $cycles)
    {
        abort_unless($request->user()->canManageCustomerDebt(), 403);
        $data = $request->validate($this->detailCustomerRules() + [
            'cycle_date' => 'required|date_format:Y-m-d',
            'cycle_from' => 'nullable|date_format:Y-m-d',
            'cycle_bills' => 'nullable|json',
            'cycle_action' => 'nullable|in:create,save,close,discard',
            'cycle_name' => 'nullable|required_if:cycle_action,create,save,close|string|max:150',
            'draft_version' => 'nullable|uuid',
            'cycle_adjustment' => 'nullable|integer|between:-999999999999999,999999999999999',
            'cycle_adjustment_reason' => 'nullable|string|max:500',
        ]);
        $bills = isset($data['cycle_bills']) ? json_decode($data['cycle_bills'], true, flags: JSON_THROW_ON_ERROR) : null;
        if (isset($data['cycle_bills'])) {
            Validator::make(['bills' => $bills], [
                'bills' => 'present|array|list', 'bills.*' => 'required|string|regex:/^[a-f0-9]{64}$/|distinct',
            ])->validate();
        }
        $action = $data['cycle_action'] ?? 'close';
        $data['cycle_adjustment'] = $action === 'discard' ? 0 : (int) ($data['cycle_adjustment'] ?? 0);
        $data['cycle_adjustment_reason'] = $data['cycle_adjustment'] === 0 ? null : trim($data['cycle_adjustment_reason'] ?? '');
        if ($data['cycle_adjustment'] !== 0 && $data['cycle_adjustment_reason'] === '') {
            throw \Illuminate\Validation\ValidationException::withMessages(['cycle_adjustment_reason' => 'Vui lòng ghi lý do điều chỉnh công nợ.']);
        }
        $statement = Cache::lock('customer-debt-load', 60)->block(5, function () use ($request, $sheets, $cycles, $data, $bills, $action) {
            $customer = $this->allocationCustomer($request, $sheets, $data);
            $policies = app(CustomerDebtPolicyService::class);

            return $policies->withWriteLock($customer, function () use ($customer, $policies, $cycles, $data, $request, $bills, $action) {
                $customer['policy'] = $policies->effective($customer, $policies->policies());

                return DB::transaction(function () use ($customer, $cycles, $data, $request, $bills, $action) {
                    $draft = $cycles->draft($customer);
                    if (($draft['version'] ?? null) !== ($data['draft_version'] ?? null)
                        || ($action === 'create' && $draft) || (in_array($action, ['save', 'discard'], true) && ! $draft)
                        || ($draft && ($draft['from'] !== ($data['cycle_from'] ?? null) || $draft['to'] !== $data['cycle_date']))) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['cycle_date' => 'Bản nháp đã thay đổi. Vui lòng mở lại kỳ để tiếp tục.']);
                    }
                    if ($action === 'discard') {
                        $cycles->clearDraft($customer);

                        return null;
                    }
                    if ($draft && $bills === null) {
                        $bills = $draft['bill_keys'];
                    }
                    if ($action !== 'close') {
                        $cycles->saveDraft($customer, $data, $bills, $draft);

                        return null;
                    }
                    $statement = $cycles->close($customer, $data['cycle_date'], $data['revision'], $request->user()->id, $data['cycle_from'] ?? null, $bills, $data['cycle_name'] ?? null, $data['cycle_adjustment'], $data['cycle_adjustment_reason']);
                    if ($draft) {
                        $cycles->clearDraft($customer);
                    }

                    return $statement;
                });
            });
        });

        if ($statement === null) {
            return redirect()->to(route('customer-debt.index', $this->allocationDetailQuery($data) + ['debt_section' => 'cycles']).'#debt-cycles')
                ->with('success', match ($action) {
                    'create' => 'Đã tạo kỳ công nợ nháp.',
                    'discard' => 'Đã bỏ kỳ nháp.',
                    default => 'Đã lưu các bill của kỳ.',
                });
        }

        return redirect()->route('customer-debt.cycles.print', ['ledger' => $statement['ledger'], 'date' => $statement['to'], 'version' => $statement['statement_id'] ?? null], 303);
    }

    public function reopenCycle(Request $request, CustomerDebtSheetService $sheets, CustomerDebtCycleStatementService $cycles)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $data = $request->validate([
            'system_customer' => 'nullable|required_without:customer|integer|min:1',
            'customer' => 'nullable|required_without:system_customer|string|size:64',
            'cycle_date' => 'required|date_format:Y-m-d',
            'statement_number' => 'required|string|max:100',
            'reason' => 'required|string|max:500',
        ]);
        Cache::lock('customer-debt-load', 60)->block(5, function () use ($request, $sheets, $cycles, $data) {
            $customer = $this->detailCustomer($this->debtContext($request, $sheets), $data);
            app(CustomerDebtPolicyService::class)->withWriteLock($customer, fn () =>
                $cycles->reopen($customer, $data['cycle_date'], $data['statement_number'], $data['reason'], $request->user()->id));
        });

        return redirect()->to(route('customer-debt.index', $this->allocationDetailQuery($data) + ['debt_section' => 'cycles']).'#debt-cycles')
            ->with('success', 'Đã gỡ chốt và mở lại kỳ nháp. Biên bản cũ được giữ trong lịch sử gỡ chốt.');
    }

    public function uploadCycleDocuments(Request $request, string $ledger, string $date, CustomerDebtCycleStatementService $cycles, CustomerDebtCycleDocuments $documents)
    {
        abort_unless($request->user()->canManageCustomerDebt(), 403);
        $data = $request->validate([
            'statement_number' => 'nullable|string|max:100',
            'documents' => 'required|array|list|min:1|max:10',
            'documents.*' => 'required|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
        ]);
        Cache::lock('customer-debt-load', 60)->block(5, function () use ($request, $ledger, $date, $cycles, $documents, $data) {
            $statement = $cycles->statement($ledger, $date);
            abort_unless($statement, 404);
            abort_if(isset($data['statement_number']) && $data['statement_number'] !== $statement['number'], 409, 'Kỳ đã thay đổi. Vui lòng tải lại trang.');
            $documents->attach($ledger, $date, $data['documents'], $request->user()->id, $statement['statement_id'] ?? null);
        });

        return back()->with('success', 'Đã lưu chứng từ cho kỳ đã chốt.')->with('cycle_documents_date', $date);
    }

    public function downloadCycleDocument(Request $request, string $ledger, string $date, string $document, CustomerDebtCycleDocuments $documents)
    {
        abort_unless($request->user()->canManageCustomerDebt(), 403);
        $file = $documents->find($ledger, $date, $document);
        $disk = \Illuminate\Support\Facades\Storage::disk('local');
        abort_unless($file && $disk->exists($file['path']), 404);

        return $disk->download($file['path'], $file['name'], ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function printCycle(Request $request, string $ledger, string $date, CustomerDebtCycleStatementService $cycles)
    {
        abort_unless($request->user()->canManageCustomerDebt(), 403);
        $data = $request->validate(['version' => 'nullable|uuid', 'archive' => 'nullable|uuid']);
        $statement = $cycles->printable($ledger, $date, $data['version'] ?? null, $data['archive'] ?? null);
        abort_unless($statement, 404);

        return response()->view('customer-debt.cycle-statement', compact('statement'))->header('Cache-Control', 'private, no-store');
    }

    public function saveNote(Request $request, CustomerDebtSheetService $sheets)
    {
        abort_unless($request->user()->canManageCustomerDebt(), 403);
        $data = $request->validate($this->detailCustomerRules() + ['note' => 'nullable|string|max:4000']);
        Cache::lock('customer-debt-load', 60)->block(5, function () use ($request, $sheets, $data) {
            $detail = $this->detailCustomer($this->debtContext($request, $sheets), $data);
            $key = $this->noteKey($detail);
            if (! hash_equals(hash('sha256', (string) Setting::get($key, '')), $data['revision'])) {
                throw \Illuminate\Validation\ValidationException::withMessages(['revision' => 'Ghi chú đã được cập nhật ở nơi khác. Vui lòng kiểm tra nội dung mới trước khi lưu lại.']);
            }

            Setting::set($key, trim($data['note'] ?? ''));
        });

        return back()->with('success', 'Đã lưu ghi chú công nợ.');
    }

    private function noteKey(array $customer): string
    {
        if ($customer['customer_id']) {
            return 'customer_debt_note_customer_'.$customer['customer_id'];
        }

        $source = config('services.google_sheets.debt_identity_spreadsheet_id', config('services.google_sheets.debt_spreadsheet_id'));

        return 'customer_debt_note_source_'.hash('sha256', $source.'|'.$customer['key']);
    }

    private function detailCustomerRules(): array
    {
        return [
            'system_customer' => 'nullable|required_without:customer|integer|min:1',
            'customer' => 'nullable|required_without:system_customer|string|size:64',
            'revision' => 'required|string|size:64',
        ];
    }

    private function allocationCustomer(Request $request, CustomerDebtSheetService $sheets, array $data): array
    {
        $context = $this->debtContext($request, $sheets);
        abort_unless($context['snapshot'], 422, 'Cần cập nhật dữ liệu Google Sheet.');

        return $this->detailCustomer($context, $data);
    }

    private function detailCustomer(array $context, array $data): array
    {
        if (isset($data['system_customer'])) {
            $customer = $context['customerDebts'][(int) $data['system_customer']] ?? null;
        } else {
            $source = $context['groups'][$data['customer']] ?? null;
            $customer = $context['customerDebts'][$source['customer_id'] ?? 0] ?? $source;
            abort_if(! empty($source['customer_id']), 409, 'Liên kết khách đã thay đổi. Vui lòng mở lại trang đối chiếu.');
        }
        abort_unless($customer, 404);

        return $customer;
    }

    private function allocationDetailQuery(array $data): array
    {
        return ['tab' => 'all', 'window' => 'all', 'recovery' => 'all',
            'system_customer' => $data['system_customer'] ?? null, 'customer' => $data['customer'] ?? null,
        ];
    }

    private function voucher(Request $request, string $presentation, array $selected, \Illuminate\Support\Collection $sales, string $reportFrom, string $reportTo)
    {
        $data = $request->validate([
            'voucher_all' => 'nullable|boolean',
            'voucher_dates' => 'nullable|array|max:100',
            'voucher_dates.*' => 'required|date_format:Y-m-d|distinct',
            'voucher_number' => 'nullable|string|max:50',
            'voucher_method' => 'nullable|in:cash,transfer',
        ]);
        $salesByDate = $sales->groupBy('date');
        $deliveryTotals = $salesByDate->map(fn ($rows) => $rows->sum('amount'));
        $deliveries = $salesByDate->map(fn ($rows) => $rows->sum('remaining'))
            ->filter(fn ($amount) => $amount > 0)->sortKeys();
        $dates = $data['voucher_dates'] ?? [];
        if (array_diff($dates, $salesByDate->keys()->all())) {
            throw \Illuminate\Validation\ValidationException::withMessages(['voucher_dates' => 'Ngày giao hàng đã thay đổi hoặc nằm ngoài kỳ đối chiếu.']);
        }

        $lines = ! empty($data['voucher_all']) ? $deliveries : $deliveries->only($dates);
        $total = $lines->sum();
        $issuedDate = now(config('app.display_timezone'))->format('d/m/Y');
        $receipt = $presentation === 'receipt';
        $title = $receipt ? 'Phiếu thu' : 'Phiếu đề nghị thanh toán';
        $company = \App\Models\Setting::get('company_legal_name', 'CÔNG TY CỔ PHẦN THỰC PHẨM HOÀNG LONG TNT');
        $number = $data['voucher_number'] ?? '';
        $method = ['cash' => 'Tiền mặt', 'transfer' => 'Chuyển khoản'][$data['voucher_method'] ?? ''] ?? '';

        return view('customer-debt.voucher', compact('presentation', 'selected', 'deliveries', 'deliveryTotals', 'lines', 'total', 'issuedDate', 'receipt', 'title', 'company', 'number', 'method', 'reportFrom', 'reportTo'));
    }

    private function quickCustomerSales()
    {
        return User::query()->whereHas('roles', fn ($q) => $q->whereIn('name', ['sale', 'leader', 'leader_sale', 'sale_manager', 'manager', 'manager_sale']))->orderBy('name')->get(['id', 'name', 'short_name']);
    }

    public function createCustomer(Request $request, CustomerDebtSheetService $service, \App\Services\CustomerPriorityService $priorities)
    {
        abort_unless($request->user()->hasRole(['admin', 'account', 'accountant', 'accounting', 'director', 'ceo']), 403);
        abort_unless(\Illuminate\Support\Facades\Gate::forUser($request->user())->allows('create', Customer::class), 403);
        $request->merge([
            'name' => trim((string) $request->input('name')),
            'customer_code' => mb_strtoupper(trim((string) $request->input('customer_code'))),
            'phone' => trim((string) $request->input('phone')),
        ]);
        $data = $request->validate([
            'source_key' => 'required|string|size:64|regex:/^[a-f0-9]{64}$/',
            'name' => 'required|string|max:255', 'customer_code' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30', 'address' => 'nullable|string|max:255',
            'delivery_time' => 'required|date_format:H:i',
            'sale_id' => ['nullable', 'integer', Rule::in($this->quickCustomerSales()->pluck('id')->all())],
        ]);
        \Illuminate\Support\Facades\Cache::lock('customer-debt-load', 60)->block(5, function () use ($request, $service, $priorities, $data) {
            \Illuminate\Support\Facades\DB::transaction(function () use ($request, $service, $priorities, $data) {
                $context = $this->resolveGroups($request, $service);
                $group = $context['groups'][$data['source_key']] ?? null;
                abort_unless($group, 404);
                if ($group['customer_id']) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['source_key' => 'Khách nguồn đã được ghép. Vui lòng tải lại danh sách.']);
                }
                $duplicate = Customer::withTrashed()->where(function ($q) use ($data) {
                    $q->where('name_normalized', Customer::normalizeName($data['name']));
                    if (! empty($data['customer_code'])) {
                        $q->orWhereRaw('UPPER(TRIM(customer_code)) = ?', [$data['customer_code']]);
                    }
                    if (! empty($data['phone'])) {
                        $q->orWhere('phone', $data['phone']);
                    }
                })->first();
                if ($duplicate) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['name' => 'Khách đã tồn tại: '.$duplicate->name.($duplicate->trashed() ? '. Khách đã bị xóa, cần khôi phục trước khi ghép.' : '. Hãy dùng “Chọn khách để ghép”.')]);
                }
                $saleId = ! empty($data['sale_id']) ? (int) $data['sale_id'] : null;
                $customer = Customer::create([
                    'name' => $data['name'], 'customer_code' => $data['customer_code'] ?: null,
                    'phone' => $data['phone'] ?: null, 'address' => $data['address'] ?? null,
                    'delivery_time' => $data['delivery_time'], 'user_id' => $request->user()->id,
                    'assigned_to' => $saleId, 'current_owner_sale_id' => $saleId,
                    'assigned_at' => $saleId ? now() : null, 'customer_status' => $saleId ? 'active' : 'free',
                    'free_from_date' => $saleId ? null : now(), 'current_cycle_no' => 1,
                ]);
                if (! empty($data['address'])) {
                    $customer->addresses()->create(['note' => $data['address'], 'is_default' => 1]);
                }
                if ($saleId) {
                    $priorities->attachSale($customer, $saleId, 1, 'created');
                } else {
                    $priorities->ensureLifecycle($customer);
                }
                $service->mapCustomerWithinLock($data['source_key'], $customer->id, $request->user()->id);
                app(CustomerDebtPolicyService::class)->promoteSource($data['source_key'], $customer->id);
            });
        });
        $request->session()->flash('success', 'Đã tạo khách hàng và ghép dữ liệu công nợ.');

        return response()->json(['message' => 'Đã tạo khách hàng và ghép dữ liệu công nợ.']);
    }

    public function updateRecovery(Request $request, CustomerDebtSheetService $service, \App\Services\CustomerDebtRecoveryService $recovery)
    {
        $data = $request->validate([
            'source_key' => 'required|string|size:64|regex:/^[a-f0-9]{64}$/',
            'recovery_status' => ['required', Rule::in(array_keys(\App\Services\CustomerDebtRecoveryService::LABELS))],
            'recovery_note' => 'nullable|string|max:1000',
            'recovery_version' => 'required|string|size:64',
        ]);
        \Illuminate\Support\Facades\Cache::lock('customer-debt-load', 60)->block(5, function () use ($request, $service, $recovery, $data) {
            $context = $this->debtContext($request, $service);
            $group = $context['groups'][$data['source_key']] ?? null;
            abort_unless($group, 404);
            $target = $group['customer_id'] ? $context['customerDebts'][$group['customer_id']] : $group;
            $recovery->save($target, $data, $request->user());
        });

        return back()->with('success', 'Đã cập nhật trạng thái thu hồi. Danh sách mặc định ẩn khách khó thu hồi và không có khả năng thu hồi.');
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

    public function debtContext(Request $request, CustomerDebtSheetService $service): array
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
            $customerDebts[$id]['purchase_reviews'] = array_merge($customerDebts[$id]['purchase_reviews'] ?? [], $group['purchase_reviews'] ?? []);
            $customerDebts[$id]['entries'] = array_merge($customerDebts[$id]['entries'], $group['entries']);
        }
        $recoveryService = app(\App\Services\CustomerDebtRecoveryService::class);
        $recoveryRecords = $recoveryService->records();
        foreach ($customerDebts as &$debt) {
            $debt['recovery'] = $recoveryService->effective($debt, $recoveryRecords);
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
            $group['recovery'] = $aggregate['recovery'] ?? $recoveryService->effective($group, $recoveryRecords);
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

    private function resolveGroups(Request $request, CustomerDebtSheetService $service, bool $voucherRead = false): array
    {
        $canLoad = ! $request->attributes->get('own_customer_debt', false)
            && ($request->user()->hasRole(['admin', 'account', 'accountant', 'accounting', 'director', 'ceo'])
                || ($voucherRead && $request->user()->hasRole('warehouse')));
        $customers = Customer::query()->with(['currentOwner:id,name,short_name', 'assignedTo:id,name,short_name'])->get(['id', 'name', 'customer_code', 'current_owner_sale_id', 'assigned_to', 'phone', 'address', 'company_name']);
        $allowedIds = $canLoad ? $customers->pluck('id') : Customer::query()->managedBySale($request->user()->id)->pluck('id');
        $snapshot = $service->snapshot();
        $groups = $service->summarize($snapshot['entries'] ?? []);
        $mappings = $service->mappings();
        $byId = $customers->keyBy('id');
        $staffNames = [];
        foreach (User::query()->get(['id', 'name', 'short_name']) as $staff) {
            foreach (array_unique([$service->customerName($staff->name), $service->customerName($staff->short_name ?? '')]) as $name) {
                if ($name !== '') {
                    $staffNames[$name][$staff->id] = $staff;
                }
            }
        }
        $sourceNames = collect($groups)->countBy(fn ($g) => $service->customerName($g['name']));
        $sourceCodes = collect($groups)->filter(fn ($g) => trim((string) $g['code']) !== '')
            ->countBy(fn ($g) => mb_strtoupper(trim((string) $g['code'])));
        foreach ($groups as $key => &$group) {
            $group['name_conflict'] = ($sourceNames[$service->customerName($group['name'])] ?? 0) > 1;
            $group['code_conflict'] = ($sourceCodes[mb_strtoupper(trim((string) $group['code']))] ?? 0) > 1;
            $matches = $service->matchingCustomers($group, $customers);
            if (! $canLoad && $matches->count() === 1) {
                $candidate = $matches->first();
                $sameCode = $group['code'] !== '' && $group['code'] === mb_strtoupper(trim((string) $candidate->customer_code));
                $phone = $service->customerPhone($group['phone'] ?? '');
                $samePhone = $phone !== '' && $phone === $service->customerPhone((string) $candidate->phone);
                if (! $sameCode && ! $samePhone) {
                    // A name alone is not sufficient evidence to disclose a ledger to a sale.
                    $matches = collect();
                }
            }
            $group['matched_by'] = $matches->count() === 1 && $group['code'] !== '' && mb_strtoupper(trim((string) $matches->first()->customer_code)) === $group['code'] ? 'code' : 'name';
            $group['customer_id'] = $matches->count() === 1 ? $matches->first()->id : null;
            $group['manual_mapping'] = isset($mappings[$key]['customer_id']);
            if (! empty($mappings[$key]['unlinked'])) {
                $group['customer_id'] = null;
                $group['matched_by'] = 'unlinked';
            } elseif ($group['manual_mapping']) {
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

        if (! $canLoad) {
            $customers = $customers->whereIn('id', $allowedIds)->values();
            if ($snapshot !== null) {
                $snapshot['has_unidentified_payments'] = ! empty($snapshot['issues']);
                $snapshot['entries'] = collect($groups)->flatMap(fn ($group) => $group['entries'])->values()->all();
                $snapshot['issues'] = [];
                unset($snapshot['payment_syncs'], $snapshot['loaded_by'], $snapshot['cashflow_ledgers'], $snapshot['cashflow_loaded_at']);
            }
        }

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
        ]);
        foreach (['allowed_orders', 'due_days'] as $field) {
            if ($request->has($field) && ! $request->filled($field)) {
                $request->merge([$field => null]);
            }
        }
        if ($request->has('fixed_debt')) {
            $fixedDebt = trim((string) $request->input('fixed_debt', ''));
            if (preg_match('/^\d{1,3}(?:\.\d{3})+$/', $fixedDebt)) {
                $fixedDebt = str_replace('.', '', $fixedDebt);
            }
            $request->merge(['fixed_debt' => $fixedDebt === '' ? null : $fixedDebt]);
        }
        $data = $request->validate([
            'fixed_debt' => 'nullable|integer|min:0|max:999999999999999',
            'threshold_days' => 'nullable|integer|min:0|max:3650',
            'source_key' => 'required|string|size:64|regex:/^[a-f0-9]{64}$/',
            'policy_version' => 'required|integer|min:0',
            'allowed_orders' => 'nullable|integer|min:0|max:100000',
            'credit_limit' => 'nullable|integer|min:0|max:999999999999999',
            'due_days' => 'nullable|integer|min:0|max:3650',
        ] + CustomerDebtPolicyService::cycleRules());
        $context = $this->resolveGroups($request, $service);
        $group = $context['groups'][$data['source_key']] ?? null;
        abort_unless($group, 404);
        $actor = $request->user();
        $canManageDebt = $actor->canManageCustomerDebt();
        app(CustomerDebtPolicyService::class)->save($group, $data, $actor->id, $canManageDebt, (int) $data['policy_version'], $actor->isSalesFlowRole() || $canManageDebt ? $actor : null);

        return back()->with('success', 'Đã cập nhật tiêu chí công nợ và tính lại cảnh báo khách hàng.');
    }

    public function unlockPolicy(Request $request, CustomerDebtSheetService $service)
    {
        abort_unless($request->user()->canManageCustomerDebt(), 403);
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
        $data = $request->validate([
            'source_key' => 'required|string|size:64|regex:/^[a-f0-9]{64}$/',
            'customer_id' => 'present|nullable|integer|min:1',
        ]);
        $key = $data['source_key'];
        \Illuminate\Support\Facades\Cache::lock('customer-debt-load', 60)->block(5, function () use ($request, $service, $key, $data) {
            $group = $this->resolveGroups($request, $service)['groups'][$key] ?? null;
            abort_unless($group, 404);
            $expected = isset($data['customer_id']) ? (int) $data['customer_id'] : null;
            abort_unless($group['customer_id'] === $expected, 409, 'Liên kết đã thay đổi. Vui lòng tải lại trang.');
            if ($group['customer_id'] || $group['manual_mapping']) {
                $service->unmapCustomerWithinLock($key);
            }
        });

        return redirect()->route('customer-debt.index', ['tab' => 'all', 'customer' => $key, 'window' => 'all', 'recovery' => 'all'], 303)
            ->withFragment('debt-detail')
            ->with('success', 'Đã gỡ ghép khách. Nguồn này chờ được ghép lại thủ công.');
    }

    public function reviewPurchase(Request $request, CustomerDebtSheetService $service)
    {
        abort_unless($request->user()->canManageCustomerDebt(), 403);
        $data = $request->validate([
            'id' => ['required', 'regex:/^[a-f0-9]{64}$/'],
            'status' => 'required|in:accepted,excluded',
        ]);
        $service->reviewPurchase($data['id'], $data['status'], $request->user()->id);

        return back()->with('success', 'Đã lưu quyết định duyệt dòng mua hàng.');
    }

    public function load(Request $request, CustomerDebtSheetService $service)
    {
        abort_unless($request->user()->hasRole(['admin', 'account', 'accountant', 'accounting', 'director', 'ceo']), 403);
        $data = $request->validate(['source' => 'required|in:google,paste,excel', 'sales_text' => 'required_if:source,paste|nullable|string|max:15000000', 'payments_text' => 'required_if:source,paste|nullable|string|max:15000000', 'bank_payments_text' => 'nullable|string|max:15000000', 'workbook' => 'required_if:source,excel|nullable|file|mimes:xlsx|max:20480']);
        try {
            $tabs = match ($data['source']) {
                'google' => $service->fetch(),
                'excel' => $service->workbook($request->file('workbook')->getRealPath()),
                default => [$service->textValues($data['sales_text']), $service->textValues($data['payments_text']), $request->filled('bank_payments_text') ? $service->textValues($data['bank_payments_text']) : null],
            };
            $paymentUpdate = $service->saveTabs($tabs, $request->user()->id, $data['source']);
        } catch (\Throwable $e) {
            report($e);
            $message = $e instanceof \Google\Service\Exception ? 'Không đọc được Google Sheet. Kiểm tra quyền đọc của tài khoản dịch vụ và các tab nguồn.' : ($e instanceof \RuntimeException ? $e->getMessage() : 'Không tải được dữ liệu. Vui lòng kiểm tra nguồn và thử lại.');

            return back()->with('error', $message);
        }

        return back()->with('success', 'Đã cập nhật bán hàng, thanh toán và tính lại công nợ từ các tab nguồn. Liên kết khách và tiêu chí cảnh báo được giữ nguyên; không cộng trùng lần tải trước.')->with('payment_update_id', $paymentUpdate['id']);
    }
}
