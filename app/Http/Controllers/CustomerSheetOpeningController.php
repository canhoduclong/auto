<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\CustomerPriorityService;
use App\Services\CustomerSheetOpeningLedger;
use App\Services\SystemCustomerDebtReportService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CustomerSheetOpeningController extends Controller
{
    public function index(Request $request, CustomerSheetOpeningLedger $ledger, SystemCustomerDebtReportService $system)
    {
        $filters = $request->validate([
            'tab' => 'nullable|in:purchases,payments,customers', 'search' => 'nullable|string|max:150',
            'page' => 'nullable|integer|min:1', 'source_page' => 'nullable|integer|min:1',
            'system_search' => 'nullable|string|max:150', 'source_search' => 'nullable|string|max:150',
            'panel' => 'nullable|in:system,source',
        ]);
        $tab = $filters['tab'] ?? 'purchases';
        $panel = $tab === 'customers' && $request->ajax() ? ($filters['panel'] ?? null) : null;
        $systemSearch = Customer::normalizeName($filters['system_search'] ?? '');
        $sourceSearch = Customer::normalizeName($filters['source_search'] ?? '');
        $search = Customer::normalizeName($filters['search'] ?? '');
        $matches = fn (string $text) => str_contains(Customer::normalizeName($text), $search);
        $snapshot = $ledger->snapshot();
        $groups = $ledger->groups($snapshot);
        $customers = Customer::query()->orderBy('name')->get(['id', 'name', 'customer_code', 'phone'])->keyBy('id');
        $allLinks = collect($ledger->allLinks())->filter(fn ($link) => $customers->has($link['customer_id'] ?? null));
        $links = $allLinks->only(array_keys($groups));
        $opening = $gifts = $paid = $systemSales = [];
        $purchases = collect();
        $payments = collect();
        $pending = collect();
        $today = now()->toDateString();
        $cutoff = CustomerSheetOpeningLedger::CUTOFF;
        foreach ($groups as $key => $group) {
            $id = $links[$key]['customer_id'] ?? null;
            $label = $customers[$id]->name ?? $group['name'];
            if (! $id && str_contains(Customer::normalizeName($group['name'].' '.$group['code'].' '.$group['phone']), $sourceSearch)) {
                $pending->push($group);
            }
            if ($panel === 'source') {
                continue;
            }
            $visible = $tab !== 'customers' && $matches($label.' '.$group['name'].' '.$group['code']);
            foreach ($group['entries'] as $entry) {
                if ($entry['date'] > $today) {
                    continue;
                }
                $row = $entry + ['customer' => $label, 'linked' => (bool) $id];
                if ($entry['type'] === 'payment') {
                    if ($id) {
                        $paid[$id] = ($paid[$id] ?? 0) + $entry['amount'];
                    }
                    if ($visible) {
                        $payments->push($row);
                    }
                    continue;
                }
                if ($entry['date'] < $cutoff && $id) {
                    $opening[$id] = ($opening[$id] ?? 0) + $entry['amount'];
                }
                if ($id) {
                    $gifts[$id] = ($gifts[$id] ?? 0) + ($entry['gift'] ?? 0);
                }
                if ($visible && $entry['date'] < $cutoff) {
                    $purchases->push($row);
                }
            }
        }
        if ($tab !== 'payments' && $panel !== 'source') {
            foreach ($system->confirmedOrders($customers->keys()->all()) as $order) {
                $date = CarbonImmutable::parse($order->delivery_date ?: $order->created_at)->toDateString();
                if ($date < $cutoff || $date > $today) {
                    continue;
                }
                $id = $order->customer_id;
                $amount = (int) round($order->recognized_revenue);
                $systemSales[$id] = ($systemSales[$id] ?? 0) + $amount;
                $customer = $customers[$id];
                if ($tab === 'purchases' && $matches($customer->name.' '.$customer->customer_code.' '.$order->code)) {
                    $purchases->push(['date' => $date, 'customer' => $customer->name, 'linked' => true, 'amount' => $amount, 'note' => $order->code, 'source_sheet' => 'Bill hệ thống', 'row' => $order->id]);
                }
            }
        }
        if ($tab === 'payments') {
            foreach ($snapshot['issues'] ?? [] as $issue) {
                if ($matches($issue['note'] ?? '')) {
                    $payments->push($issue + ['customer' => 'Chưa xác định khách', 'linked' => false]);
                }
            }
        }
        $customerRows = $customers->filter(fn ($customer) => str_contains(Customer::normalizeName($customer->name.' '.$customer->customer_code.' '.$customer->phone), $systemSearch))->values();
        $rows = match ($tab) {
            'customers' => $customerRows,
            'payments' => $payments->sortByDesc('date'),
            default => $purchases->sortByDesc('date'),
        };

        $view = $panel ? 'customers.sheet-opening-'.$panel : 'customers.sheet-opening';

        return view($view, [
            'tab' => $tab, 'snapshot' => $snapshot, 'rows' => $this->paginate($request, $rows, 'page'),
            'pending' => $this->paginate($request, $pending->sortBy('name'), 'source_page'),
            'availableCustomers' => $tab === 'customers' ? $customers->except($allLinks->pluck('customer_id')->all())->values() : collect(),
            'opening' => $opening, 'gifts' => $gifts, 'paid' => $paid,
            'systemSales' => $systemSales, 'cutoff' => $cutoff,
        ]);
    }

    private function paginate(Request $request, Collection $rows, string $name): LengthAwarePaginator
    {
        $page = max(1, (int) $request->input($name, 1));

        return new LengthAwarePaginator($rows->forPage($page, 30)->values(), $rows->count(), 30, $page, [
            'path' => $request->url(), 'query' => $request->except([$name, 'panel']), 'pageName' => $name,
        ]);
    }

    public function refresh(Request $request, CustomerSheetOpeningLedger $ledger)
    {
        try {
            $run = $ledger->refresh($request->user()->id);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Không cập nhật được Google Sheet. Dữ liệu đã lưu vẫn có thể xem.');
        }

        return back()->with('success', $run['unchanged'] ? 'Dữ liệu Google Sheet không thay đổi.' : 'Đã cập nhật dữ liệu Google Sheet.');
    }

    public function link(Request $request, CustomerSheetOpeningLedger $ledger, CustomerPriorityService $priorities)
    {
        $data = $request->validate([
            'source_key' => ['required', 'regex:/^[a-f0-9]{64}$/'],
            'action' => 'required|in:link,create',
            'customer_id' => 'required_if:action,link|nullable|integer',
        ]);
        Cache::lock('customer-debt-load', 60)->block(5, function () use ($request, $ledger, $priorities, $data) {
            DB::transaction(function () use ($request, $ledger, $priorities, $data) {
                $groups = $ledger->groups($ledger->snapshot());
                $key = $data['source_key'];
                abort_unless(isset($groups[$key]), 404);
                $links = collect($ledger->allLinks());
                $mapped = $links->get($key)['customer_id'] ?? null;
                if ($mapped && Customer::query()->whereKey($mapped)->exists()) {
                    return;
                }
                if ($data['action'] === 'link') {
                    $customer = Customer::query()->findOrFail($data['customer_id']);
                    if ($links->pluck('customer_id')->contains($customer->id)) {
                        throw ValidationException::withMessages(['customer_id' => 'Khách này đã được ghép. Vui lòng chọn khách khác.']);
                    }
                } else {
                    Gate::forUser($request->user())->authorize('create', Customer::class);
                    $group = $groups[$key];
                    $code = $group['code'];
                    $phone = $group['phone'];
                    if (Customer::withTrashed()->where('name_normalized', Customer::normalizeName($group['name']))
                        ->when($code !== '', fn ($query) => $query->orWhere('customer_code', $code))
                        ->when($phone !== '', fn ($query) => $query->orWhere('phone', $phone))->exists()) {
                        throw ValidationException::withMessages(['customer_id' => 'Khách đã có trong hệ thống. Vui lòng chọn khách để ghép.']);
                    }
                    $customer = Customer::create([
                        'name' => $group['name'], 'customer_code' => $code ?: null, 'phone' => $phone ?: null,
                        'user_id' => $request->user()->id, 'customer_status' => 'free', 'free_from_date' => now(), 'current_cycle_no' => 1,
                    ]);
                    $priorities->ensureLifecycle($customer);
                }
                $ledger->link($key, $customer->id);
            });
        });

        return back()->with('success', 'Đã ghép khách cho báo cáo đồng bộ.');
    }
}
