<?php

namespace App\Services\Finance;

use App\Models\Finance\BankLedgerEntry;
use App\Models\Finance\CashReceipt;
use App\Models\Finance\CustomerApplication;
use App\Models\Finance\CustomerLedgerEntry;
use App\Models\Finance\GlEntry;
use Illuminate\Support\Facades\DB;

class ReceiptPostingService
{
    /**
     * Post a cash receipt to the customer and G/L ledgers, and apply to open invoices.
     *
     * @param  array<int, array{customer_ledger_entry_id: int, amount_applied: float}>  $allocations
     *
     * @throws \RuntimeException
     */
    public function post(CashReceipt $receipt, array $allocations = []): void
    {
        if (strtolower($receipt->status) === 'posted') {
            throw new \RuntimeException('Receipt is already posted.');
        }

        if (! $receipt->amount || $receipt->amount <= 0) {
            throw new \RuntimeException('Cannot post a receipt with no amount.');
        }

        DB::transaction(function () use ($receipt, $allocations): void {
            $nextEntryNo = (CustomerLedgerEntry::lockForUpdate()->max('entry_no') ?? 0) + 1;

            // Customer ledger entry — payment (negative reduces receivables)
            $paymentEntry = CustomerLedgerEntry::create([
                'entry_no' => $nextEntryNo,
                'customer_id' => $receipt->customer_id,
                'document_type' => 'payment',
                'document_no' => $receipt->no,
                'posting_date' => $receipt->posting_date,
                'due_date' => $receipt->posting_date,
                'amount' => -$receipt->amount,
                'remaining_amount' => -$receipt->amount,
                'is_open' => true,
                'created_by' => auth()->id(),
            ]);

            // Bank ledger entry — money received
            if ($receipt->bank_account_id) {
                $nextBankEntryNo = (BankLedgerEntry::lockForUpdate()->max('entry_no') ?? 0) + 1;
                BankLedgerEntry::create([
                    'entry_no' => $nextBankEntryNo,
                    'bank_account_id' => $receipt->bank_account_id,
                    'document_type' => 'receipt',
                    'document_no' => $receipt->no,
                    'posting_date' => $receipt->posting_date,
                    'description' => $receipt->description,
                    'amount' => $receipt->amount,
                    'source_type' => 'CashReceipt',
                    'source_id' => $receipt->id,
                    'created_by' => auth()->id(),
                ]);
            }

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
                    'created_by' => auth()->id(),
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
                    'created_by' => auth()->id(),
                ]);
            }

            // Apply to open invoices
            $paymentRemaining = -$receipt->amount;

            foreach ($allocations as $allocation) {
                $amountApplied = (float) $allocation['amount_applied'];

                if ($amountApplied <= 0) {
                    continue;
                }

                $invoiceEntry = CustomerLedgerEntry::lockForUpdate()->find($allocation['customer_ledger_entry_id']);

                if (! $invoiceEntry || ! $invoiceEntry->is_open) {
                    continue;
                }

                // Clamp to the invoice remaining balance
                $amountApplied = min($amountApplied, (float) $invoiceEntry->remaining_amount);

                CustomerApplication::create([
                    'payment_entry_id' => $paymentEntry->id,
                    'invoice_entry_id' => $invoiceEntry->id,
                    'amount_applied' => $amountApplied,
                ]);

                // Reduce invoice remaining; close if fully paid
                $newInvoiceRemaining = (float) $invoiceEntry->remaining_amount - $amountApplied;
                $invoiceEntry->update([
                    'remaining_amount' => $newInvoiceRemaining,
                    'is_open' => $newInvoiceRemaining > 0,
                ]);

                // Reduce payment remaining (payment remaining is negative, add applied)
                $paymentRemaining += $amountApplied;
            }

            // Update payment entry remaining and close if fully applied
            $paymentEntry->update([
                'remaining_amount' => $paymentRemaining,
                'is_open' => $paymentRemaining < 0,
            ]);

            $receipt->update(['status' => 'posted']);
        });
    }
}
