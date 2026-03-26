<?php

namespace App\Services;

use App\Enums\FundTransactionType;
use App\Models\Finance\NumberSeries;
use App\Models\FundAccount;
use App\Models\FundTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FundService
{
    public function createFundAccount(array $data): FundAccount
    {
        $no = NumberSeries::generate('FUND');

        return FundAccount::create(array_merge($data, [
            'no' => $no,
            'number_series_code' => 'FUND',
        ]));
    }

    public function recordTransaction(
        FundAccount $fund,
        FundTransactionType $type,
        float $amount,
        string $description,
        ?Model $reference = null,
        ?string $documentNo = null,
        ?int $userId = null,
    ): FundTransaction {
        return DB::transaction(function () use ($fund, $type, $amount, $description, $reference, $documentNo, $userId) {
            $runningBalance = (float) $fund->balance + $amount;

            $transaction = FundTransaction::create([
                'fund_account_id' => $fund->id,
                'transaction_type' => $type,
                'description' => $description,
                'amount' => $amount,
                'running_balance' => $runningBalance,
                'reference_type' => $reference ? $reference->getMorphClass() : null,
                'reference_id' => $reference?->getKey(),
                'document_no' => $documentNo,
                'posting_date' => today(),
                'created_by' => $userId ?? auth()->id(),
            ]);

            $fund->balance = $runningBalance;
            $fund->save();

            return $transaction;
        });
    }

    public function getFundStatement(FundAccount $fund, ?Carbon $from = null, ?Carbon $to = null): Collection
    {
        $query = FundTransaction::where('fund_account_id', $fund->id);

        if ($from && $to) {
            $query->whereBetween('posting_date', [$from, $to]);
        } elseif ($from) {
            $query->where('posting_date', '>=', $from);
        } elseif ($to) {
            $query->where('posting_date', '<=', $to);
        }

        return $query->orderBy('created_at')->get();
    }

    public function recalculateBalance(FundAccount $fund): void
    {
        $balance = FundTransaction::where('fund_account_id', $fund->id)->sum('amount');
        $fund->balance = $balance;
        $fund->save();
    }
}
