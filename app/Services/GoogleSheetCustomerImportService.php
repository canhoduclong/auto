<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GoogleSheetCustomerImportService
{
    public function __construct(private CustomerDebtSheetService $sheets, private CustomerPriorityService $priorities) {}

    public function sales()
    {
        return User::query()->whereHas('roles', fn ($q) => $q->whereIn('name', ['sale', 'leader', 'leader_sale', 'sale_manager', 'manager', 'manager_sale']))->orderBy('name')->get(['id', 'name', 'short_name']);
    }

    public function load(User $actor): string
    {
        $entries = $this->sheets->parse($this->sheets->fetchSales(), 'sale');
        $groups = $this->sheets->summarize($entries);
        foreach ($groups as &$group) {
            unset($group['entries']);
        }
        unset($group);
        if (! $groups) {
            throw new \RuntimeException('Không có khách hàng trong tab nhatkybanhang.');
        }
        $token = (string) Str::uuid();
        $this->put($token, ['actor_id' => $actor->id, 'spreadsheet_id' => GoogleSheetOrderImportService::SPREADSHEET, 'loaded_at' => now()->toIso8601String(), 'groups' => $groups, 'decisions' => [], 'results' => []]);

        return $token;
    }

    public function snapshot(string $token, User $actor): array
    {
        $data = Cache::get('customer-sheet-import:'.$token);
        abort_unless($data && (int) $data['actor_id'] === (int) $actor->id, 419, 'Bản xem trước đã hết hạn hoặc không thuộc tài khoản này. Vui lòng tải lại Sheet.');

        return $data;
    }

    private function put(string $token, array $data): void
    {
        Cache::put('customer-sheet-import:'.$token, $data, now()->addHours(2));
    }

    public function plan(array $snapshot): array
    {
        $customers = Customer::withTrashed()->get(['id', 'name', 'customer_code', 'name_normalized', 'deleted_at', 'assigned_to', 'current_owner_sale_id', 'phone']);
        $byId = $customers->keyBy('id');
        $byName = $customers->groupBy(fn ($c) => Customer::normalizeName($c->name));
        $byCode = $customers->filter(fn ($c) => trim((string) $c->customer_code) !== '')->groupBy(fn ($c) => mb_strtoupper(trim($c->customer_code)));
        $names = collect($snapshot['groups'])->countBy(fn ($g) => Customer::normalizeName($g['name']));
        $sales = $this->sales();
        $saleNames = [];
        foreach ($sales as $sale) {
            foreach ([$sale->name, $sale->short_name] as $name) {
                if (trim((string) $name) !== '') {
                    $saleNames[Customer::normalizeName($name)][$sale->id] = $sale->id;
                }
            }
        }
        $mappings = $this->sheets->mappings();
        $rows = [];
        foreach ($snapshot['groups'] as $key => $group) {
            $decision = $snapshot['decisions'][$key] ?? [];
            $action = $decision['action'] ?? 'auto';
            $name = trim($decision['name'] ?? $group['name']);
            $code = mb_strtoupper(trim(array_key_exists('customer_code', $decision) ? (string) $decision['customer_code'] : $group['code']));
            $saleMatches = $saleNames[Customer::normalizeName($group['sale_name'])] ?? [];
            $saleId = array_key_exists('sale_id', $decision) ? ($decision['sale_id'] ?: null) : (count($saleMatches) === 1 ? array_values($saleMatches)[0] : null);
            $target = null;
            $reason = '';
            $status = 'create';
            if ($action === 'skip') {
                $status = 'skip';
            } elseif ($action === 'link') {
                $target = $byId[$decision['customer_id'] ?? 0] ?? null;
                if (! $target || $target->trashed()) {
                    $status = 'conflict';
                    $reason = 'Khách đã chọn không còn khả dụng.';
                } else {
                    $status = 'link';
                    $reason = 'Ghép theo lựa chọn';
                }
            } elseif ($action === 'auto' && isset($mappings[$key])) {
                $target = $byId[$mappings[$key]['customer_id']] ?? null;
                if (! $target || $target->trashed()) {
                    $status = 'conflict';
                    $reason = 'Khách đã ghép bị xóa, cần chọn lại.';
                } else {
                    $status = 'link';
                    $reason = 'Đã ghép nguồn';
                }
            } else {
                $codeMatches = $code !== '' ? ($byCode[$code] ?? collect()) : collect();
                $nameMatches = $byName[Customer::normalizeName($name)] ?? collect();
                $phoneMatches = ! empty($decision['phone']) ? $customers->where('phone', $decision['phone']) : collect();
                $candidates = $codeMatches->merge($nameMatches)->merge($phoneMatches)->unique('id');
                if ($action === 'create' && $candidates->isNotEmpty()) {
                    $status = 'conflict';
                    $reason = 'Tên, mã hoặc điện thoại đã có trong hệ thống. Hãy chọn khách để ghép.';
                } elseif ($candidates->count() > 1) {
                    $status = 'conflict';
                    $reason = 'Tên / mã trỏ đến nhiều khách khác nhau. Cần chọn khách để ghép.';
                } elseif ($candidates->count() === 1) {
                    $target = $candidates->first();
                    if ($target->trashed()) {
                        $status = 'conflict';
                        $reason = 'Khách đã nằm trong thùng rác, cần khôi phục hoặc chọn khách khác.';
                    } else {
                        $status = 'link';
                        $reason = $codeMatches->isNotEmpty() ? 'Trùng mã khách' : 'Trùng tên khách';
                    }
                } elseif ($action === 'auto' && $names[Customer::normalizeName($group['name'])] > 1) {
                    $status = 'conflict';
                    $reason = 'Nhiều mã nguồn cùng tên. Hãy ghép hoặc đổi tên khi tạo khách.';
                }
            }
            if ($saleId && ! $sales->contains('id', $saleId)) {
                $status = 'conflict';
                $reason = 'Sale đã chọn không còn hợp lệ.';
            }
            if ($name === '' || mb_strlen($name) > 255 || mb_strlen($code) > 255) {
                $status = 'conflict';
                $reason = 'Tên hoặc mã khách không hợp lệ.';
            }
            $result = $snapshot['results'][$key] ?? null;
            if ($result && ($result['ok'] ?? false)) {
                $status = 'imported';
                $target = $byId[$result['customer_id']] ?? null;
                $reason = $result['action'] === 'create' ? 'Đã tạo khách mới và ghép' : 'Đã ghép khách hệ thống';
            }
            $rows[$key] = $group + ['status' => $status, 'reason' => $reason, 'target' => $target, 'new_name' => $name, 'new_code' => $code, 'sale_id' => $saleId, 'phone' => $decision['phone'] ?? '', 'address' => $decision['address'] ?? '', 'result' => $result];
        }

        return $rows;
    }

    public function decide(string $token, User $actor, array $data): void
    {
        Cache::lock('customer-sheet-preview:'.$token, 120)->block(5, function () use ($token, $actor, $data) {
            $snapshot = $this->snapshot($token, $actor);
            abort_unless(isset($snapshot['groups'][$data['source_key']]), 404);
            abort_if(! empty($snapshot['results'][$data['source_key']]['ok']), 422, 'Dòng này đã import. Tải lại nguồn để thay đổi ghép.');
            $snapshot['decisions'][$data['source_key']] = $data;
            unset($snapshot['results'][$data['source_key']]);
            $this->put($token, $snapshot);
        });
    }

    public function commit(string $token, User $actor, bool $canCreate, int $limit = 25): array
    {
        return Cache::lock('customer-sheet-preview:'.$token, 120)->block(5, function () use ($token, $actor, $canCreate, $limit) {
            return Cache::lock('customer-debt-load', 120)->block(5, function () use ($token, $actor, $canCreate, $limit) {
                $snapshot = $this->snapshot($token, $actor);
                $counts = ['created' => 0, 'linked' => 0, 'skipped' => 0, 'errors' => 0];
                $rows = $this->plan($snapshot);
                foreach ($rows as $key => $row) {
                    if ($limit <= $counts['created'] + $counts['linked'] + $counts['errors']) {
                        break;
                    }
                    if ($row['status'] === 'imported') {
                        continue;
                    }
                    if (in_array($row['status'], ['conflict', 'skip'], true)) {
                        $counts['skipped']++;

                        continue;
                    }
                    if ($row['status'] === 'create' && ! $canCreate) {
                        $counts['skipped']++;

                        continue;
                    }
                    try {
                        $customerId = DB::transaction(function () use ($row, $actor) {
                            $customer = $row['target'];
                            if ($row['status'] === 'create') {
                                // Recheck under the shared lock, including rows created earlier in this import.
                                $duplicate = Customer::withTrashed()->where('name_normalized', Customer::normalizeName($row['new_name']))
                                    ->when($row['new_code'] !== '', fn ($q) => $q->orWhereRaw('UPPER(TRIM(customer_code)) = ?', [$row['new_code']]))
                                    ->when($row['phone'] !== '', fn ($q) => $q->orWhere('phone', $row['phone']))->exists();
                                if ($duplicate) {
                                    throw ValidationException::withMessages(['customer' => 'Khách đã tồn tại. Vui lòng chọn lại khách để ghép.']);
                                }
                                $saleId = $row['sale_id'];
                                $customer = Customer::create([
                                    'name' => $row['new_name'], 'customer_code' => $row['new_code'] ?: null, 'phone' => $row['phone'] ?: null,
                                    'address' => $row['address'] ?: null, 'user_id' => $actor->id, 'assigned_to' => $saleId, 'current_owner_sale_id' => $saleId,
                                    'assigned_at' => $saleId ? now() : null, 'customer_status' => $saleId ? 'active' : 'free',
                                    'free_from_date' => $saleId ? null : now(), 'current_cycle_no' => 1,
                                ]);
                                if ($row['address'] !== '') {
                                    $customer->addresses()->create(['note' => $row['address'], 'is_default' => 1]);
                                }
                                if ($saleId) {
                                    $this->priorities->attachSale($customer, (int) $saleId, 1, 'google_sheet_customer_import');
                                } else {
                                    $this->priorities->ensureLifecycle($customer);
                                }
                            } else {
                                $customer = Customer::query()->whereKey($customer->id)->lockForUpdate()->firstOrFail();
                            }
                            $this->sheets->mapSourceGroupWithinLock($row, $customer->id, $actor->id);
                            app(CustomerDebtPolicyService::class)->promoteSource($row['key'], $customer->id);
                            Setting::set('customer_sheet_import_audit_'.Str::uuid(), json_encode(['spreadsheet_id' => GoogleSheetOrderImportService::SPREADSHEET, 'source_key' => $row['key'], 'source_code' => $row['code'], 'source_name' => $row['name'], 'customer_id' => $customer->id, 'action' => $row['status'], 'actor_id' => $actor->id, 'at' => now()->toIso8601String()], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

                            return $customer->id;
                        });
                        $snapshot['results'][$key] = ['ok' => true, 'customer_id' => $customerId, 'action' => $row['status'] === 'create' ? 'create' : 'link'];
                        $counts[$row['status'] === 'create' ? 'created' : 'linked']++;
                    } catch (\Throwable $e) {
                        report($e);
                        $message = $e instanceof ValidationException ? collect($e->errors())->flatten()->first() : 'Không import được dòng này. Kiểm tra lại thông tin và thử lại.';
                        $snapshot['results'][$key] = ['ok' => false, 'message' => $message];
                        $counts['errors']++;
                    }
                    // Persist after each row so retries do not recreate successfully imported customers.
                    $this->put($token, $snapshot);
                }

                return $counts;
            });
        });
    }
}
