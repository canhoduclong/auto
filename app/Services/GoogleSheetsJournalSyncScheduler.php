<?php

namespace App\Services;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GoogleSheetsJournalSyncScheduler
{
    /** @var array<string, true> */
    private static array $dates = [];

    private static bool $terminationCallbackRegistered = false;

    /**
     * @param  array<int, mixed>  $dates
     */
    public function scheduleDates(array $dates): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        $normalizedDates = collect($dates)
            ->filter()
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->unique()
            ->values()
            ->all();

        if ($normalizedDates === []) {
            return;
        }

        DB::afterCommit(function () use ($normalizedDates): void {
            foreach ($normalizedDates as $date) {
                self::$dates[$date] = true;
            }

            if (self::$terminationCallbackRegistered) {
                return;
            }

            self::$terminationCallbackRegistered = true;
            app()->terminating(function (): void {
                $dates = array_keys(self::$dates);
                self::$dates = [];
                self::$terminationCallbackRegistered = false;

                if ($dates === []) {
                    return;
                }

                try {
                    $googleSheets = app(GoogleSheetsJournalService::class);
                    if (! $googleSheets->isConfigured()) {
                        return;
                    }

                    $journal = app(CompletedSalesJournalService::class);
                    $rows = collect($dates)->flatMap(
                        fn (string $date) => $journal->all($date, $date, 0, 0, 'date_desc')
                    );

                    $googleSheets->syncJournalDates($rows, $dates);
                } catch (\Throwable $exception) {
                    report($exception);
                }
            });
        });
    }

    /**
     * @param  array<int, int|string|null>  $orderIds
     */
    public function scheduleOrderIds(array $orderIds): void
    {
        $dates = Order::query()
            ->whereIn('id', collect($orderIds)->filter()->unique())
            ->pluck('created_at')
            ->all();

        $this->scheduleDates($dates);
    }
}
