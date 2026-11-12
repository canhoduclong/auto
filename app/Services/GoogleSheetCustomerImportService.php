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
    public const SYNC_FIELDS = ['name' => 'Tên khách', 'customer_code' => 'Mã khách', 'phone' => 'Điện thoại', 'address' => 'Địa chỉ', 'sale_id' => 'Sale phụ trách'];

    public function __construct(private CustomerDebtSheetService $sheets, private CustomerPriorityService $priorities) {}

    public function sales()
    {
        return User::query()->whereHas('roles', fn ($q) => $q->whereIn('name', ['sale', 'leader', 'leader_sale', 'sale_manager', 'manager', 'manager_sale']))->orderBy('name')->get(['id', 'name', 'short_name']);
    }

    public function load(User $actor, string $mode = 'import', array $fields = ['name', 'phone', 'address']): string
    {
        $entries = $this->sheets->parse($this->sheets->fetchSales(), 'sale');
        $groups = $this->sheets->summarize($entries);
        foreach ($groups as &$group) {
            $latest = collect($group['entries'])->sortByDesc(fn ($entry) => $entry['date'].'|'.str_pad((string) $entry['row'], 10, '0', STR_PAD_LEFT));
            foreach (['name', 'phone', 'address'] as $field) {
                $group[$field] = trim((string) ($latest->first(fn ($entry) => trim((string) ($entry[$field] ?? '')) !== '')[$field] ?? ($group[$field] ?? '')));
            }
            unset($group['entries']);
        }
        unset($group);
        if (! $groups) {
            throw new \RuntimeException('Không có khách hàng trong tab nhatkybanhang.');
        }
        $token = (string) Str::uuid();
        $snapshot = ['actor_id' => $actor->id, 'spreadsheet_id' => GoogleSheetOrderImportService::SPREADSHEET, 'loaded_at' => now()->toIso8601String(), 'groups' => $groups, 'decisions' => [], 'results' => [], 'mode' => $mode, 'sync_fields' => array_values(array_intersect(array_keys(self::SYNC_FIELDS), $fields)), 'baseline' => []];
        if ($mode === 'sync') {
            foreach ($this->plan($snapshot) as $key => $row) {
                if ($row['target']) {
                    $snapshot['baseline'][$key] = ['id' => $row['target']->id, 'revision' => $row['target_revision']];
                }
            }
        }
        $this->put($token, $snapshot);

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
        $customers = Customer::withTrashed()->get(['id', 'name', 'customer_code', 'name_normalized', 'deleted_at', 'assigned_to', 'current_owner_sale_id', 'phone', 'address', 'customer_status', 'assigned_at']);
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
            } elseif (in_array($action, ['link', 'update'], true)) {
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
            $sync = ($snapshot['mode'] ?? 'import') === 'sync';
            $syncFields = $decision['sync_fields'] ?? (isset($mappings[$key]['sync_fields']) ? array_values(array_intersect($snapshot['sync_fields'] ?? [], $mappings[$key]['sync_fields'])) : ($snapshot['sync_fields'] ?? []));
            if (in_array($action, ['link', 'skip'], true)) {
                $syncFields = [];
            }
            $changes = [];
            $desired = [];
            if ($sync && $target && ! $target->trashed() && $status === 'link' && $syncFields) {
                $incoming = ['name' => $name, 'customer_code' => $code, 'phone' => trim($decision['phone'] ?? ($group['phone'] ?? '')), 'address' => trim($decision['address'] ?? ($group['address'] ?? '')), 'sale_id' => $saleId];
                if ((in_array('phone', $syncFields, true) && mb_strlen($incoming['phone']) > 30) || (in_array('address', $syncFields, true) && mb_strlen($incoming['address']) > 255)) {
                    $status = 'conflict';
                    $reason = 'Điện thoại hoặc địa chỉ nguồn vượt độ dài cho phép. Cần kiểm tra lại Sheet.';
                }
                foreach ($syncFields as $field) {
                    $value = $incoming[$field] ?? null;
                    if ($value === '' || $value === null) {
                        continue;
                    }
                    $old = $field === 'sale_id' ? ($target->current_owner_sale_id ?? $target->assigned_to) : $target->{$field};
                    $desired[$field] = $value;
                    if ((string) $old !== (string) $value) {
                        $changes[$field] = ['before' => $old, 'after' => $value];
                    }
                }
                foreach (['name', 'customer_code', 'phone'] as $field) {
                    if (! isset($changes[$field])) {
                        continue;
                    }
                    $value = $changes[$field]['after'];
                    $duplicate = $customers->contains(fn ($c) => $c->id !== $target->id && ($field === 'name' ? Customer::normalizeName($c->name) === Customer::normalizeName($value) : ($field === 'customer_code' ? mb_strtoupper(trim((string) $c->customer_code)) === $value : (string) $c->phone === (string) $value)));
                    if ($duplicate) {
                        $status = 'conflict';
                        $reason = 'Thông tin cập nhật trùng với khách khác. Chọn lại trường đồng bộ hoặc chỉ ghép nguồn.';
                    }
                }
                if ($status === 'link' && $changes) {
                    $status = 'update';
                    $reason = 'Có thông tin thay đổi từ Sheet';
                }
            }
            if ($sync && $status === 'link' && isset($mappings[$key]) && (int) $mappings[$key]['customer_id'] === (int) $target?->id && $action !== 'link') {
                $status = 'unchanged';
                $reason = 'Đã ghép · Không có thay đổi';
            }
            $targetRevision = $target ? $this->customerRevision($target) : null;
            $result = $snapshot['results'][$key] ?? null;
            if ($result && ($result['ok'] ?? false)) {
                $status = 'imported';
                $target = $byId[$result['customer_id']] ?? null;
                $reason = $result['action'] === 'update' ? 'Đã cập nhật thông tin từ Sheet' : ($result['action'] === 'create' ? 'Đã tạo khách mới và ghép' : 'Đã ghép khách hệ thống');
            }
            $rows[$key] = array_replace($group, ['status' => $status, 'reason' => $reason, 'target' => $target, 'new_name' => $name, 'new_code' => $code, 'sale_id' => $saleId, 'phone' => $decision['phone'] ?? ($group['phone'] ?? ''), 'address' => $decision['address'] ?? ($group['address'] ?? ''), 'result' => $result, 'sync_mode' => $sync, 'sync_explicit' => in_array($action, ['link', 'update'], true), 'sync_preferences' => $syncFields, 'desired' => $desired, 'changes' => $changes, 'target_revision' => $targetRevision]);
        }

        if (($snapshot['mode'] ?? 'import') === 'sync') {
            $values = [];
            foreach ($rows as $key => $row) {
                if (! $row['target'] || in_array($row['status'], ['skip', 'imported'], true)) {
                    continue;
                }
                foreach ($row['desired'] as $field => $value) {
                    $values[$row['target']->id][$field][(string) $value][] = $key;
                }
            }
            foreach ($values as $fields) {
                foreach ($fields as $variants) {
                    if (count($variants) > 1) {
                        foreach ($variants as $keys) {
                            foreach ($keys as $key) {
                                $rows[$key]['status'] = 'conflict';
                                $rows[$key]['reason'] = 'Các nguồn cùng khách đề xuất thông tin khác nhau. Chọn một nguồn cập nhật và chỉ ghép các nguồn còn lại.';
                            }
                        }
                    }
                }
            }
        }

        return $rows;
    }

    public function customerRevision(Customer $customer): string
    {
        return hash('sha256', json_encode([$customer->id, $customer->name, $customer->customer_code, $customer->phone, $customer->address, $customer->assigned_to, $customer->current_owner_sale_id, $customer->customer_status, (string) $customer->assigned_at], JSON_THROW_ON_ERROR));
    }

    public function decide(string $token, User $actor, array $data): void
    {
        Cache::lock('customer-sheet-preview:'.$token, 120)->block(5, function () use ($token, $actor, $data) {
            $snapshot = $this->snapshot($token, $actor);
            abort_unless(isset($snapshot['groups'][$data['source_key']]), 404);
            abort_if(! empty($snapshot['results'][$data['source_key']]['ok']), 422, 'Dòng này đã import. Tải lại nguồn để thay đổi ghép.');
            if (($data['action'] ?? '') === 'update') {
                abort_unless(($snapshot['mode'] ?? 'import') === 'sync', 422);
                $snapshot['baseline'][$data['source_key']] = ['id' => (int) $data['customer_id'], 'revision' => $data['customer_revision']];
            }
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
                if (($snapshot['mode'] ?? 'import') === 'sync') {
                    $counts['updated'] = 0;
                }
                $rows = $this->plan($snapshot);
                foreach ($rows as $key => $row) {
                    if ($limit <= $counts['created'] + $counts['linked'] + $counts['errors'] + ($counts['updated'] ?? 0)) {
                        break;
                    }
                    if (in_array($row['status'], ['imported', 'unchanged'], true)) {
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
                        $customerId = DB::transaction(function () use ($row, $actor, $snapshot) {
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
                            $beforeUpdate = [];
                            $afterUpdate = [];
                            if ($row['status'] === 'update') {
                                abort_unless(\Illuminate\Support\Facades\Gate::forUser($actor)->allows('update', $customer), 403);
                                $baseline = $snapshot['baseline'][$row['key']] ?? null;
                                if (! $baseline || (int) $baseline['id'] !== (int) $customer->id || ! hash_equals($baseline['revision'], $this->customerRevision($customer))) {
                                    throw ValidationException::withMessages(['customer' => 'Thông tin khách đã thay đổi sau khi xem trước. Tải lại Sheet hoặc chọn lại khách trước khi cập nhật.']);
                                }
                                foreach ($row['changes'] as $field => &$change) {
                                    $actual = $field === 'sale_id' ? ($customer->current_owner_sale_id ?? $customer->assigned_to) : $customer->{$field};
                                    if ((string) $actual === (string) $change['after']) {
                                        unset($row['changes'][$field]);

                                        continue;
                                    }
                                    $change['before'] = $actual;
                                    $beforeUpdate[$field] = $change['before'];
                                    $afterUpdate[$field] = $change['after'];
                                    if ($field !== 'sale_id') {
                                        $customer->{$field} = $change['after'];
                                    }
                                }
                                unset($change);
                                if (! $row['changes']) {
                                    $row['status'] = 'link';
                                }
                                // Recheck uniqueness after earlier rows in the same batch.
                                foreach (['name', 'customer_code', 'phone'] as $field) {
                                    if (! isset($row['changes'][$field])) {
                                        continue;
                                    }
                                    $query = Customer::withTrashed()->where('id', '!=', $customer->id);
                                    if ($field === 'name') {
                                        $query->where('name_normalized', Customer::normalizeName($customer->name));
                                    } elseif ($field === 'customer_code') {
                                        $query->whereRaw('UPPER(TRIM(customer_code)) = ?', [$customer->customer_code]);
                                    } else {
                                        $query->where('phone', $customer->phone);
                                    }
                                    if ($query->exists()) {
                                        throw ValidationException::withMessages(['customer' => 'Thông tin bị trùng với khách khác. Vui lòng kiểm tra lại.']);
                                    }
                                }
                                $customer->save();
                                if (isset($row['changes']['address'])) {
                                    $address = $customer->addresses()->where('is_default', 1)->first();
                                    if ($address) {
                                        $address->update(['note' => $customer->address]);
                                    } else {
                                        $customer->addresses()->create(['note' => $customer->address, 'is_default' => 1]);
                                    }
                                }
                                if (isset($row['changes']['sale_id'])) {
                                    abort_unless($actor->isAdmin() || $actor->hasPermission('customers.assign-sale'), 403);
                                    $this->priorities->takeover($customer, (int) $row['changes']['sale_id']['after'], 'google_sheet_customer_sync');
                                }
                            }
                            if (! $row['sync_mode'] || ! $row['sync_explicit']) {
                                unset($row['sync_preferences']);
                            }
                            $this->sheets->mapSourceGroupWithinLock($row, $customer->id, $actor->id);
                            app(CustomerDebtPolicyService::class)->promoteSource($row['key'], $customer->id);
                            Setting::set('customer_sheet_import_audit_'.Str::uuid(), json_encode(['spreadsheet_id' => GoogleSheetOrderImportService::SPREADSHEET, 'source_key' => $row['key'], 'source_code' => $row['code'], 'source_name' => $row['name'], 'customer_id' => $customer->id, 'action' => $row['status'], 'before' => $beforeUpdate, 'after' => $afterUpdate, 'actor_id' => $actor->id, 'at' => now()->toIso8601String()], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

                            return ['id' => $customer->id, 'action' => $row['status'], 'revision' => $this->customerRevision($customer->fresh())];
                        });
                        $afterRevision = $customerId['revision'];
                        $resultAction = $customerId['action'];
                        $customerId = $customerId['id'];
                        if ($row['status'] === 'update') {
                            $oldRevision = $snapshot['baseline'][$key]['revision'];
                            foreach ($snapshot['baseline'] as &$baseline) {
                                if ((int) $baseline['id'] === (int) $customerId && $baseline['revision'] === $oldRevision) {
                                    $baseline['revision'] = $afterRevision;
                                }
                            }
                            unset($baseline);
                        }
                        $snapshot['results'][$key] = ['ok' => true, 'customer_id' => $customerId, 'action' => $resultAction];
                        $counts[match ($resultAction) {
                            'create' => 'created', 'update' => 'updated', default => 'linked'
                        }]++;
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
