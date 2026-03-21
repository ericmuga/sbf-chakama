<?php

namespace App\Services\Finance;

use App\Models\Finance\GlEntry;
use App\Models\Finance\PurchaseHeader;
use App\Models\Finance\VendorLedgerEntry;
use Illuminate\Support\Facades\DB;

class PurchasePostingService
{
    /**
     * Post a purchase invoice or credit memo to the vendor and G/L ledgers.
     *
     * @throws \RuntimeException
     */
    public function post(PurchaseHeader $header): void
    {
        if ($header->status === 'posted') {
            throw new \RuntimeException('Document is already posted.');
        }

        $header->load(['vendor.vendorPostingGroup', 'purchaseLines.service.servicePostingGroup']);

        $lines = $header->purchaseLines;

        if ($lines->isEmpty()) {
            throw new \RuntimeException('Cannot post a document with no lines.');
        }

        // Validate vendor posting group
        $payablesAccount = $header->vendor?->vendorPostingGroup?->payables_account_no;
        if (! $payablesAccount) {
            throw new \RuntimeException('Vendor posting group is missing or has no payables account configured.');
        }

        // Validate each line has a service with a posting group and expense account
        $isCreditMemo = strtolower($header->document_type ?? '') === 'credit_memo';

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

        $totalAmount = $lines->sum('line_amount');

        // Credit memos reverse the sign: debit payables, credit expense accounts
        $payablesSign = $isCreditMemo ? -1 : 1;

        DB::transaction(function () use ($header, $lines, $totalAmount, $payablesAccount, $isCreditMemo, $payablesSign): void {
            $nextEntryNo = (VendorLedgerEntry::lockForUpdate()->max('entry_no') ?? 0) + 1;

            // Vendor ledger entry
            VendorLedgerEntry::create([
                'entry_no' => $nextEntryNo,
                'vendor_id' => $header->vendor_id,
                'document_type' => $isCreditMemo ? 'credit_memo' : 'invoice',
                'document_no' => $header->no,
                'posting_date' => $header->posting_date,
                'due_date' => $header->due_date,
                'amount' => $payablesSign * $totalAmount,
                'remaining_amount' => $payablesSign * $totalAmount,
                'is_open' => true,
            ]);

            // G/L entry — credit payables (invoice) or debit payables (credit memo)
            GlEntry::create([
                'posting_date' => $header->posting_date,
                'document_no' => $header->no,
                'account_no' => $payablesAccount,
                'debit_amount' => $isCreditMemo ? $totalAmount : 0,
                'credit_amount' => $isCreditMemo ? 0 : $totalAmount,
                'source_type' => 'PurchaseHeader',
                'source_id' => $header->id,
            ]);

            // G/L entries — debit expense per line (invoice) or credit expense (credit memo)
            foreach ($lines as $line) {
                $expenseAccount = $line->service->servicePostingGroup->expense_account_no;
                GlEntry::create([
                    'posting_date' => $header->posting_date,
                    'document_no' => $header->no,
                    'account_no' => $expenseAccount,
                    'debit_amount' => $isCreditMemo ? 0 : $line->line_amount,
                    'credit_amount' => $isCreditMemo ? $line->line_amount : 0,
                    'source_type' => 'PurchaseLine',
                    'source_id' => $line->id,
                ]);
            }

            $header->update(['status' => 'posted']);
        });
    }
}
