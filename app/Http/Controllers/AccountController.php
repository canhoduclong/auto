<?php

namespace App\Http\Controllers;

use App\Models\Account;
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
        return redirect()->route('accounting.accounts.index')->with('success', 'Đã tạo tài khoản thành công.');
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
        return redirect()->route('accounting.accounts.index')->with('success', 'Đã cập nhật tài khoản.');
    }

    public function deposit(Request $request, Account $account)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:1',
            'note'   => 'nullable|string|max:300',
        ]);
        $account->increment('balance', (float) $data['amount']);
        return redirect()->route('accounting.accounts.index')
            ->with('success', 'Đã nạp ' . number_format($data['amount']) . 'đ vào ' . $account->name . '.');
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
        $account->decrement('balance', $amount);
        return redirect()->route('accounting.accounts.index')
            ->with('success', 'Đã rút ' . number_format($amount) . 'đ từ ' . $account->name . '.');
    }
}
