<?php

namespace App\Console\Commands;

use App\Models\OrderSchedule;
use App\Models\OrderScheduleRun;
use App\Services\OrderScheduleService;
use Illuminate\Console\Command;

class EvaluateOrderSchedulesCommand extends Command
{
    protected $signature = 'order-schedules:evaluate-today
                            {--triggered-by= : User ID if triggered manually}
                            {--trigger-type=cron : cron or manual}';
    protected $description = 'Evaluate today order schedules, check price/stock and auto generate valid schedules';

    public function handle(OrderScheduleService $service): int
    {
        $startMs = (int) round(microtime(true) * 1000);

        $schedules = OrderSchedule::query()
            ->whereDate('schedule_date', now()->toDateString())
            ->where('status', 'pending')
            ->whereNull('generated_order_id')
            ->get();

        $evaluated  = 0;
        $generated  = 0;
        $needReview = 0;
        $errorMsg   = null;

        try {
            foreach ($schedules as $schedule) {
                $result = $service->evaluateSchedule($schedule);
                $evaluated++;

                if (($result['status'] ?? null) === 'approved') {
                    $order = $service->generateOrder($schedule, []);
                    if ($order) {
                        $generated++;
                    }
                } else {
                    $needReview++;
                }
            }
        } catch (\Throwable $e) {
            $errorMsg = $e->getMessage();
            $this->error("Error: {$errorMsg}");
        }

        $durationMs = (int) round(microtime(true) * 1000) - $startMs;

        OrderScheduleRun::create([
            'triggered_by' => $this->option('triggered-by') ?: null,
            'trigger_type' => $this->option('trigger-type') ?: 'cron',
            'status'       => $errorMsg ? 'failed' : 'success',
            'evaluated'    => $evaluated,
            'generated'    => $generated,
            'need_review'  => $needReview,
            'duration_ms'  => $durationMs,
            'error'        => $errorMsg,
        ]);

        $this->info("Evaluated: {$evaluated}; Generated: {$generated}; Need review: {$needReview}; Duration: {$durationMs}ms");

        return $errorMsg ? self::FAILURE : self::SUCCESS;
    }
}
