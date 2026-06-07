<?php

namespace App\Console\Commands;

use App\Jobs\ProcessShareBillingRunJob;
use App\Models\ShareBillingRun;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('chakama:process-billing-runs')]
#[Description('Dispatch billing run jobs for all draft runs whose billing date has arrived')]
class ProcessDueShareBillingRuns extends Command
{
    public function handle(): int
    {
        $runs = ShareBillingRun::query()
            ->where('status', 'draft')
            ->whereDate('billing_date', '<=', today())
            ->get();

        if ($runs->isEmpty()) {
            $this->info('No due billing runs found.');

            return Command::SUCCESS;
        }

        foreach ($runs as $run) {
            ProcessShareBillingRunJob::dispatch($run->id);
            $this->info("Dispatched billing run #{$run->id}: {$run->title}");
        }

        $this->info("Dispatched {$runs->count()} billing run job(s).");

        return Command::SUCCESS;
    }
}
