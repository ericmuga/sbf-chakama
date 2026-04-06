<?php

namespace App\Console\Commands;

use App\Services\ShareBillingService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('chakama:generate-invoices')]
#[Description('Generate recurring share invoices for due subscriptions')]
class GenerateShareInvoices extends Command
{
    public function handle(ShareBillingService $shareBillingService): int
    {
        $count = $shareBillingService->generateRecurringInvoices();

        $this->info("Generated {$count} recurring share invoice(s).");

        return Command::SUCCESS;
    }
}
