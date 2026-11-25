<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CompanyAssetController extends Controller
{
    public const TABLES = ['assets' => 'company_assets', 'partners' => 'asset_partners', 'contracts' => 'asset_contracts'];

    public const ASSET_STATUSES = ['in_use' => 'Đang sử dụng', 'in_storage' => 'Trong kho', 'maintenance' => 'Bảo trì', 'retired' => 'Ngừng sử dụng'];

    public const CONTRACT_STATUSES = ['active' => 'Đang thực hiện', 'completed' => 'Hoàn thành', 'ended' => 'Đã kết thúc'];

    public const PAYMENT_METHODS = ['transfer' => 'Chuyển khoản', 'cash' => 'Tiền mặt', 'other' => 'Khác'];

    public const CATEGORIES = [
        'equipment' => 'Máy móc và thiết bị', 'building' => 'Nhà xưởng và kho', 'office' => 'Thiết bị văn phòng',
        'deposit' => 'Tiền cọc / tạm ứng', 'improvement' => 'Cải tạo và lắp đặt', 'supplies' => 'Vật tư tiêu hao', 'other' => 'Khác',
    ];

    public const DOCUMENT_TYPES = [
        'invoice' => 'Hóa đơn', 'quote' => 'Báo giá', 'handover' => 'Biên bản bàn giao',
        'acceptance' => 'Biên bản nghiệm thu', 'contract' => 'Hợp đồng mua bán',
        'payment_request' => 'Đề nghị thanh toán', 'other' => 'Chứng từ khác',
    ];

    public const DOCUMENT_FIELDS = [
        'invoice' => ['issuer' => 'Đơn vị phát hành', 'serial' => 'Ký hiệu hóa đơn', 'tax_code' => 'Mã số thuế',
            'subtotal' => 'Tiền trước thuế (đ)', 'vat' => 'Tiền VAT (đ)', 'amount' => 'Tổng tiền (đ)'],
        'quote' => ['issuer' => 'Đơn vị báo giá', 'amount' => 'Giá báo (đ)', 'valid_until' => 'Hiệu lực đến'],
        'handover' => ['location' => 'Nơi bàn giao', 'delivered_by' => 'Bên giao', 'received_by' => 'Bên nhận'],
        'acceptance' => ['location' => 'Nơi nghiệm thu', 'result' => 'Kết quả nghiệm thu'],
        'contract' => ['issuer' => 'Đối tác', 'amount' => 'Giá trị theo bản ký (đ)', 'valid_until' => 'Hiệu lực đến'],
        'payment_request' => ['issuer' => 'Đơn vị đề nghị', 'amount' => 'Số tiền đề nghị (đ)', 'valid_until' => 'Hạn thanh toán'],
        'other' => ['issuer' => 'Đơn vị phát hành'],
    ];

    public const DOCUMENT_MONEY_FIELDS = ['subtotal', 'vat', 'amount'];

    public function create()
    {
        return view('accounting.assets.create');
    }

    public function index(Request $request)
    {
        $filters = $request->validate([
            'tab' => ['nullable', Rule::in(array_keys(self::TABLES))],
            'q' => 'nullable|string|max:150',
            'partner_id' => 'nullable|integer|min:1',
            'from' => 'nullable|date_format:Y-m-d',
            'to' => array_filter(['nullable', 'date_format:Y-m-d', $request->filled('from') ? 'after_or_equal:from' : null]),
        ]);
        $tab = $filters['tab'] ?? 'contracts';
        $search = trim($filters['q'] ?? '');
        $query = DB::table(self::TABLES[$tab].' as r')->select('r.*');

        if ($tab === 'contracts') {
            $query->join('asset_partners as p', 'p.id', '=', 'r.partner_id')
                ->addSelect('p.name as partner_name')
                ->when($search !== '', fn ($q) => $q->where(fn ($match) => $match->whereLike('r.number', "%{$search}%")
                    ->orWhereLike('r.title', "%{$search}%")->orWhereLike('p.name', "%{$search}%")))
                ->when($filters['partner_id'] ?? null, fn ($q, $partner) => $q->where('r.partner_id', $partner))
                ->when($filters['from'] ?? null, fn ($q, $from) => $q->where('r.signed_on', '>=', $from))
                ->when($filters['to'] ?? null, fn ($q, $to) => $q->where('r.signed_on', '<=', $to))
                ->orderByDesc('r.signed_on');
        } elseif ($tab === 'assets') {
            $query->leftJoin('asset_contracts as c', 'c.id', '=', 'r.contract_id')
                ->addSelect('c.number as contract_number')
                ->when($search !== '', fn ($q) => $q->where(fn ($match) => $match->whereLike('r.code', "%{$search}%")
                    ->orWhereLike('r.name', "%{$search}%")->orWhereLike('r.location', "%{$search}%")));
        } else {
            $query->when($search !== '', fn ($q) => $q->where(fn ($match) => $match->whereLike('r.name', "%{$search}%")
                ->orWhereLike('r.tax_code', "%{$search}%")->orWhereLike('r.phone', "%{$search}%")));
        }

        $rows = $query->orderByDesc('r.id')->paginate(20)->withQueryString();
        $partners = $tab === 'contracts' ? DB::table('asset_partners')->orderBy('name')->get(['id', 'name']) : collect();

        return view('accounting.assets.index', compact('tab', 'rows', 'partners', 'filters'));
    }

    public function form(Request $request, string $kind, ?int $record = null)
    {
        $row = $record === null ? null : $this->record(self::TABLES[$kind], $record);
        $partners = $kind === 'contracts' ? DB::table('asset_partners')->orderBy('name')->get(['id', 'name']) : collect();
        $contracts = $kind === 'assets' ? DB::table('asset_contracts')->orderByDesc('signed_on')->get(['id', 'number', 'title']) : collect();

        return view('accounting.assets.form', compact('kind', 'record', 'row', 'partners', 'contracts'));
    }

    public function save(Request $request, string $kind, ?int $record = null)
    {
        $table = self::TABLES[$kind];
        if ($record !== null) {
            $this->record($table, $record);
        }
        $data = $request->validate(['note' => 'nullable|string|max:4000'] + match ($kind) {
            'partners' => [
                'name' => 'required|string|max:255', 'tax_code' => 'nullable|string|max:40',
                'phone' => 'nullable|string|max:40', 'email' => 'nullable|email|max:255', 'address' => 'nullable|string|max:500',
            ],
            'contracts' => [
                'partner_id' => 'required|integer|exists:asset_partners,id',
                'number' => ['required', 'string', 'max:100', Rule::unique($table)->where('partner_id', (int) $request->input('partner_id'))->ignore($record)],
                'title' => 'required|string|max:255', 'signed_on' => 'required|date_format:Y-m-d',
                'expires_on' => 'nullable|date_format:Y-m-d|after_or_equal:signed_on',
                'value' => 'required|integer|min:0|max:999999999999999', 'status' => ['required', Rule::in(array_keys(self::CONTRACT_STATUSES))],
            ],
            'assets' => [
                'code' => ['required', 'string', 'max:100', Rule::unique($table)->ignore($record)],
                'name' => 'required|string|max:255', 'location' => 'nullable|string|max:255',
                'category' => ['nullable', Rule::in(array_keys(self::CATEGORIES))],
                'value' => 'nullable|integer|min:0|max:999999999999999',
                'contract_id' => 'nullable|integer|exists:asset_contracts,id', 'status' => ['required', Rule::in(array_keys(self::ASSET_STATUSES))],
            ],
        });
        $attachment = $record === null && $kind !== 'partners'
            ? $this->validateDocument($request, false)
            : null;
        $data['updated_at'] = now();
        if ($record === null) {
            $record = $this->withDocumentStorage(function ($disk, array &$paths) use ($data, $table, $kind, $attachment, $request) {
                $id = DB::table($table)->insertGetId($data + ['created_at' => now()]);
                if ($attachment && ! empty($attachment['files'])) {
                    $this->writeDocuments($disk, $paths, $kind === 'assets' ? 'asset_id' : 'contract_id', $id, $attachment, $request->user()->id);
                }

                return $id;
            });
        } else {
            DB::table($table)->where('id', $record)->update($data);
        }

        $url = match ($kind) {
            'contracts' => accounting_route('assets.contracts.show', ['contract' => $record]),
            'assets' => accounting_route('assets.items.show', ['asset' => $record]),
            default => accounting_route('assets.index', ['tab' => $kind]),
        };

        return redirect()->to($url)->with('success', 'Đã lưu thông tin.');
    }

    public function showAsset(int $asset)
    {
        $row = $this->record('company_assets', $asset);
        $contract = $row->contract_id ? $this->record('asset_contracts', $row->contract_id) : null;
        $documents = $this->documents('asset_id', $asset);

        return view('accounting.assets.asset', compact('row', 'contract', 'documents'));
    }

    private function documents(string $field, int $id)
    {
        return DB::table('asset_contract_documents')->where($field, $id)
            ->orderByDesc('id')->paginate(15, ['*'], 'document_page')->withQueryString()->fragment('asset-documents');
    }

    public function showContract(int $contract, ?Request $request = null)
    {
        $request ??= request();
        $selection = $request->validate([
            'edit_payment' => 'nullable|integer|min:1',
            'edit_installment' => 'nullable|integer|min:1',
            'pay_installment' => 'nullable|integer|min:1',
        ]);
        $row = DB::table('asset_contracts as c')->join('asset_partners as p', 'p.id', '=', 'c.partner_id')
            ->select('c.*', 'p.name as partner_name', 'p.phone as partner_phone', 'p.email as partner_email')
            ->where('c.id', $contract)->first();
        abort_unless($row, 404);
        $paymentQuery = $this->contractEntries('asset_contract_payments', $contract);
        $paid = (int) (clone $paymentQuery)->sum('amount');
        $allocated = (clone $paymentQuery)->whereNotNull('installment_id')->groupBy('installment_id')
            ->selectRaw('installment_id, SUM(amount) as paid')->pluck('paid', 'installment_id');
        $installments = $this->contractEntries('asset_contract_installments', $contract)
            ->orderByRaw("CASE WHEN kind = 'deposit' THEN 0 ELSE 1 END")->orderBy('due_on')->orderBy('id')->get()
            ->map(function ($entry) use ($allocated) {
                $entry->paid = (int) ($allocated[$entry->id] ?? 0);
                $entry->remaining = (int) $entry->amount - $entry->paid;

                return $entry;
            })->keyBy('id');
        $deposit = $installments->firstWhere('kind', 'deposit');
        $editInstallment = isset($selection['edit_installment']) ? $installments->get($selection['edit_installment']) : null;
        $editPayment = isset($selection['edit_payment']) ? (clone $paymentQuery)->find($selection['edit_payment']) : null;
        $payInstallment = isset($selection['pay_installment']) ? $installments->get($selection['pay_installment']) : null;
        abort_if((isset($selection['edit_installment']) && ! $editInstallment)
            || (isset($selection['edit_payment']) && ! $editPayment)
            || (isset($selection['pay_installment']) && ! $payInstallment), 404);
        $payments = $paymentQuery->orderByDesc('paid_on')->orderByDesc('id')->paginate(15, ['*'], 'payment_page')
            ->appends($request->except(['edit_payment', 'pay_installment', 'payment_page']))->fragment('asset-payments');
        $documents = $this->documents('contract_id', $contract);
        $assets = DB::table('company_assets')->where('contract_id', $contract)->orderBy('code')
            ->paginate(15, ['*'], 'asset_page')->withQueryString()->fragment('contract-assets');
        $unallocated = $paid - (int) $allocated->sum();

        return view('accounting.assets.contract', compact(
            'row', 'paid', 'payments', 'documents', 'assets', 'installments', 'deposit', 'editInstallment', 'editPayment', 'payInstallment', 'unallocated'
        ));
    }

    public function saveInstallment(Request $request, int $contract, ?int $installment = null)
    {
        $data = $request->validateWithBag('installment', [
            'kind' => 'required|in:deposit,installment',
            'label' => 'nullable|required_if:kind,installment|string|max:150',
            'amount' => 'required|integer|min:1|max:999999999999999',
            'due_on' => 'nullable|date_format:Y-m-d',
            'milestone' => 'nullable|string|max:500',
        ]);
        $data['label'] = $data['kind'] === 'deposit' ? 'Tiền cọc' : $data['label'];
        DB::transaction(function () use ($contract, $installment, $data) {
            $this->lockContract($contract);
            $entries = $this->contractEntries('asset_contract_installments', $contract);
            if ($installment !== null) {
                abort_unless((clone $entries)->find($installment), 404);
            }
            if ($data['kind'] === 'deposit' && (clone $entries)->where('kind', 'deposit')
                ->when($installment, fn ($query) => $query->where('id', '<>', $installment))->exists()) {
                throw ValidationException::withMessages(['kind' => 'Hợp đồng đã có mục Tiền cọc. Hãy cập nhật mục đó.'])->errorBag('installment');
            }
            $data['updated_at'] = now();
            if ($installment === null) {
                $entries->insert($data + ['contract_id' => $contract, 'created_at' => now()]);
            } else {
                $entries->where('id', $installment)->update($data);
            }
        });

        return $this->backToContract($contract, 'asset-installments', 'Đã lưu đợt thanh toán.');
    }

    public function storePayment(Request $request, int $contract, ?int $payment = null)
    {
        $data = $request->validateWithBag('payment', [
            'paid_on' => 'required|date_format:Y-m-d|before_or_equal:today',
            'amount' => 'required|integer|min:1|max:999999999999999',
            'method' => ['required', Rule::in(array_keys(self::PAYMENT_METHODS))],
            'installment_id' => ['nullable', 'integer', Rule::exists('asset_contract_installments', 'id')->where('contract_id', $contract)],
            'reference' => 'nullable|string|max:100', 'note' => 'nullable|string|max:4000',
        ]);
        DB::transaction(function () use ($request, $contract, $payment, $data) {
            $this->lockContract($contract);
            $entries = $this->contractEntries('asset_contract_payments', $contract);
            $data['updated_at'] = now();
            if ($payment === null) {
                $entries->insert($data + [
                    'contract_id' => $contract, 'created_by' => $request->user()->id, 'created_at' => now(),
                ]);
            } else {
                abort_unless((clone $entries)->find($payment), 404);
                $entries->where('id', $payment)->update($data);
            }
        });

        return $this->backToContract($contract, 'asset-payments', $payment === null ? 'Đã ghi nhận thanh toán.' : 'Đã cập nhật thanh toán.');
    }

    private function contractEntries(string $table, int $contract)
    {
        return DB::table($table)->where('contract_id', $contract);
    }

    private function lockContract(int $contract): void
    {
        abort_unless(DB::table('asset_contracts')->lockForUpdate()->find($contract), 404);
    }

    public function storeDocuments(Request $request, int $contract)
    {
        $this->record('asset_contracts', $contract);
        $this->attach($request, 'contract_id', $contract);

        return $this->backToContract($contract, 'asset-documents', 'Đã lưu chứng từ.');
    }

    public function storeAssetDocuments(Request $request, int $asset)
    {
        $this->record('company_assets', $asset);
        $this->attach($request, 'asset_id', $asset);

        return redirect()->to(accounting_route('assets.items.show', ['asset' => $asset]).'#asset-documents')->with('success', 'Đã lưu chứng từ.');
    }

    private function attach(Request $request, string $field, int $id): void
    {
        $data = $this->validateDocument($request, true);
        $this->withDocumentStorage(fn ($disk, array &$paths) => $this->writeDocuments($disk, $paths, $field, $id, $data, $request->user()->id));
    }

    private function validateDocument(Request $request, bool $required): array
    {
        if ($required && ! $request->filled('document_title') && $request->filled('title')) {
            $request->merge(['document_title' => $request->input('title')]);
        }
        $filesRequired = $required || $request->anyFilled(['document_type', 'document_title', 'document_number', 'issued_on']);
        $data = $request->validate([
            'document_type' => ['nullable', Rule::in(array_keys(self::DOCUMENT_TYPES))],
            'document_title' => 'nullable|string|max:150',
            'document_number' => 'nullable|string|max:100',
            'issued_on' => 'nullable|date_format:Y-m-d',
            'document_details' => 'nullable|array',
            'files' => [Rule::requiredIf($filesRequired), 'nullable', 'array', 'list', 'min:1', 'max:10'],
            'files.*' => 'required|file|mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx|max:10240',
        ]);
        $type = $data['document_type'] ?? 'other';
        $rules = [];
        foreach (self::DOCUMENT_FIELDS[$type] as $key => $label) {
            $rules['document_details.'.$key] = match (true) {
                in_array($key, self::DOCUMENT_MONEY_FIELDS, true) => 'nullable|integer|min:0|max:999999999999999',
                $key === 'valid_until' => 'nullable|date_format:Y-m-d',
                default => 'nullable|string|max:500',
            };
        }
        $specific = $request->validate($rules);
        $data['document_type'] = $type;
        $data['document_details'] = array_filter($specific['document_details'] ?? [], fn ($value) => $value !== null && $value !== '');

        return $data;
    }

    private function withDocumentStorage(callable $operation): mixed
    {
        $disk = Storage::disk('local');
        $paths = [];
        try {
            return DB::transaction(function () use ($operation, $disk, &$paths) {
                return $operation($disk, $paths);
            });
        } catch (\Throwable $exception) {
            $disk->delete($paths);
            throw $exception;
        }
    }

    private function writeDocuments($disk, array &$paths, string $field, int $id, array $data, int $actor): void
    {
        $documents = [];
        $directory = $field === 'asset_id' ? 'company-assets' : 'asset-contracts';
        foreach ($data['files'] as $file) {
            $path = $disk->putFileAs("{$directory}/{$id}", $file, Str::uuid().'.'.$file->extension(), 'private');
            if (! $path) {
                throw new \RuntimeException('Không thể lưu chứng từ.');
            }
            $paths[] = $path;
            $name = preg_replace('/[\x00-\x1f\x7f]/u', '', basename(str_replace('\\', '/', $file->getClientOriginalName())));
            $documents[] = [
                $field => $id, 'title' => $data['document_title'] ?? null, 'path' => $path,
                'document_type' => $data['document_type'], 'document_number' => $data['document_number'] ?? null,
                'issued_on' => $data['issued_on'] ?? null,
                'details' => json_encode($data['document_details'], JSON_THROW_ON_ERROR),
                'name' => Str::limit($name ?: 'Chứng từ', 200, ''), 'size' => $file->getSize(),
                'uploaded_by' => $actor, 'created_at' => now(), 'updated_at' => now(),
            ];
        }
        DB::table('asset_contract_documents')->insert($documents);
    }

    public function downloadDocument(int $contract, int $document)
    {
        return $this->download('contract_id', $contract, $document);
    }

    public function downloadAssetDocument(int $asset, int $document)
    {
        return $this->download('asset_id', $asset, $document);
    }

    private function download(string $field, int $id, int $document)
    {
        $file = DB::table('asset_contract_documents')->where($field, $id)->where('id', $document)->first();
        $disk = Storage::disk('local');
        abort_unless($file && $disk->exists($file->path), 404);

        return $disk->download($file->path, $file->name, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    private function record(string $table, int $id): object
    {
        $row = DB::table($table)->where('id', $id)->first();
        abort_unless($row, 404);

        return $row;
    }

    private function backToContract(int $contract, string $anchor, string $message)
    {
        return redirect()->to(accounting_route('assets.contracts.show', ['contract' => $contract]).'#'.$anchor)->with('success', $message);
    }
}
