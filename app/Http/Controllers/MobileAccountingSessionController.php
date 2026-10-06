<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class MobileAccountingSessionController extends Controller
{
    private const PAGES = [
        'reconciliation' => '/accounting/reconciliation',
        'daily_sales' => '/accounting/daily-sales',
        'adjustments' => '/accounting/order-adjustments',
    ];

    public function create(Request $request)
    {
        $user = $request->user();
        abort_unless($user && $user->hasRole(['account', 'accountant', 'accounting', 'admin']), 403);
        $data = $request->validate(['page' => ['required', 'in:'.implode(',', array_keys(self::PAGES))]]);
        $ticket = Str::random(64);
        Cache::put('mobile-accounting-session:'.hash('sha256', $ticket), [
            'user_id' => $user->id,
            'path' => self::PAGES[$data['page']],
            'token_id' => $request->attributes->get('mobile_api_token')?->id,
        ], now()->addMinute());

        return response()->json(['success' => true, 'data' => [
            'url' => route('mobile.accounting.session', ['ticket' => $ticket]),
        ]])->header('Cache-Control', 'no-store');
    }

    public function consume(Request $request, string $ticket)
    {
        abort_unless(preg_match('/^[a-zA-Z0-9]{64}$/', $ticket), 404);
        $data = Cache::pull('mobile-accounting-session:'.hash('sha256', $ticket));
        abort_unless($data, 410, 'Phiên mở màn hình đã hết hạn. Vui lòng mở lại từ ứng dụng.');
        $user = User::findOrFail($data['user_id']);
        abort_unless($user->hasRole(['account', 'accountant', 'accounting', 'admin']), 403);
        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        $request->session()->put('mobile_accounting', true);
        $request->session()->put('mobile_accounting_token_id', $data['token_id']);

        return redirect($data['path'])->header('Cache-Control', 'no-store');
    }
}
