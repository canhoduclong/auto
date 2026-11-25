<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CompanyAssetAssistantController extends Controller
{
    private const SEARCH = [
        'assets' => ['company_assets', ['id', 'code', 'name', 'category', 'value', 'contract_id'], ['code', 'name']],
        'partners' => ['asset_partners', ['id', 'name', 'tax_code'], ['name', 'tax_code']],
        'contracts' => ['asset_contracts', ['id', 'number', 'title', 'partner_id', 'signed_on', 'value'], ['number', 'title']],
        'documents' => ['asset_contract_documents', ['id', 'title', 'document_type', 'document_number', 'issued_on', 'asset_id', 'contract_id'], ['document_number', 'title']],
    ];

    private const PROPOSED_FIELDS = [
        'name' => 'nullable|string|max:255', 'code' => 'nullable|string|max:100',
        'number' => 'nullable|string|max:100', 'title' => 'nullable|string|max:255',
        'partner_id' => 'nullable|integer|exists:asset_partners,id',
        'contract_id' => 'nullable|integer|exists:asset_contracts,id',
        'signed_on' => 'nullable|date_format:Y-m-d', 'expires_on' => 'nullable|date_format:Y-m-d',
        'category' => 'nullable|string|max:24', 'location' => 'nullable|string|max:255',
        'value' => 'nullable|integer|min:0|max:999999999999999', 'tax_code' => 'nullable|string|max:40',
        'document_type' => 'nullable|string|max:24', 'document_number' => 'nullable|string|max:100',
        'issued_on' => 'nullable|date_format:Y-m-d', 'issuer' => 'nullable|string|max:255',
        'serial' => 'nullable|string|max:100', 'subtotal' => 'nullable|integer|min:0|max:999999999999999',
        'vat' => 'nullable|integer|min:0|max:999999999999999', 'amount' => 'nullable|integer|min:0|max:999999999999999',
        'valid_until' => 'nullable|date_format:Y-m-d', 'delivered_by' => 'nullable|string|max:255',
        'received_by' => 'nullable|string|max:255', 'result' => 'nullable|string|max:500',
        'note' => 'nullable|string|max:2000',
    ];

    public function search(Request $request)
    {
        if (is_string($request->input('q'))) {
            $request->merge(['q' => trim($request->input('q'))]);
        }
        $filters = $request->validate(['q' => 'required|string|min:2|max:150', 'kind' => ['nullable', Rule::in(array_keys(self::SEARCH))]]);
        $results = [];
        $search = '%'.$filters['q'].'%';
        foreach (self::SEARCH as $kind => [$table, $columns, $fields]) {
            if (! isset($filters['kind']) || $filters['kind'] === $kind) {
                $results[$kind] = DB::table($table)->where(function ($query) use ($fields, $search) {
                    foreach ($fields as $field) {
                        $query->orWhereLike($field, $search);
                    }
                })->orderByDesc('id')->limit(10)->get($columns);
            }
        }

        return response()->json(['results' => $results]);
    }

    public function storeDraft(Request $request)
    {
        $rules = [];
        foreach (self::PROPOSED_FIELDS as $field => $rule) {
            $rules['proposed.'.$field] = $rule;
        }
        $rules['proposed.category'] = ['nullable', Rule::in(array_keys(CompanyAssetController::CATEGORIES))];
        $rules['proposed.document_type'] = ['nullable', Rule::in(array_keys(CompanyAssetController::DOCUMENT_TYPES))];
        $data = $request->validate([
            'request_id' => 'required|uuid',
            'operation' => 'required|in:create,update,add_document',
            'kind' => 'required|in:assets,partners,contracts',
            'target_id' => ['nullable', 'integer', 'min:1', Rule::requiredIf(in_array($request->input('operation'), ['update', 'add_document'], true)),
                Rule::prohibitedIf($request->input('operation') === 'create')],
            'title' => 'required|string|max:150',
            'proposed' => 'required|array:'.implode(',', array_keys(self::PROPOSED_FIELDS)).'|min:1',
            'source_name' => 'nullable|string|max:200',
            'review_notes' => 'nullable|string|max:2500',
        ] + $rules);
        if ($data['operation'] === 'add_document' && $data['kind'] === 'partners') {
            throw ValidationException::withMessages(['kind' => 'Chứng từ cần gắn với tài sản hoặc hợp đồng.']);
        }
        if (isset($data['target_id']) && ! DB::table(self::SEARCH[$data['kind']][0])->where('id', $data['target_id'])->exists()) {
            throw ValidationException::withMessages(['target_id' => 'Hồ sơ đích không tồn tại trong danh mục đã chọn.']);
        }

        $keyId = $request->attributes->get('asset_assistant_key');
        ksort($data['proposed']);
        $record = [
            'operation' => $data['operation'], 'kind' => $data['kind'], 'target_id' => $data['target_id'] ?? null,
            'title' => $data['title'], 'proposed' => json_encode($data['proposed'], JSON_THROW_ON_ERROR),
            'source_name' => $data['source_name'] ?? null, 'review_notes' => $data['review_notes'] ?? null,
        ];
        [$id, $created] = DB::transaction(function () use ($keyId, $data, $record) {
            abort_unless(DB::table('asset_assistant_keys')->whereNull('revoked_at')->lockForUpdate()->find($keyId), 401);
            $previous = DB::table('asset_assistant_drafts')->where('key_id', $keyId)->where('request_id', $data['request_id'])->first();
            if ($previous) {
                // JSON storage may normalize key order and whitespace.
                $existing = (array) $previous;
                $existing['proposed'] = json_decode($existing['proposed'], true, flags: JSON_THROW_ON_ERROR);
                ksort($existing['proposed']);
                $same = array_intersect_key($existing, $record) == array_replace($record, ['proposed' => $data['proposed']]);
                abort_unless($same, 409, 'Mã gửi đã được dùng cho nội dung khác.');

                return [$previous->id, false];
            }

            $id = DB::table('asset_assistant_drafts')->insertGetId($record + [
                'key_id' => $keyId, 'request_id' => $data['request_id'], 'created_at' => now(), 'updated_at' => now(),
            ]);

            return [$id, true];
        });

        return response()->json(['id' => $id, 'status' => 'draft', 'review_url' => route('accounting.assets.ai.show', ['draft' => $id])], $created ? 201 : 200);
    }

    public function schema()
    {
        $proposed = [];
        foreach (self::PROPOSED_FIELDS as $field => $validation) {
            $proposed[$field] = str_contains($validation, '|integer')
                ? ['type' => 'integer']
                : ['type' => 'string'];
            if (str_contains($validation, 'date_format')) {
                $proposed[$field]['format'] = 'date';
            }
        }
        $proposed['category']['enum'] = array_keys(CompanyAssetController::CATEGORIES);
        $proposed['document_type']['enum'] = array_keys(CompanyAssetController::DOCUMENT_TYPES);
        $objectResponse = fn (string $description) => [
            'description' => $description,
            'content' => ['application/json' => ['schema' => ['type' => 'object', 'additionalProperties' => true]]],
        ];

        return response()->json([
            'openapi' => '3.1.0',
            'info' => ['title' => 'Hồ sơ tài sản công ty', 'version' => '1.0.0',
                'description' => 'Tìm hồ sơ và lưu bản nháp cho kế toán kiểm tra. Chỉ đề xuất dữ liệu đọc được từ tài liệu.'],
            'servers' => [['url' => url('/')]],
            'security' => [['connectionKey' => []]],
            'components' => ['securitySchemes' => ['connectionKey' => ['type' => 'http', 'scheme' => 'bearer']]],
            'paths' => [
                route('asset-assistant.search', absolute: false) => ['get' => [
                    'operationId' => 'searchAssetRecords', 'summary' => 'Tìm tài sản, đối tác, hợp đồng hoặc chứng từ',
                    'x-openai-isConsequential' => false,
                    'parameters' => [
                        ['name' => 'q', 'in' => 'query', 'required' => true, 'schema' => ['type' => 'string', 'minLength' => 2, 'maxLength' => 150],
                            'description' => 'Ưu tiên số hợp đồng, mã số thuế, mã tài sản hoặc số chứng từ.'],
                        ['name' => 'kind', 'in' => 'query', 'schema' => ['type' => 'string', 'enum' => array_keys(self::SEARCH)]],
                    ],
                    'responses' => ['200' => $objectResponse('Kết quả tìm kiếm theo danh mục')],
                ]],
                route('asset-assistant.drafts.store', absolute: false) => ['post' => [
                    'operationId' => 'createAssetDraft', 'summary' => 'Lưu đề xuất dưới dạng bản nháp tài sản',
                    'description' => 'Tìm hồ sơ trước khi cập nhật hoặc bổ sung chứng từ. Bỏ qua trường chưa xác định và ghi vào review_notes. target_id lấy từ kết quả tìm kiếm.',
                    'x-openai-isConsequential' => true,
                    'requestBody' => ['required' => true, 'content' => ['application/json' => ['schema' => [
                        'type' => 'object', 'required' => ['request_id', 'operation', 'kind', 'title', 'proposed'],
                        'properties' => [
                            'request_id' => ['type' => 'string', 'format' => 'uuid', 'description' => 'UUID mới cho mỗi bản nháp. Dùng lại khi gửi lại cùng dữ liệu.'],
                            'operation' => ['type' => 'string', 'enum' => ['create', 'update', 'add_document']],
                            'kind' => ['type' => 'string', 'enum' => ['assets', 'partners', 'contracts']],
                            'target_id' => ['type' => 'integer', 'minimum' => 1, 'description' => 'ID hồ sơ khi cập nhật hoặc bổ sung chứng từ. Bỏ qua khi tạo mới.'],
                            'title' => ['type' => 'string', 'maxLength' => 150],
                            'proposed' => ['type' => 'object', 'additionalProperties' => false, 'properties' => $proposed],
                            'source_name' => ['type' => 'string', 'maxLength' => 200, 'description' => 'Tên bản scan hoặc PDF đã đọc.'],
                            'review_notes' => ['type' => 'string', 'maxLength' => 2500, 'description' => 'Thông tin mờ, còn thiếu hoặc cần kiểm tra.'],
                        ],
                    ]]]],
                    'responses' => ['201' => $objectResponse('Đã lưu, có đường dẫn xem bản nháp'), '200' => $objectResponse('Bản nháp đã được lưu trước đó')],
                ]],
            ],
        ], options: JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    public function index(Request $request)
    {
        $drafts = DB::table('asset_assistant_drafts')->latest('id')->paginate(20);
        $keys = $request->user()->isAdmin()
            ? DB::table('asset_assistant_keys')->latest('id')->get(['id', 'label', 'created_at', 'revoked_at'])
            : collect();

        return response()->view('accounting.assets.ai.index', compact('drafts', 'keys'))->header('Cache-Control', 'private, no-store');
    }

    public function show(int $draft)
    {
        $row = DB::table('asset_assistant_drafts as d')->join('asset_assistant_keys as k', 'k.id', '=', 'd.key_id')
            ->select('d.*', 'k.label as connection_name')->where('d.id', $draft)->first();
        abort_unless($row, 404);
        $proposed = json_decode($row->proposed, true, flags: JSON_THROW_ON_ERROR);

        return view('accounting.assets.ai.show', compact('row', 'proposed'));
    }

    public function createKey(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $data = $request->validate(['label' => 'required|string|max:100']);
        $secret = 'asset_'.bin2hex(random_bytes(32));
        DB::table('asset_assistant_keys')->insert([
            'label' => $data['label'], 'token_hash' => hash('sha256', $secret),
            'created_by' => $request->user()->id, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return response()->view('accounting.assets.ai.key', compact('secret'))->header('Cache-Control', 'private, no-store');
    }

    public function revokeKey(Request $request, int $key)
    {
        abort_unless($request->user()->isAdmin(), 403);
        DB::table('asset_assistant_keys')->where('id', $key)->whereNull('revoked_at')
            ->update(['revoked_at' => now(), 'updated_at' => now()]);

        return redirect()->to(accounting_route('assets.ai.index'))->with('success', 'Đã thu hồi khóa kết nối.');
    }
}
