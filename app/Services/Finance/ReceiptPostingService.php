<?php

namespace App\Services\Finance;

use App\Models\Finance\CashReceipt;
use App\Models\Finance\CustomerLedgerEntry;
use App\Models\Finance\GlEntry;
use Illuminate\Support\Facades\DB;

class ReceiptPostingService
{
    /**
     * Post a cash receipt to the customer and G/L ledgers.
     *
     * @throws \RuntimeException
     */
    public function post(CashReceipt $receipt): void
    {
        if (strtolower($receipt->status) === 'posted') {
            throw new \RuntimeException('Receipt is already posted.');
        }

        if (! $receipt->amount || $receipt->amount <= 0) {
            throw new \RuntimeException('Cannot post a receipt with no amount.');
        }

        DB::transaction(function () use ($receipt): void {
            $nextEntryNo = (CustomerLedgerEntry::lockForUpdate()->max('entry_no') ?? 0) + 1;

            // Customer ledger entry — payment (negative amount reduces receivables)
            CustomerLedgerEntry::create([
                'entry_no' => $nextEntryNo,
                'customer_id' => $receipt->customer_id,
                'document_type' => 'payment',
                'document_no' => $receipt->no,
                'posting_date' => $receipt->posting_date,
                'due_date' => $receipt->posting_date,
                'amount' => -$receipt->amount,
                'remaining_amount' => -$receipt->amount,
                'is_open' => true,
            ]);

            // G/L entry — debit bank account (money received)
            $bankGlNo = $receipt->bankAccount?->bankPostingGroup?->bank_account_gl_no;
            if ($bankGlNo) {
                GlEntry::create([
                    'posting_date' => $receipt->posting_date,
                    'document_no' => $receipt->no,
                    'account_no' => $bankGlNo,
                    'debit_amount' => $receipt->amount,
                    'credit_amount' => 0,
                    'source_type' => 'CashReceipt',
                    'source_id' => $receipt->id,
                ]);
            }

            // G/L entry — credit receivables (customer owes less)
            $receivablesGlNo = $receipt->customer?->customerPostingGroup?->receivables_account_no;
            if ($receivablesGlNo) {
                GlEntry::create([
                    'posting_date' => $receipt->posting_date,
                    'document_no' => $receipt->no,
                    'account_no' => $receivablesGlNo,
                    'debit_amount' => 0,
                    'credit_amount' => $receipt->amount,
                    'source_type' => 'CashReceipt',
                    'source_id' => $receipt->id,
                ]);
            }

            $receipt->update(['status' => 'posted']);
        });
    }
}
