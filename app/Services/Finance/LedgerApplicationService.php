<?php

namespace App\Services\Finance;

use App\Models\Finance\CustomerApplication;
use App\Models\Finance\CustomerLedgerEntry;
use App\Models\Finance\VendorApplication;
use App\Models\Finance\VendorLedgerEntry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LedgerApplicationService
{
    /**
     * Apply customer ledger entries against a source entry.
     *
     * @param  array<int, array{customer_ledger_entry_id: int, amount_applied: float}>  $applications
     *
     * @throws \RuntimeException
     */
    public function applyCustomerEntries(CustomerLedgerEntry $sourceEntry, array $applications): void
    {
        if (! $sourceEntry->is_open) {
            throw new \RuntimeException('The source entry is already closed.');
        }

        DB::transaction(function () use ($sourceEntry, $applications): void {
            $source = CustomerLedgerEntry::lockForUpdate()->findOrFail($sourceEntry->id);
            $sourceRemaining = (float) $source->remaining_amount;

            foreach ($applications as $application) {
                $amountApplied = (float) ($application['amount_applied'] ?? 0);

                if ($amountApplied <= 0 || abs($sourceRemaining) < 0.0001) {
                    continue;
                }

                $target = CustomerLedgerEntry::lockForUpdate()->find($application['customer_ledger_entry_id']);

                if (! $target || ! $target->is_open) {
                    continue;
                }

                if ($target->customer_id !== $source->customer_id) {
                    throw new \RuntimeException("Entry #{$target->entry_no} belongs to a different customer.");
                }

                if (($source->amount > 0) === ($target->amount > 0)) {
                    throw new \RuntimeException("Entries #{$source->entry_no} and #{$target->entry_no} must have opposite signs to apply against each other.");
                }

                $amountApplied = min($amountApplied, abs((float) $target->remaining_amount), abs($sourceRemaining));

                [$paymentEntry, $invoiceEntry] = $source->amount < 0
                    ? [$source, $target]
                    : [$target, $source];

                CustomerApplication::create([
                    'payment_entry_id' => $paymentEntry->id,
                    'invoice_entry_id' => $invoiceEntry->id,
                    'amount_applied' => $amountApplied,
                ]);

                $targetRemaining = (float) $target->remaining_amount;
                $newTargetRemaining = $target->amount > 0
                    ? $targetRemaining - $amountApplied
                    : $targetRemaining + $amountApplied;

                $target->update([
                    'remaining_amount' => $newTargetRemaining,
                    'is_open' => $target->amount > 0 ? $newTargetRemaining > 0 : $newTargetRemaining < 0,
                ]);

                $sourceRemaining = $source->amount > 0
                    ? $sourceRemaining - $amountApplied
                    : $sourceRemaining + $amountApplied;
            }

            $source->update([
                'remaining_amount' => $sourceRemaining,
                'is_open' => $source->amount > 0 ? $sourceRemaining > 0 : $sourceRemaining < 0,
            ]);
        });
    }

    /**
     * Auto-apply a collection of customer ledger entries against each other.
     * Payments (negative) are applied to invoices (positive) in due-date order.
     *
     * @param  Collection<int, CustomerLedgerEntry>  $entries
     *
     * @throws \RuntimeException
     */
    public function bulkApplyCustomerEntries(Collection $entries): void
    {
        $customerIds = $entries->pluck('customer_id')->unique();
        if ($customerIds->count() > 1) {
            throw new \RuntimeException('All selected entries must belong to the same customer.');
        }

        $hasPositive = $entries->contains(fn ($e) => $e->amount > 0 && $e->is_open);
        $hasNegative = $entries->contains(fn ($e) => $e->amount < 0 && $e->is_open);

        if (! $hasPositive || ! $hasNegative) {
            throw new \RuntimeException('Selection must include both invoices (positive) and payments (negative).');
        }

        DB::transaction(function () use ($entries): void {
            $invoices = $entries
                ->filter(fn ($e) => $e->amount > 0 && $e->is_open)
                ->sortBy('due_date')
                ->values();

            $payments = $entries
                ->filter(fn ($e) => $e->amount < 0 && $e->is_open)
                ->sortBy('due_date')
                ->values();

            foreach ($payments as $payment) {
                $paymentEntry = CustomerLedgerEntry::lockForUpdate()->findOrFail($payment->id);
                $paymentRemaining = abs((float) $paymentEntry->remaining_amount);

                foreach ($invoices as $invoice) {
                    if ($paymentRemaining < 0.0001) {
                        break;
                    }

                    $invoiceEntry = CustomerLedgerEntry::lockForUpdate()->findOrFail($invoice->id);

                    if (! $invoiceEntry->is_open) {
                        continue;
                    }

                    $invoiceRemaining = (float) $invoiceEntry->remaining_amount;
                    $amountApplied = min($paymentRemaining, $invoiceRemaining);

                    if ($amountApplied < 0.0001) {
                        continue;
                    }

                    CustomerApplication::create([
                        'payment_entry_id' => $paymentEntry->id,
                        'invoice_entry_id' => $invoiceEntry->id,
                        'amount_applied' => $amountApplied,
                    ]);

                    $newInvoiceRemaining = $invoiceRemaining - $amountApplied;
                    $invoiceEntry->update([
                        'remaining_amount' => $newInvoiceRemaining,
                        'is_open' => $newInvoiceRemaining > 0,
                    ]);

                    // Update local variable so we do not over-apply in next iterations
                    $invoice->remaining_amount = $newInvoiceRemaining;
                    $invoice->is_open = $newInvoiceRemaining > 0;

                    $paymentRemaining -= $amountApplied;
                    $newPaymentRemaining = -$paymentRemaining;

                    $paymentEntry->update([
                        'remaining_amount' => $newPaymentRemaining,
                        'is_open' => $newPaymentRemaining < 0,
                    ]);
                }
            }
        });
    }

    /**
     * Apply vendor ledger entries against a source entry.
     *
     * @param  array<int, array{vendor_ledger_entry_id: int, amount_applied: float}>  $applications
     *
     * @throws \RuntimeException
     */
    public function applyVendorEntries(VendorLedgerEntry $sourceEntry, array $applications): void
    {
        if (! $sourceEntry->is_open) {
            throw new \RuntimeException('The source entry is already closed.');
        }

        DB::transaction(function () use ($sourceEntry, $applications): void {
            $source = VendorLedgerEntry::lockForUpdate()->findOrFail($sourceEntry->id);
            $sourceRemaining = (float) $source->remaining_amount;

            foreach ($applications as $application) {
                $amountApplied = (float) ($application['amount_applied'] ?? 0);

                if ($amountApplied <= 0 || abs($sourceRemaining) < 0.0001) {
                    continue;
                }

                $target = VendorLedgerEntry::lockForUpdate()->find($application['vendor_ledger_entry_id']);

                if (! $target || ! $target->is_open) {
                    continue;
                }

                if ($target->vendor_id !== $source->vendor_id) {
                    throw new \RuntimeException("Entry #{$target->entry_no} belongs to a different vendor.");
                }

                if (($source->amount > 0) === ($target->amount > 0)) {
                    throw new \RuntimeException("Entries #{$source->entry_no} and #{$target->entry_no} must have opposite signs to apply against each other.");
                }

                $amountApplied = min($amountApplied, abs((float) $target->remaining_amount), abs($sourceRemaining));

                [$paymentEntry, $invoiceEntry] = $source->amount < 0
                    ? [$source, $target]
                    : [$target, $source];

                VendorApplication::create([
                    'payment_entry_id' => $paymentEntry->id,
                    'invoice_entry_id' => $invoiceEntry->id,
                    'amount_applied' => $amountApplied,
                ]);

                $targetRemaining = (float) $target->remaining_amount;
                $newTargetRemaining = $target->amount > 0
                    ? $targetRemaining - $amountApplied
                    : $targetRemaining + $amountApplied;

                $target->update([
                    'remaining_amount' => $newTargetRemaining,
                    'is_open' => $target->amount > 0 ? $newTargetRemaining > 0 : $newTargetRemaining < 0,
                ]);

                $sourceRemaining = $source->amount > 0
                    ? $sourceRemaining - $amountApplied
                    : $sourceRemaining + $amountApplied;
            }

            $source->update([
                'remaining_amount' => $sourceRemaining,
                'is_open' => $source->amount > 0 ? $sourceRemaining > 0 : $sourceRemaining < 0,
            ]);
        });
    }

    /**
     * Auto-apply a collection of vendor ledger entries against each other.
     * Payments (negative) are applied to invoices (positive) in due-date order.
     *
     * @param  Collection<int, VendorLedgerEntry>  $entries
     *
     * @throws \RuntimeException
     */
    public function bulkApplyVendorEntries(Collection $entries): void
    {
        $vendorIds = $entries->pluck('vendor_id')->unique();
        if ($vendorIds->count() > 1) {
            throw new \RuntimeException('All selected entries must belong to the same vendor.');
        }

        $hasPositive = $entries->contains(fn ($e) => $e->amount > 0 && $e->is_open);
        $hasNegative = $entries->contains(fn ($e) => $e->amount < 0 && $e->is_open);

        if (! $hasPositive || ! $hasNegative) {
            throw new \RuntimeException('Selection must include both invoices (positive) and payments (negative).');
        }

        DB::transaction(function () use ($entries): void {
            $invoices = $entries
                ->filter(fn ($e) => $e->amount > 0 && $e->is_open)
                ->sortBy('due_date')
                ->values();

            $payments = $entries
                ->filter(fn ($e) => $e->amount < 0 && $e->is_open)
                ->sortBy('due_date')
                ->values();

            foreach ($payments as $payment) {
                $paymentEntry = VendorLedgerEntry::lockForUpdate()->findOrFail($payment->id);
                $paymentRemaining = abs((float) $paymentEntry->remaining_amount);

                foreach ($invoices as $invoice) {
                    if ($paymentRemaining < 0.0001) {
                        break;
                    }

                    $invoiceEntry = VendorLedgerEntry::lockForUpdate()->findOrFail($invoice->id);

                    if (! $invoiceEntry->is_open) {
                        continue;
                    }

                    $invoiceRemaining = (float) $invoiceEntry->remaining_amount;
                    $amountApplied = min($paymentRemaining, $invoiceRemaining);

                    if ($amountApplied < 0.0001) {
                        continue;
                    }

                    VendorApplication::create([
                        'payment_entry_id' => $paymentEntry->id,
                        'invoice_entry_id' => $invoiceEntry->id,
                        'amount_applied' => $amountApplied,
                    ]);

                    $newInvoiceRemaining = $invoiceRemaining - $amountApplied;
                    $invoiceEntry->update([
                        'remaining_amount' => $newInvoiceRemaining,
                        'is_open' => $newInvoiceRemaining > 0,
                    ]);

                    $invoice->remaining_amount = $newInvoiceRemaining;
                    $invoice->is_open = $newInvoiceRemaining > 0;

                    $paymentRemaining -= $amountApplied;
                    $newPaymentRemaining = -$paymentRemaining;

                    $paymentEntry->update([
                        'remaining_amount' => $newPaymentRemaining,
                        'is_open' => $newPaymentRemaining < 0,
                    ]);
                }
            }
        });
    }
}
