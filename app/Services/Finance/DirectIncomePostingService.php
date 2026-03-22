<?php

namespace App\Services\Finance;

use App\Models\Finance\BankLedgerEntry;
use App\Models\Finance\DirectIncome;
use App\Models\Finance\GlEntry;
use Illuminate\Support\Facades\DB;

class DirectIncomePostingService
{
    /**
     * Post a direct income to the G/L ledger.
     *
     * @throws \RuntimeException
     */
    public function post(DirectIncome $income): void
    {
        if ($income->status === 'posted') {
            throw new \RuntimeException('Document is already posted.');
        }

        $income->load(['lines.service.servicePostingGroup', 'bankAccount.bankPostingGroup']);

        $lines = $income->lines;

        if ($lines->isEmpty()) {
            throw new \RuntimeException('Cannot post a document with no lines.');
        }

        $bankGlNo = $income->bankAccount?->bankPostingGroup?->bank_account_gl_no;

        if (! $bankGlNo) {
            throw new \RuntimeException('Bank account posting group is missing or has no Bank Account G/L No configured.');
        }

        foreach ($lines as $line) {
            $spg = $line->service?->servicePostingGroup;

            if (! $spg) {
                $serviceName = $line->service?->description ?? "Line #{$line->line_no}";
                throw new \RuntimeException("Service \"{$serviceName}\" has no Service Posting Group configured.");
            }

            if (! $spg->revenue_account_no) {
                $serviceName = $line->service?->description ?? "Line #{$line->line_no}";
                throw new \RuntimeException("Service Posting Group for \"{$serviceName}\" has no Revenue Account configured.");
            }
        }

        $totalAmount = $lines->sum('amount');

        DB::transaction(function () use ($income, $lines, $totalAmount, $bankGlNo): void {
            // Bank ledger entry — money in
            $nextBankEntryNo = (BankLedgerEntry::lockForUpdate()->max('entry_no') ?? 0) + 1;
            BankLedgerEntry::create([
                'entry_no' => $nextBankEntryNo,
                'bank_account_id' => $income->bank_account_id,
                'document_type' => 'income',
                'document_no' => $income->no,
                'posting_date' => $income->posting_date,
                'description' => $income->description,
                'amount' => $totalAmount,
                'source_type' => 'DirectIncome',
                'source_id' => $income->id,
                'created_by' => auth()->id(),
            ]);
            // G/L entry — debit bank account
            GlEntry::create([
                'posting_date' => $income->posting_date,
                'document_no' => $income->no,
                'account_no' => $bankGlNo,
                'debit_amount' => $totalAmount,
                'credit_amount' => 0,
                'source_type' => 'DirectIncome',
                'source_id' => $income->id,
                'created_by' => auth()->id(),
            ]);

            // G/L entries — credit revenue account per line
            foreach ($lines as $line) {
                GlEntry::create([
                    'posting_date' => $income->posting_date,
                    'document_no' => $income->no,
                    'account_no' => $line->service->servicePostingGroup->revenue_account_no,
                    'debit_amount' => 0,
                    'credit_amount' => $line->amount,
                    'source_type' => 'DirectIncomeLine',
                    'source_id' => $line->id,
                    'created_by' => auth()->id(),
                ]);
            }

            $income->update(['status' => 'posted']);
        });
    }
}
