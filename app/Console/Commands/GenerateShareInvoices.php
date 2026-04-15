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
        $recurring = $shareBillingService->generateRecurringInvoices();
        $scheduled = $shareBillingService->generateScheduledSubscriptionInvoices();

        $this->info("Generated {$recurring} recurring + {$scheduled} scheduled share invoice(s).");

        return Command::SUCCESS;
    }
}
