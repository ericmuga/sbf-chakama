<?php

namespace App\Services\Finance;

use App\Models\Finance\GlEntry;
use App\Models\Finance\VendorApplication;
use App\Models\Finance\VendorLedgerEntry;
use App\Models\Finance\VendorPayment;
use Illuminate\Support\Facades\DB;

class VendorPaymentPostingService
{
    /**
     * Post a vendor payment to the vendor and G/L ledgers, and apply to open purchase invoices.
     *
     * @param  array<int, array{vendor_ledger_entry_id: int, amount_applied: float}>  $allocations
     *
     * @throws \RuntimeException
     */
    public function post(VendorPayment $payment, array $allocations = []): void
    {
        if (strtolower($payment->status) === 'posted') {
            throw new \RuntimeException('Payment is already posted.');
        }

        if (! $payment->amount || $payment->amount <= 0) {
            throw new \RuntimeException('Cannot post a payment with no amount.');
        }

        DB::transaction(function () use ($payment, $allocations): void {
            $nextEntryNo = (VendorLedgerEntry::lockForUpdate()->max('entry_no') ?? 0) + 1;

            // Vendor ledger entry — payment (negative reduces payables)
            $paymentEntry = VendorLedgerEntry::create([
                'entry_no' => $nextEntryNo,
                'vendor_id' => $payment->vendor_id,
                'document_type' => 'payment',
                'document_no' => $payment->no,
                'posting_date' => $payment->posting_date,
                'due_date' => $payment->posting_date,
                'amount' => -$payment->amount,
                'remaining_amount' => -$payment->amount,
                'is_open' => true,
            ]);

            // G/L entry — debit payables (vendor owes less)
            $payablesGlNo = $payment->vendor?->vendorPostingGroup?->payables_account_no;
            if ($payablesGlNo) {
                GlEntry::create([
                    'posting_date' => $payment->posting_date,
                    'document_no' => $payment->no,
                    'account_no' => $payablesGlNo,
                    'debit_amount' => $payment->amount,
                    'credit_amount' => 0,
                    'source_type' => 'VendorPayment',
                    'source_id' => $payment->id,
                ]);
            }

            // G/L entry — credit bank account (money sent out)
            $bankGlNo = $payment->bankAccount?->bankPostingGroup?->bank_account_gl_no;
            if ($bankGlNo) {
                GlEntry::create([
                    'posting_date' => $payment->posting_date,
                    'document_no' => $payment->no,
                    'account_no' => $bankGlNo,
                    'debit_amount' => 0,
                    'credit_amount' => $payment->amount,
                    'source_type' => 'VendorPayment',
                    'source_id' => $payment->id,
                ]);
            }

            // Apply to open purchase invoices
            $paymentRemaining = -$payment->amount;

            foreach ($allocations as $allocation) {
                $amountApplied = (float) $allocation['amount_applied'];

                if ($amountApplied <= 0) {
                    continue;
                }

                $invoiceEntry = VendorLedgerEntry::lockForUpdate()->find($allocation['vendor_ledger_entry_id']);

                if (! $invoiceEntry || ! $invoiceEntry->is_open) {
                    continue;
                }

                // Clamp to the invoice remaining balance
                $amountApplied = min($amountApplied, (float) $invoiceEntry->remaining_amount);

                VendorApplication::create([
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

            $payment->update(['status' => 'posted']);
        });
    }
}
