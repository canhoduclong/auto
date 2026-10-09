<?php

namespace App\Http\Controllers\Admin; 
use App\Http\Controllers\Controller; 
use Illuminate\Http\Request;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
 

class SettingController extends Controller
{
    /**
     * Middleware để bảo vệ các route trong controller này
     * Chỉ người dùng đã đăng nhập và có quyền 'manage-settings' mới có thể truy cập
     */ 
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:manage-settings');
    }

    public function index()
    {
        $settings = Cache::remember('settings', 60, function () {
            return Setting::all()->keyBy('key');
        });

        $pushHistory = $this->readPushHistory();
        $showPushFeature = !$this->isRestrictedPushDomain(request()->getHost());

        $pushPublicKey = app(\App\Services\GitPushService::class)->publicKey();

        return view('admin.settings.index', compact('settings', 'pushHistory', 'showPushFeature', 'pushPublicKey'));
    }

    public function resetDataIndex()
    {
        $resetGroups = $this->dataResetGroups();
        $resettableTables = $this->getResettableTables();

        return view('admin.settings.reset-data', compact('resetGroups', 'resettableTables'));
    }

    public function resetData(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->hasRole('admin')) {
            abort(403, 'Bạn không có quyền reset dữ liệu.');
        }

        $validated = $request->validate([
            'key' => ['required', 'string'],
            'mode' => ['required', 'in:groups,tables'],
            'groups' => ['nullable', 'array'],
            'groups.*' => ['string'],
            'tables' => ['nullable', 'array'],
            'tables.*' => ['string'],
            'confirm_text' => ['required', 'string'],
        ]);

        if (trim((string) $validated['key']) !== 'huy2024') {
            return back()->with('error', 'Sai key xác nhận reset dữ liệu.');
        }

        if (strtoupper(trim((string) $validated['confirm_text'])) !== 'RESET') {
            return back()->with('error', 'Bạn phải nhập đúng từ khóa RESET để xác nhận.');
        }

        $allowedTables = $this->getResettableTables();
        $groupMap = $this->dataResetGroups();

        if ($validated['mode'] === 'groups' && empty($validated['groups'])) {
            return back()->with('error', 'Vui lòng chọn ít nhất 1 nhóm dữ liệu để reset.')->withInput();
        }

        if ($validated['mode'] === 'tables' && empty($validated['tables'])) {
            return back()->with('error', 'Vui lòng chọn ít nhất 1 bảng để reset.')->withInput();
        }

        $targetTables = [];
        if ($validated['mode'] === 'groups') {
            $selectedGroups = array_values(array_unique($validated['groups'] ?? []));
            foreach ($selectedGroups as $groupKey) {
                if (!array_key_exists($groupKey, $groupMap)) {
                    continue;
                }
                $targetTables = array_merge($targetTables, $groupMap[$groupKey]['tables']);
            }
        } else {
            $targetTables = array_values(array_unique($validated['tables'] ?? []));
        }

        $targetTables = array_values(array_intersect(array_unique($targetTables), $allowedTables));

        if (empty($targetTables)) {
            return back()->with('error', 'Không có bảng hợp lệ để làm mới dữ liệu.')->withInput();
        }

        $stats = [];

        $driver = DB::getDriverName();
        DB::beginTransaction();
        try {
            if ($driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
            }
            if ($driver === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = OFF');
            }

            foreach ($targetTables as $table) {
                $rows = (int) DB::table($table)->count();
                DB::table($table)->delete();

                if ($driver === 'mysql') {
                    DB::statement('ALTER TABLE `' . str_replace('`', '``', $table) . '` AUTO_INCREMENT = 1');
                }
                if ($driver === 'sqlite') {
                    DB::statement("DELETE FROM sqlite_sequence WHERE name = '" . str_replace("'", "''", $table) . "'");
                }

                $stats[] = [
                    'table' => $table,
                    'rows' => $rows,
                ];
            }

            if (DB::transactionLevel() > 0) {
                DB::commit();
            }
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            return back()->with('error', 'Reset dữ liệu thất bại: ' . $e->getMessage());
        } finally {
            if ($driver === 'mysql') {
                @DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
            if ($driver === 'sqlite') {
                @DB::statement('PRAGMA foreign_keys = ON');
            }
        }

        $totalRows = array_sum(array_map(fn ($row) => (int) ($row['rows'] ?? 0), $stats));

        return back()->with('success', 'Đã làm mới ' . count($stats) . ' bảng, tổng ' . number_format($totalRows) . ' bản ghi.')
            ->with('reset_result', $stats);
    }

    private function dataResetGroups(): array
    {
        return [
            'orders' => [
                'label' => 'Đơn hàng',
                'tables' => ['orders', 'order_items', 'order_histories', 'order_approvals', 'approval_orders'],
            ],
            'inventory' => [
                'label' => 'Tồn kho',
                'tables' => [
                    'inventories',
                    'inventory_adjustments',
                    'inventory_documents',
                    'inventory_document_items',
                    'inventory_document_edits',
                    'inventory_movements',
                    'inventory_reservations',
                    'goods_receipts',
                    'purchase_orders',
                ],
            ],
            'transactions' => [
                'label' => 'Giao dịch',
                'tables' => [
                    'transactions',
                    'accounting_customer_commissions',
                    'accounting_customer_discounts',
                    'accounting_supplier_payables',
                ],
            ],
            'pricing' => [
                'label' => 'Giá bán',
                'tables' => [
                    'product_price_rules',
                    'product_price_logs',
                ],
            ],
            'returns' => [
                'label' => 'Trả hàng',
                'tables' => ['order_returns', 'return_items'],
            ],
            'appointments' => [
                'label' => 'Cuộc hẹn',
                'tables' => ['customer_reminders'],
            ],
            'tasks' => [
                'label' => 'Giao việc',
                'tables' => ['tasks'],
            ],
            'reports' => [
                'label' => 'Báo cáo',
                'tables' => ['admin_events', 'customer_care_logs'],
            ],
        ];
    }

    private function getResettableTables(): array
    {
        $existing = Schema::getTableListing();
        $databaseName = strtolower((string) DB::connection()->getDatabaseName());
        $normalizedExisting = [];

        foreach ($existing as $table) {
            $table = trim((string) $table);
            if ($table === '') {
                continue;
            }

            if (str_contains($table, '.')) {
                [$schema, $name] = array_pad(explode('.', $table, 2), 2, null);
                if ($name === null || $name === '') {
                    continue;
                }

                if ($databaseName !== '' && strtolower((string) $schema) !== $databaseName) {
                    continue;
                }

                $normalizedExisting[] = $name;
                continue;
            }

            $normalizedExisting[] = $table;
        }

        $existing = array_values(array_unique($normalizedExisting));
        $protectedTables = [
            'users',
            'roles',
            'permissions',
            'role_user',
            'role_permission',
            'permission_role',
            'settings',
            'sessions',
            'cache',
            'cache_locks',
            'jobs',
            'job_batches',
            'failed_jobs',
            'password_reset_tokens',
            'teams',
        ];

        $tables = array_values(array_diff($existing, $protectedTables));
        sort($tables);

        return $tables;
    }

    public function update(Request $request)
    {
        $data = $request->except('_token');

        foreach (['priority_1_days', 'priority_2_days', 'priority_3_days', 'free_customer_days', 'customer_free_days', 'warehouse_return_warning_days'] as $numericKey) {
            if (array_key_exists($numericKey, $data)) {
                $data[$numericKey] = max((int) $data[$numericKey], 0);
            }
        }

        if (array_key_exists('customer_free_days', $data)) {
            $data['customer_free_days'] = max((int) $data['customer_free_days'], 0);
        }

        foreach ($data as $key => $value) {
            Setting::set($key, $value);
        }

        Cache::forget('settings');

        return redirect()->back()->with('success', 'Settings updated successfully.');
    }

   public function deploy(Request $request)
{
    $user = $request->user();

    if (!$user || !$user->hasRole('admin')) {
        abort(403, 'Bạn không có quyền deploy.');
    }

    if ((string) $request->input('key') !== 'huy2024') {
        return back()->with('error', 'Sai key deploy.');
    }

    $deployPath = '/home/hoanglongtnt/web';
    $branch = 'hoanglong';
    $logs = [];

    $logs[] = 'Deploy branch: ' . $branch;
    $logs[] = 'Deploy path: ' . $deployPath;
    $logs[] = '';
    $logs[] = 'Pulling code...';

    // 1. GIT PULL
    [$pullCode, $pullOutput] = $this->runDeployCommand("cd {$deployPath} && git pull origin {$branch}");
    $logs = array_merge($logs, $pullOutput);
    $logs[] = '';

    if ($pullCode !== 0) {
        $logs[] = 'Deploy failed at step: git pull';

        return back()
            ->with('error', 'Deploy thất bại ở bước pull code.')
            ->with('deploy_output', implode("\n", $logs))
            ->with('deploy_status', 'error');
    }

    // 2. FILE CHANGED
    $logs[] = 'Changed files:';
    [$diffCode, $diffOutput] = $this->runDeployCommand("cd {$deployPath} && git diff --name-only ORIG_HEAD HEAD");

    if ($diffCode === 0 && !empty($diffOutput)) {
        $logs = array_merge($logs, $diffOutput);
    } else {
        $logs[] = '(No changed files or already up-to-date)';
    }
    $logs[] = '';

    // 3. MIGRATE (🔥 CHẠY ĐÚNG CÁCH)
    $logs[] = 'Running migrate (Laravel)...';

    try {
        \Artisan::call('migrate', ['--force' => true]);
        $output = trim(\Artisan::output());
        $logs[] = $output !== '' ? $output : 'Migrate done.';
    } catch (\Throwable $e) {
        $logs[] = $e->getMessage();
        $logs[] = 'Deploy failed at step: migrate';

        return back()
            ->with('error', 'Deploy thất bại ở bước migrate.')
            ->with('deploy_output', implode("\n", $logs))
            ->with('deploy_status', 'error');
    }

    $logs[] = '';

    // 4. CLEAR CACHE (có thể dùng exec vẫn OK)
    $steps = [
        [
            'title' => 'Clearing cache...',
            'command' => "cd {$deployPath} && php artisan optimize:clear",
            'fail' => 'optimize:clear'
        ],
        [
            'title' => 'Caching config...',
            'command' => "cd {$deployPath} && php artisan config:cache",
            'fail' => 'config:cache'
        ],
        [
            'title' => 'Caching routes...',
            'command' => "cd {$deployPath} && php artisan route:cache",
            'fail' => 'route:cache'
        ],
    ];

    foreach ($steps as $step) {
        $logs[] = $step['title'];

        [$code, $output] = $this->runDeployCommand($step['command']);
        $logs = array_merge($logs, $output);
        $logs[] = '';

        if ($code !== 0) {
            $logs[] = 'Deploy failed at step: ' . $step['fail'];

            return back()
                ->with('error', 'Deploy thất bại ở bước ' . $step['fail'])
                ->with('deploy_output', implode("\n", $logs))
                ->with('deploy_status', 'error');
        }
    }

    // 5. DONE
    $logs[] = 'Deploy success.';

    return back()
        ->with('success', 'Deploy thành công')
        ->with('deploy_output', implode("\n", $logs))
        ->with('deploy_status', 'success');
}

    public function push(Request $request)
    {
        if ($this->isRestrictedPushDomain($request->getHost())) {
            return back()->with('error', 'Domain này chỉ cho phép Deploy, không hiển thị/không chạy Push.');
        }

        $user = $request->user();
        if (!$user || !$user->hasRole('admin')) {
            abort(403, 'Bạn không có quyền thực hiện push code.');
        }

        $validated = $request->validate([
            'key' => ['required', 'string'],
            'commit_message' => ['required', 'string', 'max:500'],
        ]);

        if ((string) $validated['key'] !== 'huy2024') {
            return back()->with('error', 'Sai key push code.')->withInput();
        }

        return $this->runGitPushAction($request, false, trim((string) $validated['commit_message']));
    }

    public function checkPush(Request $request)
    {
        abort_unless($request->user()?->hasRole('admin'), 403);
        abort_if($this->isRestrictedPushDomain($request->getHost()), 403);

        return $this->runGitPushAction($request, true);
    }

    private function runGitPushAction(Request $request, bool $checkOnly, string $message = '')
    {
        $lock = Cache::lock('admin-git-push', 180);
        if (! $lock->get()) {
            return back()->with('error', 'Đang có thao tác Git chạy. Vui lòng chờ rồi thử lại.');
        }
        $service = app(\App\Services\GitPushService::class);
        $status = 'success';
        try {
            if ($checkOnly) {
                $service->checkConnection();
            } else {
                $service->push($message);
            }
            $notice = $checkOnly ? 'Kết nối GitHub hợp lệ. Chưa commit hoặc push code.' : 'Push code thành công lên nhánh '.$service->branch.'.';
        } catch (\Throwable $exception) {
            $status = 'error';
            $notice = $exception->getMessage();
            $service->logs[] = $notice;
        } finally {
            $lock->release();
        }
        $this->appendPushHistory([
            'time' => now()->toDateTimeString(),
            'user' => $request->user()->email,
            'branch' => $service->branch,
            'commit_message' => $checkOnly ? '[Kiểm tra kết nối]' : $message,
            'status' => $status,
            'changed_files' => $service->changedFiles,
            'output' => $service->logs,
        ]);

        return back()->with($status, $notice)
            ->with('push_output', implode("\n", $service->logs))->with('push_status', $status);
    }

    private function runDeployCommand(string $command): array
    {
        $output = [];
        $exitCode = 0;
        exec($command . ' 2>&1', $output, $exitCode);

        if (empty($output)) {
            $output[] = '(No output)';
        }

        return [$exitCode, $output];
    }

    private function appendPushHistory(array $entry): void
    {
        $file = storage_path('app/push_history.json');
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $history = [];
        if (is_file($file)) {
            $json = @file_get_contents($file);
            $decoded = json_decode((string) $json, true);
            if (is_array($decoded)) {
                $history = $decoded;
            }
        }

        $history[] = $entry;
        if (count($history) > 100) {
            $history = array_slice($history, -100);
        }

        @file_put_contents($file, json_encode($history, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function readPushHistory(): array
    {
        $file = storage_path('app/push_history.json');
        if (!is_file($file)) {
            return [];
        }

        $json = @file_get_contents($file);
        $decoded = json_decode((string) $json, true);
        if (!is_array($decoded)) {
            return [];
        }

        return array_reverse($decoded);
    }

    private function isRestrictedPushDomain(?string $host): bool
    {
        $host = strtolower(trim((string) $host));

        return $host === 'hoanglongtnt.com' || $host === 'www.hoanglongtnt.com';
    }

    public function executeCommand(Request $request)
    {
        abort_unless($request->user()?->hasRole('admin'), 403);
        $data = $request->validate(['command' => ['required', 'string', 'max:500']]);
        $command = trim($data['command']);
        $tokens = preg_split('/\s+/', $command);
        if (array_slice($tokens, 0, 2) === ['php', 'artisan']) $tokens = array_slice($tokens, 2);
        $name = array_shift($tokens);
        $allowed = ['shipping-expenses:configure', 'view:clear', 'cache:clear', 'config:clear', 'route:clear', 'optimize:clear', 'queue:restart'];
        $arguments = [];
        $valid = in_array($name, $allowed, true);
        foreach ($tokens as $token) {
            if ($name !== 'shipping-expenses:configure' || ! preg_match('/^--(coordinator|accountant)=([1-9][0-9]*)$/D', $token, $match) || isset($arguments['--'.$match[1]])) {
                $valid = false;
                break;
            }
            $arguments['--'.$match[1]] = $match[2];
        }
        if (! $valid) return back()->withInput()->withErrors(['command' => 'Lệnh hoặc tham số chưa được hỗ trợ. Chọn một lệnh trong danh sách bên dưới.']);
        $lock = Cache::lock('admin-settings-command', 120);
        if (! $lock->get()) return back()->withInput()->withErrors(['command' => 'Đang có lệnh được thực thi. Vui lòng thử lại sau.']);
        $output = new \Symfony\Component\Console\Output\BufferedOutput();
        try {
            $exitCode = \Illuminate\Support\Facades\Artisan::call($name, $arguments, $output);
        } catch (\Throwable $exception) {
            report($exception);
            $output->writeln('Không thực thi được lệnh: '.$exception->getMessage());
            $exitCode = 1;
        } finally {
            $lock->release();
        }
        \Illuminate\Support\Facades\Log::info('Admin executed maintenance command', ['user_id' => $request->user()->id, 'command' => $name, 'arguments' => $arguments, 'exit_code' => $exitCode]);
        return back()->with('artisan_title', $command)
            ->with('artisan_output', trim($output->fetch()) ?: 'Lệnh đã kết thúc, không có nội dung trả về.')
            ->with('artisan_status', $exitCode === 0 ? 'success' : 'error')
            ->with($exitCode === 0 ? 'success' : 'error', $exitCode === 0 ? 'Thực thi lệnh thành công.' : 'Thực thi lệnh thất bại. Xem kết quả bên dưới.');
    }

    public function artisan(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->hasRole('admin')) {
            abort(403);
        }

        $allowed = [
            'dump-autoload'       => ['title' => 'composer dump-autoload',     'command' => 'cd /var/www/auto.com && composer dump-autoload --no-interaction 2>&1'],
            'fpm-reload'          => ['title' => 'PHP-FPM reload',             'command' => 'sudo service php8.2-fpm reload 2>&1'],
            'fpm-restart'         => ['title' => 'PHP-FPM restart (flush OPcache)', 'command' => 'sudo service php8.2-fpm restart 2>&1'],
            'view-clear'          => ['title' => 'php artisan view:clear',      'command' => 'cd /var/www/auto.com && php artisan view:clear 2>&1'],
            'cache-clear'         => ['title' => 'php artisan cache:clear',     'command' => 'cd /var/www/auto.com && php artisan cache:clear 2>&1'],
            'config-clear'        => ['title' => 'php artisan config:clear',    'command' => 'cd /var/www/auto.com && php artisan config:clear 2>&1'],
            'route-clear'         => ['title' => 'php artisan route:clear',     'command' => 'cd /var/www/auto.com && php artisan route:clear 2>&1'],
            'optimize-clear'      => ['title' => 'php artisan optimize:clear',  'command' => 'cd /var/www/auto.com && php artisan optimize:clear 2>&1'],
            'migrate'             => ['title' => 'php artisan migrate --force', 'command' => 'cd /var/www/auto.com && php artisan migrate --force 2>&1'],
            'queue-restart'       => ['title' => 'php artisan queue:restart',   'command' => 'cd /var/www/auto.com && php artisan queue:restart 2>&1'],
        ];

        $cmd = $request->input('cmd');
        if (!array_key_exists($cmd, $allowed)) {
            return back()->with('error', 'Lệnh không hợp lệ.');
        }

        $item = $allowed[$cmd];
        [, $output] = $this->runDeployCommand($item['command']);

        $log = implode("\n", $output);

        return back()
            ->with('artisan_output', $log)
            ->with('artisan_title', $item['title'])
            ->with('artisan_status', 'success');
    }
}
