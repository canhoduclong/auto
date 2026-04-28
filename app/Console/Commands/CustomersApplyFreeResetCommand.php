<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Services\CustomerPriorityService;
use Illuminate\Console\Command;

class CustomersApplyFreeResetCommand extends Command
{
    protected $signature = 'customers:apply-free-reset {--chunk=200 : Chunk size for processing customers}';

    protected $description = 'Apply free-customer reset based on inactivity days and lifecycle rules';

    public function handle(CustomerPriorityService $priorityService): int
    {
        $chunkSize = max((int) $this->option('chunk'), 50);
        $processed = 0;
        $reset = 0;

        Customer::query()
            ->where('is_employee', false)
            ->whereNotNull('assigned_to')
            ->orderBy('id')
            ->chunkById($chunkSize, function ($customers) use ($priorityService, &$processed, &$reset) {
                foreach ($customers as $customer) {
                    $processed++;
                    if ($priorityService->applyFreeCustomerReset($customer)) {
                        $reset++;
                    }
                }
            });

        $this->info("Processed {$processed} customers. Reset {$reset} customers to free state.");

        return self::SUCCESS;
    }
}
