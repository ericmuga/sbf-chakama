<?php

namespace App\Services\Finance;

use App\Models\Finance\CustomerLedgerEntry;
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

        $lines = $header->salesLines()->with('generalPostingSetup')->get();

        if ($lines->isEmpty()) {
            throw new \RuntimeException('Cannot post a document with no lines.');
        }

        $totalAmount = $lines->sum('line_amount');

        DB::transaction(function () use ($header, $lines, $totalAmount): void {
            // Customer ledger entry
            CustomerLedgerEntry::create([
                'customer_id' => $header->customer_id,
                'document_type' => $header->document_type ?? 'invoice',
                'document_no' => $header->no,
                'posting_date' => $header->posting_date,
                'due_date' => $header->due_date,
                'amount' => $totalAmount,
                'remaining_amount' => $totalAmount,
                'is_open' => true,
            ]);

            // G/L entry — debit receivables
            $receivablesAccount = $header->customerPostingGroup?->receivables_account_no;
            if ($receivablesAccount) {
                GlEntry::create([
                    'posting_date' => $header->posting_date,
                    'document_no' => $header->no,
                    'account_no' => $receivablesAccount,
                    'debit_amount' => $totalAmount,
                    'credit_amount' => 0,
                    'source_type' => 'SalesHeader',
                    'source_id' => $header->id,
                ]);
            }

            // G/L entries — credit revenue per line
            foreach ($lines as $line) {
                $salesAccount = $line->generalPostingSetup?->sales_account_no;
                if ($salesAccount) {
                    GlEntry::create([
                        'posting_date' => $header->posting_date,
                        'document_no' => $header->no,
                        'account_no' => $salesAccount,
                        'debit_amount' => 0,
                        'credit_amount' => $line->line_amount,
                        'source_type' => 'SalesLine',
                        'source_id' => $line->id,
                    ]);
                }
            }

            $header->update(['status' => 'posted']);
        });
    }
}
