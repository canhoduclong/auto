<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\GoogleSheetsOrderService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class SyncOrdersToGoogleSheets extends Command
{
    protected $signature = 'orders:sync-google-sheets
        {--all : Đồng bộ toàn bộ đơn đã được Manager duyệt và đơn đã hủy}
        {--order=* : Chỉ đồng bộ các ID hoặc mã đơn được chỉ định}';

    protected $description = 'Đổ đơn hàng và chi tiết đơn hàng sang Google Sheets vận hành';

    public function handle(GoogleSheetsOrderService $service): int
    {
        if (! $service->isConfigured()) {
            $this->error('Google Sheets đơn hàng chưa được bật hoặc chưa đủ cấu hình trong .env.');

            return self::FAILURE;
        }

        $requested = collect($this->option('order'))->filter()->values();
        if (! $this->option('all') && $requested->isEmpty()) {
            $this->error('Dùng --all hoặc --order=ID/MÃ_ĐƠN để xác định phạm vi đồng bộ.');

            return self::INVALID;
        }

        $query = Order::query()
            ->where(function (Builder $statusQuery): void {
                $statusQuery->where('status', Order::STATUS_CANCELLED)
                    ->orWhereHas('approvals', fn (Builder $approvalQuery) => $approvalQuery
                        ->where('status', 'approved')
                        ->whereHas('step', fn (Builder $stepQuery) => $stepQuery
                            ->whereIn(DB::raw('LOWER(role_slug)'), ['manager_sale', 'manager', 'director'])));
            })
            ->where(fn (Builder $orderTypeQuery) => $orderTypeQuery
                ->whereNull('is_return_order')
                ->orWhere('is_return_order', false));

        if ($requested->isNotEmpty()) {
            $ids = $requested->filter(fn ($value) => ctype_digit((string) $value))->map(fn ($value) => (int) $value);
            $codes = $requested->reject(fn ($value) => ctype_digit((string) $value));
            $query->where(fn (Builder $selectedQuery) => $selectedQuery
                ->whereIn('id', $ids)
                ->orWhereIn('code', $codes));
        }

        $total = (clone $query)->count();
        if ($total === 0) {
            $this->warn('Không có đơn phù hợp để đồng bộ.');

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($total);
        $success = 0;
        $failed = 0;
        $query->orderBy('id')->chunkById(50, function ($orders) use ($service, &$success, &$failed, $bar): void {
            foreach ($orders as $order) {
                try {
                    $service->sync($order);
                    $success++;
                } catch (\Throwable $exception) {
                    $failed++;
                    report($exception);
                    $this->newLine();
                    $this->error(($order->code ?: '#'.$order->id).': '.$exception->getMessage());
                }
                $bar->advance();
            }
        });
        $bar->finish();
        $this->newLine(2);
        $this->info("Đồng bộ thành công {$success}/{$total} đơn; lỗi {$failed} đơn.");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
