<?php

namespace App\Console\Commands;

use App\Models\FundAccount;
use App\Services\FundService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('funds:recalculate')]
#[Description('Recalculate all fund account balances from transactions')]
class RecalculateFundBalances extends Command
{
    public function handle(FundService $fundService): int
    {
        $count = 0;

        FundAccount::all()->each(function (FundAccount $fund) use ($fundService, &$count): void {
            $fundService->recalculateBalance($fund);
            $this->line("Recalculated balance for fund {$fund->no} — {$fund->name}.");
            $count++;
        });

        $this->info("Recalculated balances for {$count} fund account(s).");

        return Command::SUCCESS;
    }
}
