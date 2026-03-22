<?php

namespace App\Services\Finance;

use App\Models\Finance\CustomerLedgerEntry;
use App\Models\Finance\GeneralPostingSetup;
use App\Models\Finance\GlEntry;
use App\Models\Finance\SalesHeader;
use Illuminate\Support\Facades\DB;

class SalesPostingService
{
    /**
     * Post a sales invoice to the customer and G/L ledgers.
     *
     * @throws \RuntimeException
     */
    public function post(SalesHeader $header): void
    {
        if ($header->status === 'posted') {
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

        DB::transaction(function () use ($header, $lines, $totalAmount, $receivablesAccount): void {
            $nextEntryNo = (CustomerLedgerEntry::lockForUpdate()->max('entry_no') ?? 0) + 1;

            // Customer ledger entry
            CustomerLedgerEntry::create([
                'entry_no' => $nextEntryNo,
                'customer_id' => $header->customer_id,
                'document_type' => $header->document_type ?? 'invoice',
                'document_no' => $header->no,
                'posting_date' => $header->posting_date,
                'due_date' => $header->due_date,
                'amount' => $totalAmount,
                'remaining_amount' => $totalAmount,
                'is_open' => true,
                'created_by' => auth()->id(),
            ]);

            // G/L entry — debit receivables (already validated above)
            GlEntry::create([
                'posting_date' => $header->posting_date,
                'document_no' => $header->no,
                'account_no' => $receivablesAccount,
                'debit_amount' => $totalAmount,
                'credit_amount' => 0,
                'source_type' => 'SalesHeader',
                'source_id' => $header->id,
                'created_by' => auth()->id(),
            ]);

            // G/L entries — credit revenue per line (GPS already resolved and validated)
            foreach ($lines as $line) {
                $gps = GeneralPostingSetup::find($line->general_posting_setup_id);
                GlEntry::create([
                    'posting_date' => $header->posting_date,
                    'document_no' => $header->no,
                    'account_no' => $gps->sales_account_no,
                    'debit_amount' => 0,
                    'credit_amount' => $line->line_amount,
                    'source_type' => 'SalesLine',
                    'source_id' => $line->id,
                    'created_by' => auth()->id(),
                ]);
            }

            $header->update(['status' => 'posted']);
        });
    }
}
