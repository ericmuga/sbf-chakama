<?php

namespace App\Services\Finance;

use App\Models\Finance\BankLedgerEntry;
use App\Models\Finance\DirectExpense;
use App\Models\Finance\GlEntry;
use Illuminate\Support\Facades\DB;

class DirectExpensePostingService
{
    /**
     * Post a direct expense to the G/L ledger.
     *
     * @throws \RuntimeException
     */
    public function post(DirectExpense $expense): void
    {
        if ($expense->status === 'posted') {
            throw new \RuntimeException('Document is already posted.');
        }

        $expense->load(['lines.service.servicePostingGroup', 'bankAccount.bankPostingGroup']);

        $lines = $expense->lines;

        if ($lines->isEmpty()) {
            throw new \RuntimeException('Cannot post a document with no lines.');
        }

        $bankGlNo = $expense->bankAccount?->bankPostingGroup?->bank_account_gl_no;

        if (! $bankGlNo) {
            throw new \RuntimeException('Bank account posting group is missing or has no Bank Account G/L No configured.');
        }

        foreach ($lines as $line) {
            $spg = $line->service?->servicePostingGroup;

            if (! $spg) {
                $serviceName = $line->service?->description ?? "Line #{$line->line_no}";
                throw new \RuntimeException("Service \"{$serviceName}\" has no Service Posting Group configured.");
            }

            if (! $spg->expense_account_no) {
                $serviceName = $line->service?->description ?? "Line #{$line->line_no}";
                throw new \RuntimeException("Service Posting Group for \"{$serviceName}\" has no Expense Account configured.");
            }
        }

        $totalAmount = $lines->sum('amount');

        DB::transaction(function () use ($expense, $lines, $totalAmount, $bankGlNo): void {
            // Bank ledger entry — money out
            $nextBankEntryNo = (BankLedgerEntry::lockForUpdate()->max('entry_no') ?? 0) + 1;
            BankLedgerEntry::create([
                'entry_no' => $nextBankEntryNo,
                'bank_account_id' => $expense->bank_account_id,
                'document_type' => 'expense',
                'document_no' => $expense->no,
                'posting_date' => $expense->posting_date,
                'description' => $expense->description,
                'amount' => -$totalAmount,
                'source_type' => 'DirectExpense',
                'source_id' => $expense->id,
                'created_by' => auth()->id(),
            ]);
            // G/L entries — debit expense account per line
            foreach ($lines as $line) {
                GlEntry::create([
                    'posting_date' => $expense->posting_date,
                    'document_no' => $expense->no,
                    'account_no' => $line->service->servicePostingGroup->expense_account_no,
                    'debit_amount' => $line->amount,
                    'credit_amount' => 0,
                    'source_type' => 'DirectExpenseLine',
                    'source_id' => $line->id,
                    'created_by' => auth()->id(),
                ]);
            }

            // G/L entry — credit bank account
            GlEntry::create([
                'posting_date' => $expense->posting_date,
                'document_no' => $expense->no,
                'account_no' => $bankGlNo,
                'debit_amount' => 0,
                'credit_amount' => $totalAmount,
                'source_type' => 'DirectExpense',
                'source_id' => $expense->id,
                'created_by' => auth()->id(),
            ]);

            $expense->update(['status' => 'posted']);
        });
    }
}
