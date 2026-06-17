<?php

namespace App\Services\Finance;

use App\Models\Finance\CustomerLedgerEntry;
use App\Models\Finance\GeneralPostingSetup;
use App\Models\Finance\GlEntry;
use App\Models\Finance\SalesHeader;
use Illuminate\Support\Facades\DB;

class SalesPostingService
{
    public function __construct(private LedgerApplicationService $ledgerApplicationService) {}

    /**
     * Post a sales invoice or credit memo to the customer and G/L ledgers.
     *
     * @throws \RuntimeException
     */
    public function post(SalesHeader $header): void
    {
        if ($header->isPosted()) {
            throw new \RuntimeException('Document is already posted.');
        }

        $header->load(['customer.customerPostingGroup', 'salesLines.service']);

        $lines = $header->salesLines;

        if ($lines->isEmpty()) {
            throw new \RuntimeException('Cannot post a document with no lines.');
        }

        // Validate customer posting group
        $receivablesAccount = $header->customer?->customerPostingGroup?->receivables_account_no;
        if (! $receivablesAccount) {
            throw new \RuntimeException('Customer posting group is missing or has no receivables account configured.');
        }

        // Validate each line has a general posting setup with a sales account
        foreach ($lines as $line) {
            if (! $line->service_posting_group_id) {
                $serviceName = $line->service?->description ?? "Line #{$line->line_no}";
                throw new \RuntimeException("Service \"{$serviceName}\" has no Service Posting Group configured.");
            }

            $gps = GeneralPostingSetup::where('customer_posting_group_id', $header->customer_posting_group_id)
                ->where('service_posting_group_id', $line->service_posting_group_id)
                ->first();

            if (! $gps) {
                $serviceName = $line->service?->description ?? "Line #{$line->line_no}";
                throw new \RuntimeException("No General Posting Setup found for service \"{$serviceName}\" with the selected customer posting group. Please configure it in General Posting Setups.");
            }

            if (! $gps->sales_account_no) {
                $serviceName = $line->service?->description ?? "Line #{$line->line_no}";
                throw new \RuntimeException("General Posting Setup for service \"{$serviceName}\" has no Sales Account configured.");
            }

            // Attach the resolved GPS id so the transaction can use it
            $line->general_posting_setup_id = $gps->id;
        }

        $totalAmount = $lines->sum('line_amount');

        // Credit memos reverse the sign: they reduce receivables, so the customer
        // ledger entry is negative and the G/L postings are the mirror of an invoice.
        $isCreditMemo = $header->isCreditMemo();
        $sign = $isCreditMemo ? -1 : 1;

        DB::transaction(function () use ($header, $lines, $totalAmount, $receivablesAccount, $isCreditMemo, $sign): void {
            $nextEntryNo = (CustomerLedgerEntry::lockForUpdate()->max('entry_no') ?? 0) + 1;

            // Customer ledger entry
            $ledgerEntry = CustomerLedgerEntry::create([
                'entry_no' => $nextEntryNo,
                'customer_id' => $header->customer_id,
                'document_type' => $isCreditMemo ? 'credit_memo' : 'invoice',
                'document_no' => $header->no,
                'posting_date' => $header->posting_date,
                'due_date' => $header->due_date,
                'amount' => $sign * $totalAmount,
                'remaining_amount' => $sign * $totalAmount,
                'is_open' => true,
                'created_by' => auth()->id(),
            ]);

            // G/L entry — debit receivables (invoice) or credit receivables (credit memo)
            GlEntry::create([
                'posting_date' => $header->posting_date,
                'document_no' => $header->no,
                'account_no' => $receivablesAccount,
                'debit_amount' => $isCreditMemo ? 0 : $totalAmount,
                'credit_amount' => $isCreditMemo ? $totalAmount : 0,
                'source_type' => 'SalesHeader',
                'source_id' => $header->id,
                'created_by' => auth()->id(),
            ]);

            // G/L entries — credit revenue (invoice) or debit revenue (credit memo) per line
            foreach ($lines as $line) {
                $gps = GeneralPostingSetup::find($line->general_posting_setup_id);
                GlEntry::create([
                    'posting_date' => $header->posting_date,
                    'document_no' => $header->no,
                    'account_no' => $gps->sales_account_no,
                    'debit_amount' => $isCreditMemo ? $line->line_amount : 0,
                    'credit_amount' => $isCreditMemo ? 0 : $line->line_amount,
                    'source_type' => 'SalesLine',
                    'source_id' => $line->id,
                    'created_by' => auth()->id(),
                ]);
            }

            $header->update(['status' => 'posted']);

            // Credit memo allocation: apply the (negative) credit memo entry against
            // the nominated open invoice, reducing its remaining balance.
            if ($isCreditMemo && $header->applies_to_doc_no) {
                $invoiceEntry = CustomerLedgerEntry::where('customer_id', $header->customer_id)
                    ->where('document_no', $header->applies_to_doc_no)
                    ->where('is_open', true)
                    ->where('amount', '>', 0)
                    ->first();

                if ($invoiceEntry) {
                    $this->ledgerApplicationService->applyCustomerEntries($ledgerEntry, [[
                        'customer_ledger_entry_id' => $invoiceEntry->id,
                        'amount_applied' => $totalAmount,
                    ]]);
                }
            }
        });
    }
}
