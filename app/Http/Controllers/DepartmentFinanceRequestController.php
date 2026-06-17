<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionCategory;
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
            ])
            ->where('request_source', $source)
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(20)
            ->appends($request->query());

        $categories = TransactionCategory::active()
            ->orderBy('flow_direction')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'flow_direction']);

        return view('department_finance_requests.index', [
            'config' => $config,
            'source' => $source,
            'requests' => $requests,
            'categories' => $categories,
            'status' => $status,
            'settings' => $settings,
        ]);
    }

    private function store(Request $request, string $source)
    {
        $config = $this->config($source);
        $this->authorizeSource($config);

        $rawAmount = str_replace(['.', ',', ' '], '', (string) $request->input('amount', ''));
        $request->merge(['amount' => $rawAmount]);

        $validated = $request->validate([
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
            'note' => ['required', 'string', 'max:1000'],
            'receipt_image' => ['nullable', 'image', 'max:5120'],
        ]);

        $category = TransactionCategory::query()
            ->whereKey((int) $validated['transaction_category_id'])
            ->where('flow_direction', $validated['flow_direction'])
            ->firstOrFail();

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
            'account_id' => null,
            'method' => $validated['method'] ?? null,
            'note' => $validated['note'],
            'status' => Transaction::STATUS_PENDING_APPROVAL,
            'submitted_by' => auth()->id(),
            'request_source' => $source,
            'request_department' => $config['label'],
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
            ->with('success', 'Đã gửi phiếu yêu cầu #' . $transaction->id . ' sang phòng Kế toán duyệt.');
    }

    private function printRequest(Transaction $transaction, string $source)
    {
        $config = $this->config($source);
        $this->authorizeSource($config);

        abort_unless($transaction->request_source === $source, 404);

        $transaction->load([
            'submitter:id,name,email',
            'approver:id,name',
            'rejecter:id,name',
            'transactionCategory:id,code,name,flow_direction',
            'account:id,name,type',
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
