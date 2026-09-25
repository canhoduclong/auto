<?php

namespace App\Console\Commands;

use App\Models\DailyOrderSchedule;
use App\Models\OrderSchedule;
use App\Models\OrderScheduleRun;
use App\Services\OrderScheduleService;
use Illuminate\Console\Command;

class ProcessDailyOrderSchedulesCommand extends Command
{
    protected $signature = 'order-schedules:process-daily-rules
                            {--triggered-by= : User ID if triggered manually}
                            {--trigger-type=cron : cron or manual}';

    protected $description = 'Create today schedules from daily auto-order rules, then evaluate and generate/review them';

    public function handle(OrderScheduleService $service): int
    {
        $today = now()->toDateString();
        $startMs = (int) round(microtime(true) * 1000);
        $evaluated = 0;
        $generated = 0;
        $needReview = 0;
        $errorMsg = null;

        try {
            $rules = DailyOrderSchedule::query()
                ->with(['items'])
                ->where('is_active', true)
                ->whereDate('start_date', '<=', $today)
                ->where(function ($q) use ($today) {
                    $q->whereNull('last_processed_date')
                        ->orWhereDate('last_processed_date', '<', $today);
                })
                ->get();

            foreach ($rules as $rule) {
                $existing = OrderSchedule::query()
                    ->where('daily_order_schedule_id', $rule->id)
                    ->whereDate('schedule_date', $today)
                    ->first();

                if ($existing) {
                    $rule->update(['last_processed_date' => $today]);
                    continue;
                }

                $schedule = OrderSchedule::create([
                    'customer_id' => $rule->customer_id,
                    'daily_order_schedule_id' => $rule->id,
                    'schedule_date' => $today,
                    'status' => 'pending',
                    'price_status' => 'ok',
                    'stock_status' => 'ok',
                    'created_by' => $rule->created_by,
                    'is_active' => $rule->is_active,
                    'review_meta' => [
                        'source' => 'daily_order_schedule',
                        'daily_order_schedule_id' => $rule->id,
                        'approval_required' => (bool) $rule->approval_required,
                        'materialized_at' => now()->toDateTimeString(),
                    ],
                ]);

                foreach ($rule->items as $item) {
                    $schedule->items()->create([
                        'product_id' => (int) $item->product_id,
                        'product_variant_id' => (int) $item->product_variant_id,
                        'quantity' => (int) $item->quantity,
                        'scheduled_price' => (float) $item->scheduled_price,
                        'current_price' => (float) $item->scheduled_price,
                        'price_diff' => false,
                        'stock_available' => 0,
                        'stock_diff' => false,
                    ]);
                }

                if ($schedule->items()->count() === 0) {
                    $schedule->delete();
                    $rule->update(['last_processed_date' => $today]);
                    continue;
                }

                $evaluated++;
                $rule->update(['last_processed_date' => $today]);

                $result = $service->evaluateSchedule($schedule);

                if ($rule->approval_required) {
                    $reviewMeta = (array) ($schedule->review_meta ?? []);
                    $reviewMeta['approval_required'] = true;
                    $reviewMeta['requires_sale_confirmation'] = true;

                    $schedule->update([
                        'status' => 'need_review',
                        'review_meta' => $reviewMeta,
                    ]);

                    $needReview++;
                    continue;
                }

                if (($result['status'] ?? null) === 'approved') {
                    $order = $service->generateOrder($schedule, []);
                    if ($order) {
                        $generated++;
                        continue;
                    }

                    $schedule->update(['status' => 'need_review']);
                }

                $needReview++;
            }
        } catch (\Throwable $e) {
            $errorMsg = $e->getMessage();
            $this->error("Error: {$errorMsg}");
        }

        $durationMs = (int) round(microtime(true) * 1000) - $startMs;

        OrderScheduleRun::create([
            'triggered_by' => $this->option('triggered-by') ?: null,
            'trigger_type' => $this->option('trigger-type') ?: 'cron',
            'command_name' => 'order-schedules:process-daily-rules',
            'status' => $errorMsg ? 'failed' : 'success',
            'evaluated' => $evaluated,
            'generated' => $generated,
            'need_review' => $needReview,
            'duration_ms' => $durationMs,
            'error' => $errorMsg,
        ]);

        $this->info("Processed rules: {$evaluated}; Generated: {$generated}; Need review: {$needReview}; Duration: {$durationMs}ms");

        return $errorMsg ? self::FAILURE : self::SUCCESS;
    }
}