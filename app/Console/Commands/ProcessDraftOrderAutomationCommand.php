<?php

namespace App\Console\Commands;

use App\Models\OrderScheduleRun;
use App\Models\TextOrderDraft;
use App\Services\DraftOrderAutomationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ProcessDraftOrderAutomationCommand extends Command
{
    protected $signature = 'order-drafts:process-automation
                            {--triggered-by= : User ID if triggered manually}
                            {--trigger-type=cron : cron or manual}';

    protected $description = 'Copy active order templates to orders using current prices';

    public function handle(DraftOrderAutomationService $service): int
    {
        $startedAt = microtime(true);
        $today = Carbon::today('Asia/Bangkok')->toDateString();
        $evaluated = 0;
        $generated = 0;
        $failed = 0;
        $errors = [];

        TextOrderDraft::query()
            ->where('draft_scope', TextOrderDraft::SCOPE_SALE_PRIVATE)
            ->where('automation_enabled', true)
            ->whereIn('automation_mode', [TextOrderDraft::AUTOMATION_DAILY, TextOrderDraft::AUTOMATION_SCHEDULED])
            ->orderBy('id')
            ->chunkById(100, function ($drafts) use ($service, $today, &$evaluated, &$generated, &$failed, &$errors) {
                foreach ($drafts as $draft) {
                    $dates = $draft->automation_mode === TextOrderDraft::AUTOMATION_DAILY
                        ? collect([$today])
                        : collect($draft->automation_dates ?: [])
                            ->filter(fn ($date) => is_string($date) && $date <= $today)
                            ->unique()
                            ->sort()
                            ->values();

                    $processedDates = $draft->automatedSchedules()
                        ->whereIn('schedule_date', $dates->all())
                        ->pluck('schedule_date')
                        ->map(fn ($date) => Carbon::parse($date)->toDateString());
                    $dates = $dates->diff($processedDates)->values();

                    foreach ($dates as $date) {
                        $evaluated++;
                        try {
                            $schedule = $service->generate($draft, $date);
                            if ($schedule->generated_order_id) {
                                $generated++;
                            }
                        } catch (\Throwable $exception) {
                            $failed++;
                            $errors[] = "#{$draft->id} ({$date}): {$exception->getMessage()}";
                            $draft->update(['automation_last_error' => $exception->getMessage()]);
                            $this->error(end($errors));
                        }
                    }
                }
            });

        OrderScheduleRun::query()->create([
            'triggered_by' => $this->option('triggered-by') ?: null,
            'trigger_type' => $this->option('trigger-type') ?: 'cron',
            'command_name' => 'order-drafts:process-automation',
            'status' => $failed > 0 ? 'failed' : 'success',
            'evaluated' => $evaluated,
            'generated' => $generated,
            'need_review' => 0,
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'error' => $errors ? implode(' | ', $errors) : null,
        ]);

        $this->info("Processed: {$evaluated}; Generated: {$generated}; Failed: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
