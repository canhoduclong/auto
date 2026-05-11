<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountAdjustment;
use Illuminate\Http\Request;

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
        $accountId = (int) $request->input('account_id', 0);
        $type = $request->input('type', '');
        $fromDate = $request->input('from_date', '');
        $toDate = $request->input('to_date', '');

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
            ->when($fromDate !== '', fn ($q) => $q->whereDate('created_at', '>=', $fromDate))
            ->when($toDate !== '', fn ($q) => $q->whereDate('created_at', '<=', $toDate))
            ->latest()
            ->paginate(30)
            ->appends($request->query());

        $accounts = Account::orderBy('name')->get(['id', 'name', 'type']);
        $totalDeposit = AccountAdjustment::query()
            ->where('type', 'deposit')
            ->when($accountId > 0, fn ($q) => $q->where('account_id', $accountId))
            ->when($fromDate !== '', fn ($q) => $q->whereDate('created_at', '>=', $fromDate))
            ->when($toDate !== '', fn ($q) => $q->whereDate('created_at', '<=', $toDate))
            ->sum('amount');
        $totalWithdraw = AccountAdjustment::query()
            ->where('type', 'withdraw')
            ->when($accountId > 0, fn ($q) => $q->where('account_id', $accountId))
            ->when($fromDate !== '', fn ($q) => $q->whereDate('created_at', '>=', $fromDate))
            ->when($toDate !== '', fn ($q) => $q->whereDate('created_at', '<=', $toDate))
            ->sum('amount');

        return view('accounting.accounts.adjustments', compact(
            'query', 'accounts', 'accountId', 'type', 'fromDate', 'toDate',
            'totalDeposit', 'totalWithdraw', 'currentBalanceTotal', 'currentAccounts'
        ));
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
