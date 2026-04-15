<?php

namespace Database\Seeders;

use App\Models\Finance\BankLedgerEntry;
use App\Models\Finance\CashReceipt;
use App\Models\Finance\CustomerLedgerEntry;
use App\Models\Finance\GlEntry;
use App\Models\Finance\NumberSeries;
use App\Models\Finance\VendorLedgerEntry;
use App\Models\Finance\VendorPayment;
use App\Models\ShareSubscription;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReportsDemoSeeder extends Seeder
{
    // ─── Existing IDs ────────────────────────────────────────────────────────
    // Customers:  3=Amina, 4=John, 5=Grace, 6=Test4
    // Vendors:    1=Nairobi Office Supplies, 2=Kenya Power, 3=Amina, 4=John, 5=Grace
    // Bank accts: 1=KCB, 2=Equity, 3=M-Pesa, 4=Cash
    // Pay methods:1=Cash, 2=Cheque, 3=M-Pesa, 4=Card
    // Posting grp:1=MEMBER, 2=DOMESTIC

    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedMemberInvoicesAndPayments();
            $this->seedVendorPayments();
            $this->seedGlEntries();
        });
    }

    // ─── 1. Member invoices + receipts → CustomerLedgerEntry ─────────────────

    private function seedMemberInvoicesAndPayments(): void
    {
        $members = [
            ['customer_id' => 3, 'name' => 'Amina Wanjiru'],
            ['customer_id' => 4, 'name' => 'John Mwangi'],
            ['customer_id' => 5, 'name' => 'Grace Ochieng'],
            ['customer_id' => 6, 'name' => 'Test 4'],
        ];

        $entryNo = (CustomerLedgerEntry::max('entry_no') ?? 0) + 1;
        $receiptSeq = (int) preg_replace('/\D/', '', CashReceipt::max('no') ?? 'RCPT-000000') + 1;

        // Invoices: 3 months × 4 members — annual fee + admin fee
        $invoices = [
            ['month' => '2026-01', 'desc' => 'Annual Membership — Jan 2026', 'amount' => 5000, 'doc_prefix' => 'INV-JAN-'],
            ['month' => '2026-02', 'desc' => 'Annual Membership — Feb 2026', 'amount' => 5000, 'doc_prefix' => 'INV-FEB-'],
            ['month' => '2026-03', 'desc' => 'Annual Membership — Mar 2026', 'amount' => 5000, 'doc_prefix' => 'INV-MAR-'],
        ];

        // Payments: Jan + Feb paid, Mar outstanding
        $payments = [
            ['month' => '2026-01', 'doc_prefix' => 'PMT-JAN-', 'amount' => 5000, 'method_id' => 3, 'bank_id' => 3],
            ['month' => '2026-02', 'doc_prefix' => 'PMT-FEB-', 'amount' => 5000, 'method_id' => 1, 'bank_id' => 4],
        ];

        foreach ($members as $idx => $member) {
            $seq = str_pad($idx + 1, 3, '0', STR_PAD_LEFT);

            // ── Invoices ──────────────────────────────────────────────
            foreach ($invoices as $inv) {
                $date = Carbon::parse("{$inv['month']}-01");
                $docNo = $inv['doc_prefix'].$seq;
                $isPaid = $inv['month'] !== '2026-03'; // Mar still open

                CustomerLedgerEntry::create([
                    'entry_no' => $entryNo++,
                    'customer_id' => $member['customer_id'],
                    'document_type' => 'invoice',
                    'document_no' => $docNo,
                    'posting_date' => $date,
                    'due_date' => $date->copy()->addDays(30),
                    'amount' => $inv['amount'],
                    'remaining_amount' => $isPaid ? 0 : $inv['amount'],
                    'is_open' => ! $isPaid,
                    'created_by' => 1,
                ]);
            }

            // ── Receipts ──────────────────────────────────────────────
            foreach ($payments as $pmt) {
                $date = Carbon::parse("{$pmt['month']}-15");
                $docNo = $pmt['doc_prefix'].$seq;
                $rcptNo = 'RCPT-'.str_pad($receiptSeq++, 6, '0', STR_PAD_LEFT);

                CustomerLedgerEntry::create([
                    'entry_no' => $entryNo++,
                    'customer_id' => $member['customer_id'],
                    'document_type' => 'payment',
                    'document_no' => $docNo,
                    'posting_date' => $date,
                    'due_date' => $date,
                    'amount' => -$pmt['amount'],
                    'remaining_amount' => 0,
                    'is_open' => false,
                    'created_by' => 1,
                ]);

                CashReceipt::create([
                    'no' => $rcptNo,
                    'number_series_code' => 'RCPT',
                    'customer_id' => $member['customer_id'],
                    'bank_account_id' => $pmt['bank_id'],
                    'payment_method_id' => $pmt['method_id'],
                    'posting_date' => $date,
                    'amount' => $pmt['amount'],
                    'description' => "Payment — {$member['name']} — {$date->format('M Y')}",
                    'mpesa_receipt_no' => $pmt['method_id'] === 3
                        ? 'QA'.strtoupper(substr(md5($rcptNo), 0, 9))
                        : null,
                    'mpesa_phone' => $pmt['method_id'] === 3 ? '0712345678' : null,
                    'status' => 'posted',
                ]);

                BankLedgerEntry::create([
                    'entry_no' => (BankLedgerEntry::max('entry_no') ?? 0) + 1,
                    'bank_account_id' => $pmt['bank_id'],
                    'document_type' => 'payment',
                    'document_no' => $docNo,
                    'posting_date' => $date,
                    'description' => "Member receipt — {$member['name']}",
                    'amount' => $pmt['amount'],
                    'source_type' => 'CashReceipt',
                    'source_id' => CashReceipt::where('no', $rcptNo)->value('id'),
                    'created_by' => 1,
                ]);
            }

            // ── Share subscription payment (Chakama members) ──────────
            if (in_array($member['customer_id'], [3, 4, 6])) {
                $date = Carbon::parse('2026-01-20');
                $rcptNo = 'RCPT-'.str_pad($receiptSeq++, 6, '0', STR_PAD_LEFT);

                CustomerLedgerEntry::create([
                    'entry_no' => $entryNo++,
                    'customer_id' => $member['customer_id'],
                    'document_type' => 'payment',
                    'document_no' => 'SHRPMT-'.$seq,
                    'posting_date' => $date,
                    'due_date' => $date,
                    'amount' => -100000,
                    'remaining_amount' => 0,
                    'is_open' => false,
                    'created_by' => 1,
                ]);

                CashReceipt::create([
                    'no' => $rcptNo,
                    'number_series_code' => 'RCPT',
                    'customer_id' => $member['customer_id'],
                    'bank_account_id' => 3,
                    'payment_method_id' => 3,
                    'posting_date' => $date,
                    'amount' => 100000,
                    'description' => "Share payment — {$member['name']}",
                    'mpesa_receipt_no' => 'QA'.strtoupper(substr(md5($rcptNo), 0, 9)),
                    'mpesa_phone' => '0712345678',
                    'status' => 'posted',
                ]);
            }
        }
    }

    // ─── 2. Vendor payments → VendorLedgerEntry ──────────────────────────────

    private function seedVendorPayments(): void
    {
        $disbursements = [
            ['vendor_id' => 1, 'desc' => 'Office Supplies Q1',     'amount' => 18500,  'date' => '2026-01-10', 'bank' => 1, 'method' => 2, 'doc' => 'VEXP-001'],
            ['vendor_id' => 2, 'desc' => 'Electricity — Jan 2026', 'amount' => 9200,   'date' => '2026-01-25', 'bank' => 1, 'method' => 2, 'doc' => 'VEXP-002'],
            ['vendor_id' => 1, 'desc' => 'Stationery & Printing',  'amount' => 6300,   'date' => '2026-02-05', 'bank' => 4, 'method' => 1, 'doc' => 'VEXP-003'],
            ['vendor_id' => 2, 'desc' => 'Electricity — Feb 2026', 'amount' => 8750,   'date' => '2026-02-25', 'bank' => 1, 'method' => 2, 'doc' => 'VEXP-004'],
            ['vendor_id' => 3, 'desc' => 'Medical Claim — Amina',  'amount' => 45000,  'date' => '2026-02-14', 'bank' => 1, 'method' => 2, 'doc' => 'CLM-001'],
            ['vendor_id' => 5, 'desc' => 'Emergency Relief — Grace', 'amount' => 30000, 'date' => '2026-03-01', 'bank' => 1, 'method' => 2, 'doc' => 'CLM-002'],
            ['vendor_id' => 1, 'desc' => 'AGM Expenses',           'amount' => 22000,  'date' => '2026-03-10', 'bank' => 1, 'method' => 2, 'doc' => 'VEXP-005'],
            ['vendor_id' => 2, 'desc' => 'Electricity — Mar 2026', 'amount' => 9100,   'date' => '2026-03-25', 'bank' => 1, 'method' => 2, 'doc' => 'VEXP-006'],
            ['vendor_id' => 1, 'desc' => 'Audit Fees FY2025',      'amount' => 75000,  'date' => '2026-04-02', 'bank' => 1, 'method' => 2, 'doc' => 'VEXP-007'],
        ];

        $vpaySeq = (int) preg_replace('/\D/', '', VendorPayment::max('no') ?? 'VPAY-000000') + 1;
        $vleEntryNo = (VendorLedgerEntry::max('entry_no') ?? 0) + 1;

        foreach ($disbursements as $d) {
            $date = Carbon::parse($d['date']);
            $vpNo = 'VPAY-'.str_pad($vpaySeq++, 6, '0', STR_PAD_LEFT);

            $vp = VendorPayment::create([
                'no' => $vpNo,
                'number_series_code' => 'VPAY',
                'vendor_id' => $d['vendor_id'],
                'bank_account_id' => $d['bank'],
                'payment_method_id' => $d['method'],
                'posting_date' => $date,
                'amount' => $d['amount'],
                'status' => 'posted',
            ]);

            VendorLedgerEntry::create([
                'entry_no' => $vleEntryNo++,
                'vendor_id' => $d['vendor_id'],
                'document_type' => 'payment',
                'document_no' => $vpNo,
                'posting_date' => $date,
                'due_date' => $date,
                'amount' => -$d['amount'],
                'remaining_amount' => 0,
                'is_open' => false,
                'created_by' => 1,
            ]);

            BankLedgerEntry::create([
                'entry_no' => (BankLedgerEntry::max('entry_no') ?? 0) + 1,
                'bank_account_id' => $d['bank'],
                'document_type' => 'payment',
                'document_no' => $vpNo,
                'posting_date' => $date,
                'description' => $d['desc'],
                'amount' => -$d['amount'],
                'source_type' => 'VendorPayment',
                'source_id' => $vp->id,
                'created_by' => 1,
            ]);
        }
    }

    // ─── 3. GL entries (double-entry) ────────────────────────────────────────

    private function seedGlEntries(): void
    {
        // Each entry: [date, doc_no, debit_account, credit_account, amount, description]
        $transactions = [
            // ── Opening bank balance ──────────────────────────────────────────
            ['2026-01-01', 'OB-001', '1050', null, 750000, null, 'Opening balance — KCB'],
            ['2026-01-01', 'OB-002', '1055', null, 200000, null, 'Opening balance — M-Pesa'],
            ['2026-01-01', 'OB-003', '1010', null, 50000,  null, 'Opening balance — Cash'],

            // ── Member registration / annual fees (revenue) ───────────────────
            ['2026-01-01', 'INV-JAN-001', '1100', '4000', 5000,  null, 'Annual fee — Amina'],
            ['2026-01-01', 'INV-JAN-002', '1100', '4000', 5000,  null, 'Annual fee — John'],
            ['2026-01-01', 'INV-JAN-003', '1100', '4000', 5000,  null, 'Annual fee — Grace'],
            ['2026-01-01', 'INV-JAN-004', '1100', '4000', 5000,  null, 'Annual fee — Test 4'],

            ['2026-02-01', 'INV-FEB-001', '1100', '4000', 5000,  null, 'Annual fee — Amina Feb'],
            ['2026-02-01', 'INV-FEB-002', '1100', '4000', 5000,  null, 'Annual fee — John Feb'],
            ['2026-02-01', 'INV-FEB-003', '1100', '4000', 5000,  null, 'Annual fee — Grace Feb'],
            ['2026-02-01', 'INV-FEB-004', '1100', '4000', 5000,  null, 'Annual fee — Test 4 Feb'],

            ['2026-03-01', 'INV-MAR-001', '1100', '4000', 5000,  null, 'Annual fee — Amina Mar'],
            ['2026-03-01', 'INV-MAR-002', '1100', '4000', 5000,  null, 'Annual fee — John Mar'],
            ['2026-03-01', 'INV-MAR-003', '1100', '4000', 5000,  null, 'Annual fee — Grace Mar'],
            ['2026-03-01', 'INV-MAR-004', '1100', '4000', 5000,  null, 'Annual fee — Test 4 Mar'],

            // ── Share subscription revenue (Chakama) ──────────────────────────
            ['2026-01-15', 'SHRPMT-001', '1100', '4200', 100000, null, 'Share subscription — Amina'],
            ['2026-01-20', 'SHRPMT-002', '1100', '4200', 100000, null, 'Share subscription — John'],
            ['2026-01-20', 'SHRPMT-006', '1100', '4200', 100000, null, 'Share subscription — Test 4'],

            // ── Cash receipts (bank debits AR credits) ────────────────────────
            ['2026-01-15', 'PMT-JAN-001', '1055', '1100', 5000,  null, 'M-Pesa — Amina Jan'],
            ['2026-01-15', 'PMT-JAN-002', '1055', '1100', 5000,  null, 'M-Pesa — John Jan'],
            ['2026-01-15', 'PMT-JAN-003', '1055', '1100', 5000,  null, 'M-Pesa — Grace Jan'],
            ['2026-01-15', 'PMT-JAN-004', '1055', '1100', 5000,  null, 'M-Pesa — Test4 Jan'],
            ['2026-01-20', 'SHRPMT-001',  '1055', '1100', 100000, null, 'Share receipt — Amina'],
            ['2026-01-20', 'SHRPMT-002',  '1055', '1100', 100000, null, 'Share receipt — John'],
            ['2026-01-20', 'SHRPMT-006',  '1055', '1100', 100000, null, 'Share receipt — Test4'],

            ['2026-02-15', 'PMT-FEB-001', '1010', '1100', 5000,  null, 'Cash — Amina Feb'],
            ['2026-02-15', 'PMT-FEB-002', '1010', '1100', 5000,  null, 'Cash — John Feb'],
            ['2026-02-15', 'PMT-FEB-003', '1010', '1100', 5000,  null, 'Cash — Grace Feb'],
            ['2026-02-15', 'PMT-FEB-004', '1010', '1100', 5000,  null, 'Cash — Test4 Feb'],

            // ── Expenses (posted to GL) ───────────────────────────────────────
            ['2026-01-10', 'VEXP-001', '5410', '2100', 18500, null, 'Office Supplies Q1'],
            ['2026-01-25', 'VEXP-002', '5430', '2100', 9200,  null, 'Electricity Jan'],
            ['2026-02-05', 'VEXP-003', '5410', '2100', 6300,  null, 'Stationery Feb'],
            ['2026-02-14', 'CLM-001',  '5210', '2100', 45000, null, 'Medical claim — Amina'],
            ['2026-02-25', 'VEXP-004', '5430', '2100', 8750,  null, 'Electricity Feb'],
            ['2026-03-01', 'CLM-002',  '5230', '2100', 30000, null, 'Emergency relief — Grace'],
            ['2026-03-10', 'VEXP-005', '5100', '2100', 22000, null, 'AGM Expenses'],
            ['2026-03-25', 'VEXP-006', '5430', '2100', 9100,  null, 'Electricity Mar'],
            ['2026-04-02', 'VEXP-007', '5100', '2100', 75000, null, 'Audit Fees FY2025'],

            // ── Vendor payments (AP cleared) ──────────────────────────────────
            ['2026-01-10', 'VPAY-000002', '2100', '1050', 18500, null, 'Pay — Office Supplies'],
            ['2026-01-25', 'VPAY-000003', '2100', '1050', 9200,  null, 'Pay — Electricity Jan'],
            ['2026-02-05', 'VPAY-000004', '2100', '1010', 6300,  null, 'Pay — Stationery'],
            ['2026-02-14', 'VPAY-000005', '2100', '1050', 45000, null, 'Pay — Medical claim'],
            ['2026-02-25', 'VPAY-000006', '2100', '1050', 8750,  null, 'Pay — Electricity Feb'],
            ['2026-03-01', 'VPAY-000007', '2100', '1050', 30000, null, 'Pay — Emergency relief'],
            ['2026-03-10', 'VPAY-000008', '2100', '1050', 22000, null, 'Pay — AGM'],
            ['2026-03-25', 'VPAY-000009', '2100', '1050', 9100,  null, 'Pay — Electricity Mar'],
            ['2026-04-02', 'VPAY-000010', '2100', '1050', 75000, null, 'Pay — Audit Fees'],

            // ── Bank interest income ──────────────────────────────────────────
            ['2026-01-31', 'BANK-INT-JAN', '1050', '4100', 3200,  null, 'Bank interest Jan'],
            ['2026-02-28', 'BANK-INT-FEB', '1050', '4100', 2900,  null, 'Bank interest Feb'],
            ['2026-03-31', 'BANK-INT-MAR', '1050', '4100', 3100,  null, 'Bank interest Mar'],
        ];

        // ── Sync number series counters after seeding ─────────────────────────
        $this->syncNumberSeries();

        foreach ($transactions as [$date, $docNo, $debitAcc, $creditAcc, $amount, $proj, $desc]) {
            // Debit leg
            GlEntry::create([
                'posting_date' => Carbon::parse($date),
                'document_no' => $docNo,
                'account_no' => $debitAcc,
                'debit_amount' => $amount,
                'credit_amount' => 0,
                'source_type' => 'Seeder',
                'source_id' => 0,
                'created_by' => 1,
            ]);

            // Credit leg (if specified)
            if ($creditAcc) {
                GlEntry::create([
                    'posting_date' => Carbon::parse($date),
                    'document_no' => $docNo,
                    'account_no' => $creditAcc,
                    'debit_amount' => 0,
                    'credit_amount' => $amount,
                    'source_type' => 'Seeder',
                    'source_id' => 0,
                    'created_by' => 1,
                ]);
            }
        }
    }

    private function syncNumberSeries(): void
    {
        $map = [
            'RCPT' => fn () => (int) preg_replace('/\D/', '', CashReceipt::max('no') ?? '0'),
            'VPAY' => fn () => (int) preg_replace('/\D/', '', VendorPayment::max('no') ?? '0'),
            'SHARE' => fn () => (int) preg_replace('/\D/', '', ShareSubscription::max('no') ?? '0'),
        ];

        foreach ($map as $code => $resolver) {
            $max = $resolver();
            if ($max > 0) {
                NumberSeries::where('code', $code)->update(['last_no' => $max]);
            }
        }
    }
}
