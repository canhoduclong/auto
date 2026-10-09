<?php

namespace App\Console\Commands;

use App\Models\ProcessDefinition;
use App\Models\User;
use App\Services\ProcessEngine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ConfigureShippingExpenseProcess extends Command
{
    protected $signature = 'shipping-expenses:configure {--coordinator= : ID điều phối} {--accountant= : ID kế toán}';
    protected $description = 'Kiểm tra và kích hoạt quy trình chi phí ship; giữ cấu hình duyệt hiện có';

    public function handle(ProcessEngine $engine): int
    {
        try {
            DB::transaction(function () use ($engine): void {
                $definitions = ProcessDefinition::where('activity', 'shipping_expense')->lockForUpdate()->get();
                $definition = $definitions->firstWhere('is_active', true);
                if (! $definition && $definitions->count() > 1) {
                    throw new \RuntimeException('Có nhiều quy trình chưa kích hoạt. Hãy chọn và kích hoạt tại /process-management.');
                }
                $definition ??= $definitions->first();
                if (! $definition) {
                    $coordinator = $this->person('coordinator', 'Biet Nguyen');
                    $accountant = $this->person('accountant', 'Chi - Kế toán');
                    $configuration = ['initiator_role' => 'shipper', 'positions' => ['inbox', 'shipping_list'], 'steps' => [
                        ['name' => 'Điều phối xác nhận', 'role' => 'manager_shipper', 'user_id' => $coordinator->id, 'assignment_mode' => 'user_role', 'revision_resume' => 'current'],
                        ['name' => 'Kế toán xác nhận', 'role' => 'accountant', 'user_id' => $accountant->id, 'assignment_mode' => 'user_role', 'revision_resume' => 'current'],
                    ]];
                    // Account installations may use a different accounting role name.
                    $configuration['steps'][1]['role'] = $accountant->roles->first(fn ($role) => in_array(strtolower($role->name), ['accountant', 'accounting', 'account']))?->name ?? 'accountant';
                    $definition = new ProcessDefinition(['code' => 'shipping_expense', 'activity' => 'shipping_expense', 'name' => 'Shipper → Điều phối → Kế toán', 'version' => 1, 'configuration' => $configuration]);
                } elseif ($this->option('coordinator') || $this->option('accountant')) {
                    throw new \RuntimeException('Quy trình đã tồn tại. Đổi người duyệt tại /process-management để giữ đầy đủ phiên bản.');
                }
                $steps = $definition->configuration['steps'] ?? [];
                if (! $steps || empty($definition->configuration['initiator_role'])) throw new \RuntimeException('Cấu hình quy trình thiếu người khởi tạo hoặc các bước duyệt.');
                foreach ($steps as $step) {
                    $mode = $step['assignment_mode'] ?? 'user_role';
                    $people = $mode === 'role' ? User::whereHas('roles', fn ($query) => $query->whereRaw('LOWER(name)=?', [strtolower($step['role'] ?? '')]))->get() : collect([User::find($step['user_id'] ?? 0)])->filter();
                    if (! $people->contains(fn ($user) => $engine->matches($step, $user))) throw new \RuntimeException('Người duyệt không hợp lệ tại bước: '.($step['name'] ?? '?').'. Cập nhật tại /process-management.');
                }
                $definition->is_active = true;
                $definition->save();
                $this->info('Quy trình #'.$definition->id.' đã sẵn sàng. App có thể gửi xác nhận chi phí ship.');
            });
            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }
    }

    private function person(string $option, string $name): User
    {
        $users = $this->option($option) ? User::whereKey($this->option($option))->get() : User::where('name', $name)->get();
        if ($users->count() !== 1) throw new \RuntimeException('Không xác định được '.$option.'. Chạy lại với --'.$option.'=ID người duyệt.');
        return $users->first();
    }
}
