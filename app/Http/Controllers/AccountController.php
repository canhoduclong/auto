<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountAdjustment;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class AccountController extends Controller
{
    public function index()
    {
        $accounts = Account::orderBy('name')->get();
        $totalBalance = $accounts->where('is_active', true)->sum('balance');
        $lowBalanceCount = $accounts->where('is_active', true)->filter(fn ($a) => $a->isLowBalance())->count();

        return view('accounting.accounts.index', compact('accounts', 'totalBalance', 'lowBalanceCount'));
    }

    public function adjustmentHistory(Request $request)
    {
        $filters = $request->validate([
            'account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'type' => ['nullable', 'in:deposit,withdraw'],
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $accountId = (int) ($filters['account_id'] ?? 0);
        $type = (string) ($filters['type'] ?? '');
        $fromDate = filled($filters['from_date'] ?? null)
            ? Carbon::createFromFormat('Y-m-d', $filters['from_date'])->toDateString()
            : null;
        $toDate = filled($filters['to_date'] ?? null)
            ? Carbon::createFromFormat('Y-m-d', $filters['to_date'])->toDateString()
            : null;

        if ($fromDate && $toDate && $fromDate > $toDate) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        $currentAccountsQuery = Account::query()
            ->where('is_active', true)
            ->when($accountId > 0, fn ($q) => $q->where('id', $accountId));

        $currentAccounts = $currentAccountsQuery
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'balance', 'warning_threshold']);

        $currentBalanceTotal = (float) $currentAccounts->sum('balance');

        $query = AccountAdjustment::query()
            ->with(['account:id,name,type', 'performer:id,name'])
            ->when($accountId > 0, fn ($q) => $q->where('account_id', $accountId))
            ->when(in_array($type, ['deposit', 'withdraw'], true), fn ($q) => $q->where('type', $type))
            ->when($fromDate, fn ($q) => $q->whereDate('created_at', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('created_at', '<=', $toDate))
            ->latest()
            ->paginate(30)
            ->appends($request->query());

        $accounts = Account::orderBy('name')->get(['id', 'name', 'type']);
        $totalDeposit = AccountAdjustment::query()
            ->where('type', 'deposit')
            ->when($accountId > 0, fn ($q) => $q->where('account_id', $accountId))
            ->when($fromDate, fn ($q) => $q->whereDate('created_at', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('created_at', '<=', $toDate))
            ->sum('amount');
        $totalWithdraw = AccountAdjustment::query()
            ->where('type', 'withdraw')
            ->when($accountId > 0, fn ($q) => $q->where('account_id', $accountId))
            ->when($fromDate, fn ($q) => $q->whereDate('created_at', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('created_at', '<=', $toDate))
            ->sum('amount');

        return view('accounting.accounts.adjustments', compact(
            'query', 'accounts', 'accountId', 'type', 'fromDate', 'toDate',
            'totalDeposit', 'totalWithdraw', 'currentBalanceTotal', 'currentAccounts'
        ));
    }

    public function balanceHistory(Request $request)
    {
        $filters = $request->validate([
            'account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'direction' => ['nullable', 'in:in,out'],
            'source' => ['nullable', 'in:transaction,adjustment'],
            'movement_scope' => ['nullable', 'in:internal,external,adjustment'],
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $accountId = (int) ($filters['account_id'] ?? 0);
        $direction = (string) ($filters['direction'] ?? '');
        $source = (string) ($filters['source'] ?? '');
        $movementScope = (string) ($filters['movement_scope'] ?? '');
        $fromDate = filled($filters['from_date'] ?? null) ? Carbon::parse($filters['from_date'])->startOfDay() : null;
        $toDate = filled($filters['to_date'] ?? null) ? Carbon::parse($filters['to_date'])->endOfDay() : null;

        if ($fromDate && $toDate && $fromDate->gt($toDate)) {
            [$fromDate, $toDate] = [$toDate->copy()->startOfDay(), $fromDate->copy()->endOfDay()];
        }

        $accounts = Account::query()->orderBy('name')->get(['id', 'name', 'type', 'balance', 'is_active']);
        $accountMap = $accounts->keyBy('id');
        $accountIds = $accountId > 0 ? [$accountId] : $accounts->pluck('id')->all();

        $adjustmentMovements = AccountAdjustment::query()
            ->with('performer:id,name,short_name')
            ->whereIn('account_id', $accountIds)
            ->get()
            ->map(function (AccountAdjustment $adjustment) use ($accountMap): array {
                $isDeposit = $adjustment->type === 'deposit';

                return [
                    'key' => 'adjustment-' . $adjustment->id,
                    'source' => 'adjustment',
                    'source_id' => $adjustment->id,
                    'movement_scope' => 'adjustment',
                    'account_id' => (int) $adjustment->account_id,
                    'account' => $accountMap->get($adjustment->account_id),
                    'direction' => $isDeposit ? 'in' : 'out',
                    'type_label' => $isDeposit ? 'Nạp tiền' : 'Rút tiền',
                    'from_name' => $isDeposit ? 'Ngoài hệ thống' : ($accountMap->get($adjustment->account_id)?->name ?? 'Tài khoản'),
                    'to_name' => $isDeposit ? ($accountMap->get($adjustment->account_id)?->name ?? 'Tài khoản') : 'Ngoài hệ thống',
                    'request_title' => null,
                    'amount' => (float) $adjustment->amount,
                    'signed_amount' => ($isDeposit ? 1 : -1) * (float) $adjustment->amount,
                    'performed_by' => $this->userLedgerName($adjustment->performer),
                    'note' => $adjustment->note,
                    'occurred_at' => $adjustment->created_at,
                    'detail_url' => null,
                ];
            });

        $transactionMovements = Transaction::query()
            ->with([
                'transactionCategory:id,name,flow_direction',
                'submitter:id,name,short_name',
                'approver:id,name,short_name',
                'destinationAccount:id,name,type,account_number,bank_name',
            ])
            ->whereIn('account_id', $accountIds)
            ->where('status', Transaction::STATUS_APPROVED)
            ->get()
            ->map(function (Transaction $transaction) use ($accountMap): array {
                $flowDirection = $transaction->transactionCategory?->flow_direction
                    ?? (in_array((string) $transaction->type, ['payment', 'extra_income'], true) ? 'in' : 'out');
                $isIncome = $flowDirection === 'in';
                $isInternalTransfer = !$isIncome
                    && $transaction->destination_type === 'internal'
                    && $transaction->destination_account_id;
                $accountName = $accountMap->get($transaction->account_id)?->name ?? 'Tài khoản nguồn';
                $externalName = $transaction->external_recipient ?: 'Bên ngoài hệ thống';

                return [
                    'key' => 'transaction-' . $transaction->id,
                    'source' => 'transaction',
                    'source_id' => $transaction->id,
                    'movement_scope' => $isInternalTransfer ? 'internal' : 'external',
                    'account_id' => (int) $transaction->account_id,
                    'account' => $accountMap->get($transaction->account_id),
                    'direction' => $isIncome ? 'in' : 'out',
                    'type_label' => $transaction->transactionCategory?->name ?: $this->transactionTypeLabel((string) $transaction->type),
                    'from_name' => $isIncome ? $externalName : $accountName,
                    'to_name' => $isIncome
                        ? $accountName
                        : ($isInternalTransfer ? ($transaction->destinationAccount?->name ?? 'Tài khoản đến') : $externalName),
                    'request_title' => $transaction->request_title,
                    'amount' => (float) $transaction->amount,
                    'signed_amount' => ($isIncome ? 1 : -1) * (float) $transaction->amount,
                    'performed_by' => $this->userLedgerName($transaction->approver ?: $transaction->submitter),
                    'note' => $transaction->note ?: $transaction->request_title,
                    'occurred_at' => $transaction->approved_at ?? $transaction->created_at,
                    'detail_url' => accounting_route('cashflow.show', $transaction),
                ];
            });

        $internalTransferMovements = Transaction::query()
            ->with([
                'transactionCategory:id,name,flow_direction',
                'submitter:id,name,short_name',
                'approver:id,name,short_name',
                'account:id,name',
            ])
            ->where('destination_type', 'internal')
            ->whereIn('destination_account_id', $accountIds)
            ->where('status', Transaction::STATUS_APPROVED)
            ->whereHas('transactionCategory', fn ($query) => $query->where('flow_direction', 'out'))
            ->get()
            ->map(function (Transaction $transaction) use ($accountMap): array {
                return [
                    'key' => 'transaction-destination-' . $transaction->id,
                    'source' => 'transaction',
                    'source_id' => $transaction->id,
                    'movement_scope' => 'internal',
                    'account_id' => (int) $transaction->destination_account_id,
                    'account' => $accountMap->get($transaction->destination_account_id),
                    'direction' => 'in',
                    'type_label' => 'Luân chuyển nội bộ',
                    'from_name' => $transaction->account?->name ?? 'Tài khoản nguồn',
                    'to_name' => $accountMap->get($transaction->destination_account_id)?->name ?? 'Tài khoản đến',
                    'request_title' => $transaction->request_title,
                    'amount' => (float) $transaction->amount,
                    'signed_amount' => (float) $transaction->amount,
                    'performed_by' => $this->userLedgerName($transaction->approver ?: $transaction->submitter),
                    'note' => 'Nhận từ ' . ($transaction->account?->name ?? 'tài khoản nguồn')
                        . ($transaction->note ? ' - ' . $transaction->note : ''),
                    'occurred_at' => $transaction->approved_at ?? $transaction->created_at,
                    'detail_url' => accounting_route('cashflow.show', $transaction),
                ];
            });

        $allMovements = $adjustmentMovements
            ->concat($transactionMovements)
            ->concat($internalTransferMovements)
            ->groupBy('account_id')
            ->flatMap(function ($accountMovements, $movementAccountId) use ($accountMap) {
                $runningBalance = (float) ($accountMap->get((int) $movementAccountId)?->balance ?? 0);

                return $accountMovements
                    ->sortByDesc(fn (array $movement) => sprintf(
                        '%s-%s',
                        optional($movement['occurred_at'])->format('Y-m-d H:i:s.u') ?? '',
                        $movement['key']
                    ))
                    ->map(function (array $movement) use (&$runningBalance): array {
                        $movement['balance_after'] = $runningBalance;
                        $movement['balance_before'] = $runningBalance - $movement['signed_amount'];
                        $runningBalance = $movement['balance_before'];

                        return $movement;
                    });
            })
            ->sortByDesc(fn (array $movement) => optional($movement['occurred_at'])->timestamp ?? 0)
            ->values();

        $filteredMovements = $allMovements
            ->when($direction !== '', fn ($items) => $items->where('direction', $direction))
            ->when($source !== '', fn ($items) => $items->where('source', $source))
            ->when($movementScope !== '', fn ($items) => $items->where('movement_scope', $movementScope))
            ->when($fromDate, fn ($items) => $items->filter(fn ($item) => $item['occurred_at']?->gte($fromDate)))
            ->when($toDate, fn ($items) => $items->filter(fn ($item) => $item['occurred_at']?->lte($toDate)))
            ->values();

        $ledgerEntries = $filteredMovements
            ->groupBy(fn (array $movement) => $movement['source'] . '-' . $movement['source_id'])
            ->map(function ($entryMovements): array {
                $first = $entryMovements->first();

                return [
                    'key' => $first['source'] . '-' . $first['source_id'],
                    'source' => $first['source'],
                    'source_id' => $first['source_id'],
                    'movement_scope' => $first['movement_scope'],
                    'from_name' => $first['from_name'],
                    'to_name' => $first['to_name'],
                    'type_label' => $first['type_label'],
                    'request_title' => $first['request_title'],
                    'performed_by' => $first['performed_by'],
                    'note' => $first['note'],
                    'occurred_at' => $entryMovements->max('occurred_at'),
                    'detail_url' => $first['detail_url'],
                    'movements_by_account' => $entryMovements->keyBy('account_id'),
                ];
            })
            ->sortByDesc(fn (array $entry) => optional($entry['occurred_at'])->timestamp ?? 0)
            ->values();

        $perPage = 30;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $ledgerRows = new LengthAwarePaginator(
            $ledgerEntries->forPage($currentPage, $perPage)->values(),
            $ledgerEntries->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('accounting.accounts.balance_history', [
            'ledgerRows' => $ledgerRows,
            'accounts' => $accounts,
            'accountId' => $accountId,
            'direction' => $direction,
            'source' => $source,
            'movementScope' => $movementScope,
            'fromDate' => $fromDate?->toDateString(),
            'toDate' => $toDate?->toDateString(),
            'totalIn' => (float) $filteredMovements->where('direction', 'in')->sum('amount'),
            'totalOut' => (float) $filteredMovements->where('direction', 'out')->sum('amount'),
            'externalIn' => (float) $filteredMovements->where('movement_scope', 'external')->where('direction', 'in')->sum('amount'),
            'externalOut' => (float) $filteredMovements->where('movement_scope', 'external')->where('direction', 'out')->sum('amount'),
            'internalTransferTotal' => (float) $filteredMovements->where('movement_scope', 'internal')->unique('source_id')->sum('amount'),
            'totalAccountBalance' => (float) $accounts->where('is_active', true)->sum('balance'),
        ]);
    }

    private function transactionTypeLabel(string $type): string
    {
        return match ($type) {
            'payment' => 'Thu tiền đơn hàng',
            'refund' => 'Hoàn tiền',
            'extra_income' => 'Thu nhập khác',
            'expense' => 'Chi phí',
            default => ucfirst(str_replace('_', ' ', $type)),
        };
    }

    private function userLedgerName($user): string
    {
        if (!$user) {
            return 'Hệ thống';
        }

        return (string) ($user->short_name ?: $user->name ?: 'Hệ thống');
    }

    public function create()
    {
        return view('accounting.accounts.form', ['account' => null]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'              => 'required|string|max:100',
            'type'              => 'required|in:cash,bank',
            'owner_type'        => 'required|in:personal,company,business_household,other',
            'owner_name'        => 'nullable|string|max:150',
            'account_number'    => 'nullable|string|max:50',
            'bank_name'         => 'nullable|string|max:100',
            'balance'           => 'required|numeric|min:0',
            'warning_threshold' => 'required|numeric|min:0',
            'is_active'         => 'boolean',
            'note'              => 'nullable|string|max:500',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);

        Account::create($data);
        return redirect()->route(accounting_route_name('accounts.index'))->with('success', 'Đã tạo tài khoản thành công.');
    }

    public function edit(Account $account)
    {
        return view('accounting.accounts.form', compact('account'));
    }

    public function update(Request $request, Account $account)
    {
        $data = $request->validate([
            'name'              => 'required|string|max:100',
            'type'              => 'required|in:cash,bank',
            'owner_type'        => 'required|in:personal,company,business_household,other',
            'owner_name'        => 'nullable|string|max:150',
            'account_number'    => 'nullable|string|max:50',
            'bank_name'         => 'nullable|string|max:100',
            'warning_threshold' => 'required|numeric|min:0',
            'is_active'         => 'boolean',
            'note'              => 'nullable|string|max:500',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);

        $account->update($data);
        return redirect()->route(accounting_route_name('accounts.index'))->with('success', 'Đã cập nhật tài khoản.');
    }

    public function deposit(Request $request, Account $account)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:1',
            'note'   => 'nullable|string|max:300',
        ]);
        $amount = (float) $data['amount'];
        $balanceBefore = (float) $account->balance;

        $account->increment('balance', $amount);
        $account->increment('opening_balance', $amount);

        AccountAdjustment::create([
            'account_id'     => $account->id,
            'performed_by'   => auth()->id(),
            'type'           => 'deposit',
            'amount'         => $amount,
            'balance_before' => $balanceBefore,
            'balance_after'  => $balanceBefore + $amount,
            'note'           => $data['note'] ?? null,
        ]);

        return redirect()->route(accounting_route_name('accounts.index'))
            ->with('success', 'Đã nạp ' . number_format($amount) . 'đ vào ' . $account->name . '.');
    }

    public function withdraw(Request $request, Account $account)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:1',
            'note'   => 'nullable|string|max:300',
        ]);
        $amount = (float) $data['amount'];
        if ($amount > (float) $account->balance) {
            return back()->withErrors(['amount' => 'Số tiền rút vượt quá số dư hiện tại.']);
        }
        $balanceBefore = (float) $account->balance;

        $account->decrement('balance', $amount);
        $account->decrement('opening_balance', $amount);

        AccountAdjustment::create([
            'account_id'     => $account->id,
            'performed_by'   => auth()->id(),
            'type'           => 'withdraw',
            'amount'         => $amount,
            'balance_before' => $balanceBefore,
            'balance_after'  => $balanceBefore - $amount,
            'note'           => $data['note'] ?? null,
        ]);

        return redirect()->route(accounting_route_name('accounts.index'))
            ->with('success', 'Đã rút ' . number_format($amount) . 'đ từ ' . $account->name . '.');
    }
}
