<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class DepartmentFinanceRequestController extends Controller
{
    private const SOURCES = [
        'warehouse' => [
            'label' => 'Kho',
            'layout' => 'layouts.warehouse',
            'route_prefix' => 'warehouse.finance-requests',
            'role' => 'warehouse,admin',
        ],
        'ceo' => [
            'label' => 'CEO',
            'layout' => 'layouts.ceo',
            'route_prefix' => 'ceo.finance-requests',
            'role' => 'ceo,CEO,admin',
        ],
        'leader' => [
            'label' => 'Leader',
            'layout' => 'layouts.site',
            'route_prefix' => 'leader.finance-requests',
            'role' => 'leader,leader_sale,sale_manager,admin',
        ],
        'manager' => [
            'label' => 'Manager',
            'layout' => 'layouts.site',
            'route_prefix' => 'manager.finance-requests',
            'role' => 'manager,manager_sale,director,admin',
        ],
        'shipper' => [
            'label' => 'Shipper',
            'layout' => 'layouts.shipper',
            'route_prefix' => 'shipper.finance-requests',
            'role' => 'shipper,manager_shipper,admin',
            'own_only' => true,
        ],
        'procurement' => [
            'label' => 'Thu mua',
            'layout' => 'layouts.procurement',
            'route_prefix' => 'procurement.finance-requests',
            'role' => 'procurement_manager,admin',
            'own_only' => true,
        ],
    ];

    protected $settings;

    public function __construct()
    {
        $this->middleware('auth');
        $this->settings = Cache::remember('settings', 60, function () {
            return Setting::all()->keyBy('key');
        });
    }

    public function warehouseIndex(Request $request)
    {
        return $this->index($request, 'warehouse');
    }

    public function warehouseStore(Request $request)
    {
        return $this->store($request, 'warehouse');
    }

    public function warehousePrint(Transaction $transaction)
    {
        return $this->printRequest($transaction, 'warehouse');
    }

    public function ceoIndex(Request $request)
    {
        return $this->index($request, 'ceo');
    }

    public function ceoStore(Request $request)
    {
        return $this->store($request, 'ceo');
    }

    public function ceoPrint(Transaction $transaction)
    {
        return $this->printRequest($transaction, 'ceo');
    }

    public function leaderIndex(Request $request)
    {
        return $this->index($request, 'leader');
    }

    public function leaderStore(Request $request)
    {
        return $this->store($request, 'leader');
    }

    public function leaderPrint(Transaction $transaction)
    {
        return $this->printRequest($transaction, 'leader');
    }

    public function managerIndex(Request $request)
    {
        return $this->index($request, 'manager');
    }

    public function managerStore(Request $request)
    {
        return $this->store($request, 'manager');
    }

    public function managerPrint(Transaction $transaction)
    {
        return $this->printRequest($transaction, 'manager');
    }

    public function shipperIndex(Request $request)
    {
        return $this->index($request, 'shipper');
    }

    public function shipperStore(Request $request)
    {
        return $this->store($request, 'shipper');
    }

    public function shipperPrint(Transaction $transaction)
    {
        return $this->printRequest($transaction, 'shipper');
    }

    public function procurementIndex(Request $request) { return $this->index($request, 'procurement'); }
    public function procurementStore(Request $request) { return $this->store($request, 'procurement'); }
    public function procurementPrint(Transaction $transaction) { return $this->printRequest($transaction, 'procurement'); }

    private function config(string $source): array
    {
        abort_unless(isset(self::SOURCES[$source]), 404);

        return self::SOURCES[$source];
    }

    private function index(Request $request, string $source)
    {
        $settings = $this->settings;
        

        $config = $this->config($source);
        $this->authorizeSource($config);

        $status = in_array($request->input('status'), ['all', Transaction::STATUS_PENDING_APPROVAL, Transaction::STATUS_APPROVED, Transaction::STATUS_REJECTED], true)
            ? $request->input('status')
            : 'all';
        $formType = in_array($request->input('form_type'), ['all', Transaction::REQUEST_FORM_CASH, Transaction::REQUEST_FORM_PAYMENT], true)
            ? $request->input('form_type')
            : 'all';

        $requests = Transaction::query()
            ->with([
                'submitter:id,name,email,department_id,block_id',
                'submitter.department:id,name,block_id',
                'submitter.department.block:id,name',
                'submitter.block:id,name',
                'approver:id,name',
                'rejecter:id,name',
                'transactionCategory:id,code,name,flow_direction',
                'account:id,name,type',
                'destinationAccount:id,name,type,account_number,bank_name',
            ])
            ->where('request_source', $source)
            ->when($config['own_only'] ?? false, fn ($query) => $query->where('submitted_by', auth()->id()))
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($formType !== 'all', fn ($query) => $query->where('request_form_type', $formType))
            ->latest()
            ->paginate(20)
            ->appends($request->query());

        $categories = TransactionCategory::active()
            ->orderBy('flow_direction')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'flow_direction']);

        $managedAccounts = auth()->user()->managedAccounts()
            ->where('accounts.is_active', true)
            ->orderByDesc('account_user.is_default')
            ->orderBy('accounts.name')
            ->get(['accounts.id', 'accounts.name', 'accounts.type', 'accounts.account_number', 'accounts.bank_name']);
        $defaultManagedAccountId = optional(
            $managedAccounts->first(fn ($account) => (bool) $account->pivot->is_default) ?: $managedAccounts->first()
        )->id;

        return view('department_finance_requests.index', [
            'config' => $config,
            'source' => $source,
            'requests' => $requests,
            'categories' => $categories,
            'status' => $status,
            'formType' => $formType,
            'settings' => $settings,
            'accounts' => Account::active()->orderBy('name')->get(['id', 'name', 'type', 'account_number', 'bank_name']),
            'managedAccounts' => $managedAccounts,
            'defaultManagedAccountId' => $defaultManagedAccountId,
        ]);
    }

    private function store(Request $request, string $source)
    {
        $config = $this->config($source);
        $this->authorizeSource($config);

        $rawAmount = str_replace(['.', ',', ' '], '', (string) $request->input('amount', ''));
        $request->merge(['amount' => $rawAmount]);

        $validated = $request->validate([
            'request_form_type' => ['required', 'in:' . Transaction::REQUEST_FORM_CASH . ',' . Transaction::REQUEST_FORM_PAYMENT],
            'flow_direction' => ['required', 'in:in,out'],
            'request_title' => ['required', 'string', 'max:255'],
            'transaction_category_id' => ['required', 'integer', 'exists:transaction_categories,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.content' => ['required', 'string', 'max:255'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'request_vat' => ['nullable', 'numeric', 'min:0'],
            'method' => ['nullable', 'string', 'max:50'],
            'destination_type' => ['required', 'in:internal,external'],
            'destination_account_id' => ['nullable', 'required_if:destination_type,internal', 'integer', 'exists:accounts,id'],
            'external_recipient' => ['nullable', 'required_if:destination_type,external', 'string', 'max:255'],
            'source_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'note' => ['required', 'string', 'max:1000'],
            'receipt_image' => ['nullable', 'image', 'max:5120'],
        ]);

        if ($validated['request_form_type'] === Transaction::REQUEST_FORM_PAYMENT) {
            $validated['flow_direction'] = 'out';
        }

        $category = TransactionCategory::query()
            ->whereKey((int) $validated['transaction_category_id'])
            ->where('flow_direction', $validated['flow_direction'])
            ->firstOrFail();

        $managedAccountIds = auth()->user()->managedAccounts()
            ->where('accounts.is_active', true)
            ->pluck('accounts.id')
            ->map(fn ($id) => (int) $id);
        $sourceAccountId = (int) ($validated['source_account_id'] ?? 0);

        if ($category->flow_direction === 'out' && $managedAccountIds->isNotEmpty()) {
            if ($sourceAccountId === 0 || !$managedAccountIds->contains($sourceAccountId)) {
                throw ValidationException::withMessages([
                    'source_account_id' => 'Vui lòng chọn tài khoản chi trong danh sách tài khoản bạn đang quản lý.',
                ]);
            }
        } else {
            $sourceAccountId = 0;
        }

        if (
            $category->flow_direction === 'out'
            && $validated['destination_type'] === 'internal'
            && $sourceAccountId > 0
            && $sourceAccountId === (int) $validated['destination_account_id']
        ) {
            throw ValidationException::withMessages([
                'destination_account_id' => 'Tài khoản đến phải khác tài khoản chi.',
            ]);
        }

        $items = collect($validated['items'])
            ->map(function (array $item, int $index) {
                $quantity = (float) $item['quantity'];
                $unitPrice = (float) $item['unit_price'];
                $lineTotal = round($quantity * $unitPrice, 2);

                return [
                    'stt' => $index + 1,
                    'content' => trim($item['content']),
                    'unit' => trim((string) ($item['unit'] ?? '')),
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ];
            })
            ->filter(fn (array $item) => $item['content'] !== '')
            ->values();

        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Vui lòng nhập ít nhất một dòng nội dung.',
            ]);
        }

        $subtotal = round((float) $items->sum('line_total'), 2);
        $vat = round((float) ($validated['request_vat'] ?? 0), 2);
        $total = round($subtotal + $vat, 2);

        if ($total < 1000) {
            throw ValidationException::withMessages([
                'items' => 'Tổng cộng phải từ 1.000đ trở lên.',
            ]);
        }

        $data = [
            'amount' => $total,
            'type' => $category->flow_direction === 'in' ? 'extra_income' : 'extra_expense',
            'transaction_category_id' => $category->id,
            'account_id' => $sourceAccountId ?: null,
            'destination_type' => $validated['destination_type'],
            'destination_account_id' => $validated['destination_type'] === 'internal'
                ? (int) $validated['destination_account_id']
                : null,
            'external_recipient' => $validated['destination_type'] === 'external'
                ? trim((string) $validated['external_recipient'])
                : null,
            'method' => $validated['method'] ?? null,
            'note' => $validated['note'],
            'status' => Transaction::STATUS_PENDING_APPROVAL,
            'submitted_by' => auth()->id(),
            'request_source' => $source,
            'request_department' => $config['label'],
            'request_form_type' => $validated['request_form_type'],
            'request_title' => $validated['request_title'],
            'request_items' => $items->all(),
            'request_subtotal' => $subtotal,
            'request_vat' => $vat,
            'request_total' => $total,
        ];

        if ($request->hasFile('receipt_image')) {
            $data['receipt_image_path'] = $request->file('receipt_image')->store('transactions/requests', 'public');
        }

        $transaction = Transaction::create($data);
        app(\App\Services\ApprovalService::class)->initTransactionApproval($transaction);

        return redirect()
            ->route($config['route_prefix'] . '.index')
            ->with('success', 'Đã gửi ' . ($transaction->request_form_type === Transaction::REQUEST_FORM_PAYMENT ? 'phiếu đề nghị thanh toán' : 'phiếu yêu cầu thu/chi') . ' #' . $transaction->id . ' sang phòng Kế toán duyệt.');
    }

    private function printRequest(Transaction $transaction, string $source)
    {
        $config = $this->config($source);
        $this->authorizeSource($config);

        abort_unless($transaction->request_source === $source, 404);
        if ($config['own_only'] ?? false) {
            abort_unless((int) $transaction->submitted_by === (int) auth()->id(), 403);
        }

        $transaction->load([
            'submitter:id,name,email,department_id,block_id',
            'submitter.department:id,name,block_id',
            'submitter.block:id,name',
            'approver:id,name',
            'rejecter:id,name',
            'transactionCategory:id,code,name,flow_direction',
            'account:id,name,type',
            'destinationAccount:id,name,type,account_number,bank_name',
        ]);

        return view('department_finance_requests.print', [
            'config' => $config,
            'transaction' => $transaction,
        ]);
    }

    private function authorizeSource(array $config): void
    {
        $roles = explode(',', $config['role']);
        $user = auth()->user();

        abort_unless($user && collect($roles)->contains(fn ($role) => $user->hasRole(trim($role))), 403);
    }
}
