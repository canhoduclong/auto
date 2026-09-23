<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DepartmentFinanceRequestController extends Controller
{
    private const SOURCES = [
        'warehouse' => [
            'label' => 'Kho',
            'layout' => 'layouts.warehouse',
            'route_prefix' => 'warehouse.finance-requests',
            'role' => 'warehouse,admin',
        ],
        'package' => [
            'label' => 'Đóng hàng',
            'layout' => 'layouts.package',
            'route_prefix' => 'package.finance-requests',
            'role' => 'package,admin',
            'own_only' => true,
        ],
        'accounting' => [
            'label' => 'Kế toán',
            'layout' => 'layouts.accounting',
            'route_prefix' => 'accounting.finance-requests',
            'role' => 'account,accountant,accounting,admin',
            'own_only' => true,
        ],
        'ceo' => [
            'label' => 'CEO',
            'layout' => 'layouts.ceo',
            'route_prefix' => 'ceo.finance-requests',
            'role' => 'ceo,CEO,admin',
        ],
        'director' => [
            'label' => 'Director',
            'layout' => 'layouts.director',
            'route_prefix' => 'director.finance-requests',
            'role' => 'director,Director,admin',
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
            'role' => 'manager,manager_sale,admin',
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

    public function packageIndex(Request $request) { return $this->index($request, 'package'); }
    public function packageStore(Request $request) { return $this->store($request, 'package'); }
    public function packagePrint(Transaction $transaction) { return $this->printRequest($transaction, 'package'); }

    public function accountingIndex(Request $request) { return $this->index($request, 'accounting'); }
    public function accountingStore(Request $request) { return $this->store($request, 'accounting'); }
    public function accountingPrint(Transaction $transaction) { return $this->printRequest($transaction, 'accounting'); }

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

    public function directorIndex(Request $request)
    {
        return $this->index($request, 'director');
    }

    public function directorStore(Request $request)
    {
        return $this->store($request, 'director');
    }

    public function directorPrint(Transaction $transaction)
    {
        return $this->printRequest($transaction, 'director');
    }

    public function leaderIndex(Request $request)
    {
        return $this->index($request, 'leader');
    }

    public function leaderStore(Request $request)
    {
        return $this->store($request, 'leader');
    }

    public function leaderEdit(Request $request, Transaction $transaction)
    {
        $this->authorizeLeaderEdit($transaction);

        return $this->index($request, 'leader', $transaction);
    }

    public function leaderUpdate(Request $request, Transaction $transaction)
    {
        $this->authorizeLeaderEdit($transaction);
        $isResubmission = $transaction->status === Transaction::STATUS_REJECTED;
        $data = $this->validatedRequestData($request, 'leader');
        $removedAttachmentPaths = $data['_removed_attachment_paths'] ?? [];
        unset($data['_removed_attachment_paths']);
        unset($data['status'], $data['submitted_by'], $data['request_source'], $data['request_department']);

        DB::transaction(function () use ($transaction, $data, $isResubmission): void {
            if ($isResubmission) {
                $data = array_merge($data, [
                    'status' => Transaction::STATUS_PENDING_APPROVAL,
                    'approved_by' => null,
                    'approved_at' => null,
                    'rejected_by' => null,
                    'rejected_at' => null,
                    'reject_reason' => null,
                ]);
                $transaction->approvalSteps()->delete();
            }

            $transaction->update($data);

            if ($isResubmission) {
                app(\App\Services\ApprovalService::class)->initTransactionApproval($transaction);
            }
        });
        $this->deleteUnreferencedRequestAttachments($removedAttachmentPaths, $transaction->id);

        return redirect()
            ->route('leader.finance-requests.index')
            ->with('success', $isResubmission
                ? 'Đã cập nhật và gửi lại phiếu yêu cầu #' . $transaction->id . ' vào luồng duyệt.'
                : 'Đã cập nhật phiếu yêu cầu #' . $transaction->id . '.');
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

    public function managerCreate(Request $request)
    {
        return $this->index($request, 'manager', null, true);
    }

    public function managerDuplicate(Request $request, Transaction $transaction)
    {
        $config = $this->config('manager');
        $this->authorizeSource($config);
        abort_unless($transaction->request_source === 'manager', 404);

        $duplicate = DB::transaction(function () use ($transaction, $request, $config): Transaction {
            $copy = Transaction::query()->create([
                'amount' => $transaction->amount,
                'type' => $transaction->type,
                'destination_type' => $transaction->destination_type,
                'destination_account_id' => $transaction->destination_account_id,
                'external_recipient' => $transaction->external_recipient,
                'external_account_number' => $transaction->external_account_number,
                'external_bank_name' => $transaction->external_bank_name,
                'external_bank_branch' => $transaction->external_bank_branch,
                'method' => $transaction->method,
                'note' => $transaction->note,
                'receipt_image_path' => $transaction->receipt_image_path,
                'request_attachments' => $transaction->request_attachments,
                'status' => Transaction::STATUS_PENDING_APPROVAL,
                'submitted_by' => $request->user()->id,
                'request_source' => 'manager',
                'request_department' => $config['label'],
                'request_job_title' => trim((string) ($request->user()->job_title ?: $config['label'])),
                'request_form_type' => $transaction->request_form_type,
                'request_document_title' => $transaction->request_document_title,
                'request_title' => $transaction->request_title,
                'request_items' => $transaction->request_items,
                'request_subtotal' => $transaction->request_subtotal,
                'request_vat' => $transaction->request_vat,
                'request_total' => $transaction->request_total,
            ]);

            app(\App\Services\ApprovalService::class)->initTransactionApproval($copy);

            return $copy;
        });

        return redirect()
            ->route('manager.finance-requests.index')
            ->with('success', 'Đã nhân bản phiếu #'.$transaction->id.' thành phiếu #'.$duplicate->id.' và gửi vào luồng duyệt mới.');
    }

    public function managerEdit(Request $request, Transaction $transaction)
    {
        $this->authorizeManagerMutation($transaction);

        return $this->index($request, 'manager', $transaction, true);
    }

    public function managerUpdate(Request $request, Transaction $transaction)
    {
        $this->authorizeManagerMutation($transaction);
        $data = $this->validatedRequestData($request, 'manager');
        $removedAttachmentPaths = $data['_removed_attachment_paths'] ?? [];
        unset($data['_removed_attachment_paths']);
        unset($data['status'], $data['submitted_by'], $data['request_source'], $data['request_department']);

        DB::transaction(function () use ($transaction, $data): void {
            $transaction->approvalSteps()->delete();
            $transaction->update(array_merge($data, [
                'status' => Transaction::STATUS_PENDING_APPROVAL,
                'approved_by' => null,
                'approved_at' => null,
                'rejected_by' => null,
                'rejected_at' => null,
                'reject_reason' => null,
            ]));
            app(\App\Services\ApprovalService::class)->initTransactionApproval($transaction);
        });
        $this->deleteUnreferencedRequestAttachments($removedAttachmentPaths, $transaction->id);

        return redirect()->route('manager.finance-requests.index')
            ->with('success', 'Đã cập nhật phiếu #'.$transaction->id.' và khởi tạo lại luồng duyệt.');
    }

    public function managerDestroy(Transaction $transaction)
    {
        $this->authorizeManagerMutation($transaction);
        $id = $transaction->id;

        DB::transaction(function () use ($transaction): void {
            $transaction->approvalSteps()->delete();
            $transaction->delete();
        });

        return redirect()->route('manager.finance-requests.index')
            ->with('success', 'Đã xóa phiếu yêu cầu #'.$id.'.');
    }

    public function managerPrint(Transaction $transaction)
    {
        return $this->printRequest($transaction, 'manager');
    }

    public function adminIndex(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403);
        $source = array_key_exists((string) $request->input('source'), self::SOURCES) ? (string) $request->input('source') : 'all';
        $status = in_array($request->input('status'), ['all', Transaction::STATUS_PENDING_APPROVAL, Transaction::STATUS_APPROVED_PENDING_COMPLETION, Transaction::STATUS_APPROVED, Transaction::STATUS_REJECTED], true)
            ? $request->input('status') : 'all';
        $search = trim((string) $request->input('search'));

        $requests = Transaction::query()
            ->with(['submitter:id,name,email', 'submitter.roles:id,name', 'approver:id,name', 'rejecter:id,name'])
            ->whereNotNull('request_source')
            ->when($source !== 'all', fn ($query) => $query->where('request_source', $source))
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nested) use ($search): void {
                    $nested->where('id', ctype_digit($search) ? (int) $search : 0)
                        ->orWhere('request_title', 'like', "%{$search}%")
                        ->orWhere('request_department', 'like', "%{$search}%")
                        ->orWhereHas('submitter', fn ($user) => $user->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(25)
            ->appends($request->query());

        return view('admin.finance_requests.index', [
            'requests' => $requests,
            'sources' => collect(self::SOURCES)->map(fn ($config) => $config['label'])->all(),
            'source' => $source,
            'status' => $status,
            'search' => $search,
        ]);
    }

    public function adminEdit(Request $request, Transaction $transaction)
    {
        abort_unless($request->user()?->isAdmin(), 403);
        abort_unless($transaction->request_source, 404);
        $transaction->load(['submitter.roles:id,name']);

        return view('admin.finance_requests.edit', [
            'transaction' => $transaction,
            'sources' => collect(self::SOURCES)->map(fn ($config) => $config['label'])->all(),
            'sourceConfigs' => self::SOURCES,
            'accounts' => Account::active()->orderBy('name')->get(),
        ]);
    }

    public function adminUpdate(Request $request, Transaction $transaction)
    {
        abort_unless($request->user()?->isAdmin(), 403);
        abort_unless($transaction->request_source, 404);

        $validated = $request->validate([
            'request_source' => ['required', 'in:'.implode(',', array_keys(self::SOURCES))],
            'request_department' => ['required', 'string', 'max:150'],
            'request_job_title' => ['nullable', 'string', 'max:150'],
            'request_form_type' => ['required', 'in:'.Transaction::REQUEST_FORM_CASH.','.Transaction::REQUEST_FORM_PAYMENT],
            'request_document_title' => ['nullable', 'string', 'max:255'],
            'request_document_title_custom' => ['nullable', 'required_if:request_document_title,__custom__', 'string', 'max:255'],
            'flow_direction' => ['required', 'in:in,out'],
            'request_title' => ['required', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.content' => ['required', 'string', 'max:255'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'request_vat' => ['nullable', 'numeric', 'min:0'],
            'method' => ['required', 'in:cash,managed_transfer,bank_transfer'],
            'destination_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'external_recipient' => ['nullable', 'string', 'max:255'],
            'external_account_number' => ['nullable', 'string', 'max:100'],
            'external_bank_name' => ['nullable', 'string', 'max:150'],
            'external_bank_branch' => ['nullable', 'string', 'max:150'],
            'note' => ['required', 'string', 'max:1000'],
        ]);

        $flow = $validated['request_form_type'] === Transaction::REQUEST_FORM_PAYMENT ? 'out' : $validated['flow_direction'];
        $items = collect($validated['items'])->values()->map(function (array $item, int $index): array {
            $quantity = (float) $item['quantity'];
            $unitPrice = (float) $item['unit_price'];
            return ['stt' => $index + 1, 'content' => trim($item['content']), 'unit' => trim((string) ($item['unit'] ?? '')), 'quantity' => $quantity, 'unit_price' => $unitPrice, 'line_total' => round($quantity * $unitPrice, 2)];
        });
        $subtotal = round((float) $items->sum('line_total'), 2);
        $vat = round((float) ($validated['request_vat'] ?? 0), 2);
        $total = round($subtotal + $vat, 2);

        $transaction->update([
            'request_source' => $validated['request_source'],
            'request_department' => trim($validated['request_department']),
            'request_job_title' => trim((string) (($validated['request_job_title'] ?? null)
                ?: $transaction->request_job_title
                ?: $transaction->submitter?->job_title
                ?: $validated['request_department'])),
            'request_form_type' => $validated['request_form_type'],
            'request_document_title' => trim((string) (($validated['request_document_title'] ?? null) === '__custom__'
                ? $validated['request_document_title_custom']
                : (($validated['request_document_title'] ?? null)
                    ?: ($validated['request_form_type'] === Transaction::REQUEST_FORM_PAYMENT ? 'Phiếu đề nghị thanh toán' : 'Phiếu yêu cầu')))),
            'request_title' => trim($validated['request_title']),
            'request_items' => $items->all(),
            'request_subtotal' => $subtotal,
            'request_vat' => $vat,
            'request_total' => $total,
            'amount' => $total,
            'type' => $flow === 'in' ? 'extra_income' : 'extra_expense',
            'method' => $validated['method'],
            'destination_type' => match ($validated['method']) { 'cash' => 'cash', 'managed_transfer' => 'internal', default => 'external' },
            'destination_account_id' => $validated['method'] === 'managed_transfer' ? ($validated['destination_account_id'] ?? null) : null,
            'external_recipient' => $validated['method'] === 'bank_transfer' ? trim((string) ($validated['external_recipient'] ?? '')) : null,
            'external_account_number' => $validated['method'] === 'bank_transfer' ? trim((string) ($validated['external_account_number'] ?? '')) : null,
            'external_bank_name' => $validated['method'] === 'bank_transfer' ? trim((string) ($validated['external_bank_name'] ?? '')) : null,
            'external_bank_branch' => $validated['method'] === 'bank_transfer' ? trim((string) ($validated['external_bank_branch'] ?? '')) : null,
            'note' => trim($validated['note']),
        ]);

        return redirect()->route('admin.accounting.finance-requests.index')->with('success', 'Đã cập nhật phiếu yêu cầu #'.$transaction->id.'. Trạng thái và lịch sử duyệt được giữ nguyên.');
    }

    public function adminPrint(Request $request, Transaction $transaction)
    {
        abort_unless($request->user()?->isAdmin(), 403);
        abort_unless($transaction->request_source, 404);
        $source = array_key_exists($transaction->request_source, self::SOURCES) ? $transaction->request_source : 'accounting';

        return $this->printRequest($transaction, $source);
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

    private function index(Request $request, string $source, ?Transaction $editingRequest = null, bool $showCreateForm = false)
    {
        $settings = $this->settings;
        

        $config = $this->config($source);
        $this->authorizeSource($config);

        $status = in_array($request->input('status'), ['all', Transaction::STATUS_PENDING_APPROVAL, Transaction::STATUS_APPROVED_PENDING_COMPLETION, Transaction::STATUS_APPROVED, Transaction::STATUS_REJECTED], true)
            ? $request->input('status')
            : 'all';
        $formType = in_array($request->input('form_type'), ['all', Transaction::REQUEST_FORM_CASH, Transaction::REQUEST_FORM_PAYMENT], true)
            ? $request->input('form_type')
            : 'all';
        $search = trim((string) $request->input('search'));

        $requests = Transaction::query()
            ->with([
                'submitter:id,name,email,job_title,department_id,block_id',
                'submitter.department:id,name,block_id',
                'submitter.department.block:id,name',
                'submitter.block:id,name',
                'approver:id,name',
                'rejecter:id,name',
                'transactionCategory:id,code,name,flow_direction',
                'account:id,name,type',
                'destinationAccount:id,name,type,owner_name,account_number,bank_name',
                'approvalSteps:id,transaction_id,status,approved_by',
            ])
            ->where('request_source', $source)
            ->when($config['own_only'] ?? false, fn ($query) => $query->where('submitted_by', auth()->id()))
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($formType !== 'all', fn ($query) => $query->where('request_form_type', $formType))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nested) use ($search): void {
                    $nested->where('id', ctype_digit($search) ? (int) $search : 0)
                        ->orWhere('request_title', 'like', "%{$search}%")
                        ->orWhere('note', 'like', "%{$search}%")
                        ->orWhereHas('submitter', fn ($user) => $user->where('name', 'like', "%{$search}%"));
                });
            })
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
            'search' => $search,
            'settings' => $settings,
            'accounts' => Account::active()->orderBy('name')->get(['id', 'name', 'type', 'account_number', 'bank_name']),
            'managedAccounts' => $managedAccounts,
            'defaultManagedAccountId' => $defaultManagedAccountId,
            'editingRequest' => $editingRequest,
            'showCreateForm' => $showCreateForm,
        ]);
    }

    private function store(Request $request, string $source)
    {
        $config = $this->config($source);
        $this->authorizeSource($config);

        $data = $this->validatedRequestData($request, $source);
        unset($data['_removed_attachment_paths']);

        $transaction = Transaction::create($data);
        app(\App\Services\ApprovalService::class)->initTransactionApproval($transaction);

        return redirect()
            ->route($config['route_prefix'] . '.index')
            ->with('success', 'Đã gửi ' . ($transaction->request_form_type === Transaction::REQUEST_FORM_PAYMENT ? 'phiếu đề nghị thanh toán' : 'phiếu yêu cầu thu/chi') . ' #' . $transaction->id . ' vào luồng Kế toán xác nhận → Director duyệt → Kế toán hoàn thành.');
    }

    private function validatedRequestData(Request $request, string $source): array
    {
        $config = $this->config($source);

        $validated = $request->validate([
            'request_form_type' => ['required', 'in:' . Transaction::REQUEST_FORM_CASH . ',' . Transaction::REQUEST_FORM_PAYMENT],
            'request_document_title' => ['nullable', 'string', 'max:255'],
            'request_document_title_custom' => ['nullable', 'required_if:request_document_title,__custom__', 'string', 'max:255'],
            'flow_direction' => ['required', 'in:in,out'],
            'request_title' => ['required', 'string', 'max:255'],
            'request_job_title' => ['nullable', 'string', 'max:150'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.content' => ['required', 'string', 'max:255'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'request_vat' => ['nullable', 'numeric', 'min:0'],
            'method' => ['required', 'in:cash,managed_transfer,bank_transfer'],
            'destination_account_id' => ['nullable', 'required_if:method,managed_transfer', 'integer', 'exists:accounts,id'],
            'external_recipient' => ['nullable', 'required_if:method,bank_transfer', 'string', 'max:255'],
            'external_account_number' => ['nullable', 'required_if:method,bank_transfer', 'string', 'max:100'],
            'external_bank_name' => ['nullable', 'required_if:method,bank_transfer', 'string', 'max:150'],
            'external_bank_branch' => ['nullable', 'string', 'max:150'],
            'note' => ['required', 'string', 'max:1000'],
            'receipt_image' => ['nullable', 'image', 'max:5120'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx', 'max:20480'],
            'remove_attachments' => ['nullable', 'array'],
            'remove_attachments.*' => ['integer', 'min:0', 'distinct'],
        ]);

        if ($validated['request_form_type'] === Transaction::REQUEST_FORM_PAYMENT) {
            $validated['flow_direction'] = 'out';
        }

        $managedAccountIds = auth()->user()->managedAccounts()
            ->where('accounts.is_active', true)
            ->pluck('accounts.id')
            ->map(fn ($id) => (int) $id);
        $destinationAccountId = (int) ($validated['destination_account_id'] ?? 0);

        if ($validated['method'] === 'managed_transfer') {
            if ($destinationAccountId === 0 || !$managedAccountIds->contains($destinationAccountId)) {
                throw ValidationException::withMessages([
                    'destination_account_id' => 'Vui lòng chọn tài khoản chuyển khoản trong danh sách tài khoản bạn đang quản lý.',
                ]);
            }
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
            'type' => $validated['flow_direction'] === 'in' ? 'extra_income' : 'extra_expense',
            'transaction_category_id' => null,
            'account_id' => null,
            'destination_type' => match ($validated['method']) {
                'cash' => 'cash',
                'managed_transfer' => 'internal',
                default => 'external',
            },
            'destination_account_id' => $validated['method'] === 'managed_transfer'
                ? $destinationAccountId
                : null,
            'external_recipient' => $validated['method'] === 'bank_transfer'
                ? trim((string) $validated['external_recipient'])
                : null,
            'external_account_number' => $validated['method'] === 'bank_transfer'
                ? trim((string) $validated['external_account_number'])
                : null,
            'external_bank_name' => $validated['method'] === 'bank_transfer'
                ? trim((string) $validated['external_bank_name'])
                : null,
            'external_bank_branch' => $validated['method'] === 'bank_transfer'
                ? trim((string) ($validated['external_bank_branch'] ?? ''))
                : null,
            'method' => $validated['method'],
            'note' => $validated['note'],
            'status' => Transaction::STATUS_PENDING_APPROVAL,
            'submitted_by' => auth()->id(),
            'request_source' => $source,
            'request_department' => $config['label'],
            'request_job_title' => trim((string) (($validated['request_job_title'] ?? null) ?: auth()->user()?->job_title ?: $config['label'])),
            'request_form_type' => $validated['request_form_type'],
            'request_document_title' => trim((string) (($validated['request_document_title'] ?? null) === '__custom__'
                ? $validated['request_document_title_custom']
                : (($validated['request_document_title'] ?? null)
                    ?: ($validated['request_form_type'] === Transaction::REQUEST_FORM_PAYMENT ? 'Phiếu đề nghị thanh toán' : 'Phiếu yêu cầu')))),
            'request_title' => $validated['request_title'],
            'request_items' => $items->all(),
            'request_subtotal' => $subtotal,
            'request_vat' => $vat,
            'request_total' => $total,
        ];

        if ($request->hasFile('receipt_image')) {
            $data['receipt_image_path'] = $request->file('receipt_image')->store('transactions/requests', 'public');
        }

        $editingTransaction = $request->route('transaction');
        $editingTransaction = $editingTransaction instanceof Transaction ? $editingTransaction : null;
        $existingAttachments = collect($editingTransaction?->request_attachments ?: [])->values();
        $removeIndexes = collect($validated['remove_attachments'] ?? [])->map(fn ($index) => (int) $index)->unique();
        $removedAttachments = $existingAttachments->filter(fn ($attachment, $index) => $removeIndexes->contains($index));
        $retainedAttachments = $existingAttachments->reject(fn ($attachment, $index) => $removeIndexes->contains($index))->values();

        if ($editingTransaction || $request->hasFile('attachments')) {
            $storedAttachments = $retainedAttachments->all();
            $newAttachments = [];

            try {
                foreach ($request->file('attachments', []) as $file) {
                    if (! $file || ! $file->isValid()) {
                        continue;
                    }

                    $path = $file->store('transactions/request-documents', 'public');
                    $attachment = [
                        'name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'mime_type' => $file->getClientMimeType(),
                        'size' => $file->getSize(),
                    ];
                    $storedAttachments[] = $attachment;
                    $newAttachments[] = $attachment;
                }
            } catch (\Throwable $exception) {
                foreach ($newAttachments as $attachment) {
                    Storage::disk('public')->delete($attachment['path']);
                }

                throw $exception;
            }

            if (count($storedAttachments) > 10) {
                foreach ($newAttachments as $attachment) {
                    Storage::disk('public')->delete($attachment['path']);
                }
                throw ValidationException::withMessages([
                    'attachments' => 'Mỗi phiếu chỉ được lưu tối đa 10 chứng từ.',
                ]);
            }

            $data['request_attachments'] = $storedAttachments;
            $data['_removed_attachment_paths'] = $removedAttachments->pluck('path')->filter()->values()->all();
        }

        return $data;
    }

    private function authorizeLeaderEdit(Transaction $transaction): void
    {
        $config = $this->config('leader');
        $this->authorizeSource($config);

        abort_unless($transaction->request_source === 'leader', 404);
        abort_unless(in_array($transaction->status, [Transaction::STATUS_PENDING_APPROVAL, Transaction::STATUS_REJECTED], true), 403);
        if ($transaction->status === Transaction::STATUS_PENDING_APPROVAL) {
            abort_unless(
                $transaction->approvalSteps()
                    ->where(fn ($query) => $query->whereNotNull('approved_by')->orWhere('status', '!=', 'pending'))
                    ->doesntExist(),
                403
            );
        }

        $user = auth()->user();
        abort_unless(
            $user->hasRole('admin') || (int) $transaction->submitted_by === (int) $user->id,
            403
        );
    }

    private function deleteUnreferencedRequestAttachments(array $paths, int $excludingTransactionId): void
    {
        foreach (array_unique(array_filter($paths)) as $path) {
            $isStillReferenced = Transaction::query()
                ->whereKeyNot($excludingTransactionId)
                ->whereNotNull('request_attachments')
                ->get(['request_attachments'])
                ->contains(fn (Transaction $transaction) => collect($transaction->request_attachments ?: [])
                    ->contains(fn ($attachment) => ($attachment['path'] ?? null) === $path));

            if (! $isStillReferenced) {
                Storage::disk('public')->delete($path);
            }
        }
    }

    private function authorizeManagerMutation(Transaction $transaction): void
    {
        $this->authorizeSource($this->config('manager'));
        abort_unless($transaction->request_source === 'manager', 404);
        abort_unless(in_array($transaction->status, [Transaction::STATUS_PENDING_APPROVAL, Transaction::STATUS_REJECTED], true), 403, 'Chỉ được sửa hoặc xóa phiếu chờ duyệt hay bị từ chối.');
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
            'submitter:id,name,email,job_title,department_id,block_id',
            'submitter.department:id,name,block_id',
            'submitter.block:id,name',
            'approver:id,name',
            'rejecter:id,name',
            'transactionCategory:id,code,name,flow_direction',
            'account:id,name,type',
            'destinationAccount:id,name,type,owner_name,account_number,bank_name',
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
