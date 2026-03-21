<?php

namespace Database\Seeders;

use App\Models\Finance\BankAccount;
use App\Models\Finance\BankPostingGroup;
use App\Models\Finance\Customer;
use App\Models\Finance\CustomerPostingGroup;
use App\Models\Finance\GeneralPostingSetup;
use App\Models\Finance\GlAccount;
use App\Models\Finance\NumberSeries;
use App\Models\Finance\PaymentMethod;
use App\Models\Finance\PaymentTerms;
use App\Models\Finance\PurchaseSetup;
use App\Models\Finance\SalesSetup;
use App\Models\Finance\Service;
use App\Models\Finance\ServicePostingGroup;
use App\Models\Finance\Vendor;
use App\Models\Finance\VendorPostingGroup;
use Illuminate\Database\Seeder;

class FinanceSeeder extends Seeder
{
    public function run(): void
    {
        $sinv = NumberSeries::firstOrCreate(['code' => 'SINV'], [
            'description' => 'Sales Invoices', 'prefix' => 'SINV-', 'last_no' => 0, 'length' => 6,
            'is_manual_allowed' => false, 'prevent_repeats' => true, 'is_active' => true,
        ]);

        $psinv = NumberSeries::firstOrCreate(['code' => 'PSINV'], [
            'description' => 'Posted Sales Invoices', 'prefix' => 'PSINV-', 'last_no' => 0, 'length' => 6,
            'is_manual_allowed' => false, 'prevent_repeats' => true, 'is_active' => true,
        ]);

        $pinv = NumberSeries::firstOrCreate(['code' => 'PINV'], [
            'description' => 'Purchase Invoices', 'prefix' => 'PINV-', 'last_no' => 0, 'length' => 6,
            'is_manual_allowed' => false, 'prevent_repeats' => true, 'is_active' => true,
        ]);

        $ppinv = NumberSeries::firstOrCreate(['code' => 'PPINV'], [
            'description' => 'Posted Purchase Invoices', 'prefix' => 'PPINV-', 'last_no' => 0, 'length' => 6,
            'is_manual_allowed' => false, 'prevent_repeats' => true, 'is_active' => true,
        ]);

        NumberSeries::firstOrCreate(['code' => 'PCST'], [
            'description' => 'Posted Documents', 'prefix' => 'PCST-', 'last_no' => 0, 'length' => 6,
            'is_manual_allowed' => false, 'prevent_repeats' => true, 'is_active' => true,
        ]);

        $custNos = NumberSeries::firstOrCreate(['code' => 'CUST'], [
            'description' => 'Customer Numbers', 'prefix' => 'CUST-', 'last_no' => 0, 'length' => 6,
            'is_manual_allowed' => false, 'prevent_repeats' => true, 'is_active' => true,
        ]);

        $vendNos = NumberSeries::firstOrCreate(['code' => 'VEND'], [
            'description' => 'Vendor Numbers', 'prefix' => 'VEND-', 'last_no' => 0, 'length' => 6,
            'is_manual_allowed' => false, 'prevent_repeats' => true, 'is_active' => true,
        ]);

        $rcptNos = NumberSeries::firstOrCreate(['code' => 'RCPT'], [
            'description' => 'Cash Receipts', 'prefix' => 'RCPT-', 'last_no' => 0, 'length' => 6,
            'is_manual_allowed' => false, 'prevent_repeats' => true, 'is_active' => true,
        ]);

        $mbrNos = NumberSeries::firstOrCreate(['code' => 'MBR'], [
            'description' => 'Member Numbers', 'prefix' => 'MBR-', 'last_no' => 0, 'length' => 6,
            'is_manual_allowed' => false, 'prevent_repeats' => true, 'is_active' => true,
        ]);

        // ─── Customer Posting Groups ──────────────────────────────────────────

        $memberCpg = CustomerPostingGroup::firstOrCreate(['code' => 'MEMBER'], [
            'description' => 'Member Customers', 'receivables_account_no' => '1100', 'service_charge_account_no' => null,
        ]);

        $domestic = CustomerPostingGroup::firstOrCreate(['code' => 'DOMESTIC'], [
            'description' => 'Domestic Customers', 'receivables_account_no' => '1100', 'service_charge_account_no' => null,
        ]);

        $foreign = CustomerPostingGroup::firstOrCreate(['code' => 'FOREIGN'], [
            'description' => 'Foreign Customers', 'receivables_account_no' => '1110', 'service_charge_account_no' => null,
        ]);

        // ─── Vendor Posting Groups ────────────────────────────────────────────

        VendorPostingGroup::firstOrCreate(['code' => 'MEMBER'], [
            'description' => 'Member Vendors', 'payables_account_no' => '2100',
        ]);

        VendorPostingGroup::firstOrCreate(['code' => 'LOCAL'], [
            'description' => 'Local Vendors', 'payables_account_no' => '2100',
        ]);

        $importVpg = VendorPostingGroup::firstOrCreate(['code' => 'IMPORT'], [
            'description' => 'Import Vendors', 'payables_account_no' => '2110',
        ]);

        // ─── Service Posting Groups ───────────────────────────────────────────

        $memberServices = ServicePostingGroup::firstOrCreate(['code' => 'MEMBER-SERVICES'], [
            'description' => 'Member Services',
            'revenue_account_no' => '4000',
            'expense_account_no' => null,
        ]);

        $admin = ServicePostingGroup::firstOrCreate(['code' => 'ADMIN'], [
            'description' => 'Administrative Services',
            'revenue_account_no' => '4100',
            'expense_account_no' => '5100',
        ]);

        ServicePostingGroup::firstOrCreate(['code' => 'FINANCE'], [
            'description' => 'Finance Services',
            'revenue_account_no' => '4200',
            'expense_account_no' => null,
        ]);

        $claims = ServicePostingGroup::firstOrCreate(['code' => 'CLAIMS'], [
            'description' => 'Welfare Claims',
            'revenue_account_no' => null,
            'expense_account_no' => '5200',
        ]);

        $projects = ServicePostingGroup::firstOrCreate(['code' => 'PROJECTS'], [
            'description' => 'Community Projects',
            'revenue_account_no' => null,
            'expense_account_no' => '5300',
        ]);

        $operations = ServicePostingGroup::firstOrCreate(['code' => 'OPERATIONS'], [
            'description' => 'Operations & Overheads',
            'revenue_account_no' => null,
            'expense_account_no' => '5400',
        ]);

        // ─── Bank Posting Groups ──────────────────────────────────────────────

        $mainBankPg = BankPostingGroup::firstOrCreate(['code' => 'MAIN-BANK'], [
            'description' => 'Main Bank Account', 'bank_account_gl_no' => '1050',
        ]);

        $mpesaPg = BankPostingGroup::firstOrCreate(['code' => 'MPESA'], [
            'description' => 'M-Pesa Mobile Money', 'bank_account_gl_no' => '1055',
        ]);

        $cashPg = BankPostingGroup::firstOrCreate(['code' => 'CASH'], [
            'description' => 'Cash on Hand', 'bank_account_gl_no' => '1010',
        ]);

        // ─── Bank Accounts ────────────────────────────────────────────────────

        $kcbAccount = BankAccount::firstOrCreate(['code' => 'KCB-MAIN'], [
            'name' => 'KCB Main Account', 'bank_account_no' => '1234567890',
            'bank_posting_group_id' => $mainBankPg->id, 'currency_code' => 'KES',
        ]);

        BankAccount::firstOrCreate(['code' => 'EQUITY-OPS'], [
            'name' => 'Equity Operations Account', 'bank_account_no' => '0987654321',
            'bank_posting_group_id' => $mainBankPg->id, 'currency_code' => 'KES',
        ]);

        $mpesaAccount = BankAccount::firstOrCreate(['code' => 'MPESA-PAYBILL'], [
            'name' => 'M-Pesa Paybill', 'bank_account_no' => '400200',
            'bank_posting_group_id' => $mpesaPg->id, 'currency_code' => 'KES',
        ]);

        $cashAccount = BankAccount::firstOrCreate(['code' => 'CASH-MAIN'], [
            'name' => 'Main Cash Drawer', 'bank_account_no' => '',
            'bank_posting_group_id' => $cashPg->id, 'currency_code' => 'KES',
        ]);

        $posAccount = BankAccount::firstOrCreate(['code' => 'POS-TERMINAL'], [
            'name' => 'POS Card Terminal', 'bank_account_no' => 'POS-001',
            'bank_posting_group_id' => $mainBankPg->id, 'currency_code' => 'KES',
        ]);

        // ─── Payment Methods ──────────────────────────────────────────────────

        PaymentMethod::firstOrCreate(['code' => 'CASH'], [
            'description' => 'Cash', 'bank_account_id' => $cashAccount->id,
        ]);

        PaymentMethod::firstOrCreate(['code' => 'CHEQUE'], [
            'description' => 'Cheque', 'bank_account_id' => $kcbAccount->id,
        ]);

        PaymentMethod::firstOrCreate(['code' => 'MPESA'], [
            'description' => 'M-Pesa', 'bank_account_id' => $mpesaAccount->id,
        ]);

        PaymentMethod::firstOrCreate(['code' => 'CARD'], [
            'description' => 'Card (Debit/Credit)', 'bank_account_id' => $posAccount->id,
        ]);

        PaymentMethod::firstOrCreate(['code' => 'CREDIT'], [
            'description' => 'Credit (Internal)', 'bank_account_id' => null,
        ]);

        // ─── General Posting Setups (Sales) ───────────────────────────────────

        GeneralPostingSetup::firstOrCreate([
            'customer_posting_group_id' => $memberCpg->id,
            'service_posting_group_id' => $memberServices->id,
        ], ['sales_account_no' => '4000']);

        GeneralPostingSetup::firstOrCreate([
            'customer_posting_group_id' => $domestic->id,
            'service_posting_group_id' => $memberServices->id,
        ], ['sales_account_no' => '4000']);

        GeneralPostingSetup::firstOrCreate([
            'customer_posting_group_id' => $domestic->id,
            'service_posting_group_id' => $admin->id,
        ], ['sales_account_no' => '4100']);

        // ─── Payment Terms ────────────────────────────────────────────────────

        PaymentTerms::firstOrCreate(['code' => 'COD'], ['description' => 'Cash on Delivery', 'due_days' => 0]);
        PaymentTerms::firstOrCreate(['code' => 'NET30'], ['description' => 'Net 30 Days', 'due_days' => 30]);
        PaymentTerms::firstOrCreate(['code' => 'NET60'], ['description' => 'Net 60 Days', 'due_days' => 60]);

        // ─── G/L Accounts ─────────────────────────────────────────────────────

        // Assets
        GlAccount::firstOrCreate(['no' => '1010'], ['name' => 'Cash on Hand', 'account_type' => 'Posting']);
        GlAccount::firstOrCreate(['no' => '1050'], ['name' => 'KCB Bank Account', 'account_type' => 'Posting']);
        GlAccount::firstOrCreate(['no' => '1051'], ['name' => 'Equity Bank Account', 'account_type' => 'Posting']);
        GlAccount::firstOrCreate(['no' => '1055'], ['name' => 'M-Pesa Account', 'account_type' => 'Posting']);
        GlAccount::firstOrCreate(['no' => '1060'], ['name' => 'POS / Card Account', 'account_type' => 'Posting']);
        GlAccount::firstOrCreate(['no' => '1100'], ['name' => 'Accounts Receivable', 'account_type' => 'Posting']);
        GlAccount::firstOrCreate(['no' => '1110'], ['name' => 'Accounts Receivable (Foreign)', 'account_type' => 'Posting']);

        // Liabilities
        GlAccount::firstOrCreate(['no' => '2100'], ['name' => 'Accounts Payable', 'account_type' => 'Posting']);
        GlAccount::firstOrCreate(['no' => '2110'], ['name' => 'Accounts Payable (Import)', 'account_type' => 'Posting']);

        // Revenue
        GlAccount::firstOrCreate(['no' => '4000'], ['name' => 'Member Services Revenue', 'account_type' => 'Posting']);
        GlAccount::firstOrCreate(['no' => '4100'], ['name' => 'Administrative Revenue', 'account_type' => 'Posting']);
        GlAccount::firstOrCreate(['no' => '4200'], ['name' => 'Finance Services Revenue', 'account_type' => 'Posting']);

        // Expenses
        GlAccount::firstOrCreate(['no' => '5100'], ['name' => 'Administrative Expenses', 'account_type' => 'Posting']);
        GlAccount::firstOrCreate(['no' => '5200'], ['name' => 'Welfare Claims Expense', 'account_type' => 'Posting']);
        GlAccount::firstOrCreate(['no' => '5210'], ['name' => 'Medical Claims Expense', 'account_type' => 'Posting']);
        GlAccount::firstOrCreate(['no' => '5220'], ['name' => 'Funeral & Bereavement Claims', 'account_type' => 'Posting']);
        GlAccount::firstOrCreate(['no' => '5230'], ['name' => 'Emergency Relief Claims', 'account_type' => 'Posting']);
        GlAccount::firstOrCreate(['no' => '5300'], ['name' => 'Community Projects Expense', 'account_type' => 'Posting']);
        GlAccount::firstOrCreate(['no' => '5310'], ['name' => 'Infrastructure & Construction', 'account_type' => 'Posting']);
        GlAccount::firstOrCreate(['no' => '5320'], ['name' => 'Education Projects Expense', 'account_type' => 'Posting']);
        GlAccount::firstOrCreate(['no' => '5400'], ['name' => 'Operations & Overheads', 'account_type' => 'Posting']);
        GlAccount::firstOrCreate(['no' => '5410'], ['name' => 'Office & Supplies Expense', 'account_type' => 'Posting']);
        GlAccount::firstOrCreate(['no' => '5420'], ['name' => 'Transport & Travel Expense', 'account_type' => 'Posting']);
        GlAccount::firstOrCreate(['no' => '5430'], ['name' => 'Utilities Expense', 'account_type' => 'Posting']);

        // ─── Customers ────────────────────────────────────────────────────────

        Customer::firstOrCreate(['no' => 'CUST-001'], [
            'name' => 'Chakama Community Fund',
            'customer_posting_group_id' => $domestic->id,
            'payment_terms_code' => 'NET30',
        ]);

        Customer::firstOrCreate(['no' => 'CUST-002'], [
            'name' => 'SBF Development Trust',
            'customer_posting_group_id' => $foreign->id,
            'payment_terms_code' => 'NET60',
        ]);

        // ─── Vendors ──────────────────────────────────────────────────────────

        Vendor::firstOrCreate(['no' => 'VEND-001'], [
            'name' => 'Nairobi Office Supplies Ltd',
            'vendor_posting_group_id' => $importVpg->id,
            'payment_terms_code' => 'NET30',
        ]);

        Vendor::firstOrCreate(['no' => 'VEND-002'], [
            'name' => 'Kenya Power & Lighting Co.',
            'vendor_posting_group_id' => $importVpg->id,
            'payment_terms_code' => 'COD',
        ]);

        // ─── Services ─────────────────────────────────────────────────────────

        // Sellable (income-generating)
        Service::firstOrCreate(['code' => 'MEM-REG'], [
            'description' => 'Member Registration Fee',
            'unit_price' => 500.0000,
            'service_posting_group_id' => $memberServices->id,
            'is_sellable' => true,
            'is_purchasable' => false,
        ]);

        Service::firstOrCreate(['code' => 'MEM-ANNUAL'], [
            'description' => 'Annual Membership Subscription',
            'unit_price' => 2400.0000,
            'service_posting_group_id' => $memberServices->id,
            'is_sellable' => true,
            'is_purchasable' => false,
        ]);

        Service::firstOrCreate(['code' => 'ADMIN-FEE'], [
            'description' => 'Administrative Processing Fee',
            'unit_price' => 200.0000,
            'service_posting_group_id' => $admin->id,
            'is_sellable' => true,
            'is_purchasable' => false,
        ]);

        // Purchasable welfare claims
        Service::firstOrCreate(['code' => 'CLM-MEDICAL'], [
            'description' => 'Medical Claim Payout',
            'unit_price' => 0.0000,
            'service_posting_group_id' => $claims->id,
            'is_sellable' => false,
            'is_purchasable' => true,
        ]);

        Service::firstOrCreate(['code' => 'CLM-FUNERAL'], [
            'description' => 'Funeral & Bereavement Claim',
            'unit_price' => 0.0000,
            'service_posting_group_id' => $claims->id,
            'is_sellable' => false,
            'is_purchasable' => true,
        ]);

        Service::firstOrCreate(['code' => 'CLM-EMERGENCY'], [
            'description' => 'Emergency Relief Claim',
            'unit_price' => 0.0000,
            'service_posting_group_id' => $claims->id,
            'is_sellable' => false,
            'is_purchasable' => true,
        ]);

        // Community projects
        Service::firstOrCreate(['code' => 'PROJ-INFRA'], [
            'description' => 'Infrastructure & Construction Project',
            'unit_price' => 0.0000,
            'service_posting_group_id' => $projects->id,
            'is_sellable' => false,
            'is_purchasable' => true,
        ]);

        Service::firstOrCreate(['code' => 'PROJ-EDUCATION'], [
            'description' => 'Education Support Project',
            'unit_price' => 0.0000,
            'service_posting_group_id' => $projects->id,
            'is_sellable' => false,
            'is_purchasable' => true,
        ]);

        // Operational expenses
        Service::firstOrCreate(['code' => 'OPS-OFFICE'], [
            'description' => 'Office Supplies & Stationery',
            'unit_price' => 0.0000,
            'service_posting_group_id' => $operations->id,
            'is_sellable' => false,
            'is_purchasable' => true,
        ]);

        Service::firstOrCreate(['code' => 'OPS-TRANSPORT'], [
            'description' => 'Transport & Travel',
            'unit_price' => 0.0000,
            'service_posting_group_id' => $operations->id,
            'is_sellable' => false,
            'is_purchasable' => true,
        ]);

        Service::firstOrCreate(['code' => 'OPS-UTILITIES'], [
            'description' => 'Utilities (Electricity, Water)',
            'unit_price' => 0.0000,
            'service_posting_group_id' => $operations->id,
            'is_sellable' => false,
            'is_purchasable' => true,
        ]);

        Service::firstOrCreate(['code' => 'OPS-ADMIN'], [
            'description' => 'General Administrative Expenses',
            'unit_price' => 0.0000,
            'service_posting_group_id' => $admin->id,
            'is_sellable' => false,
            'is_purchasable' => true,
        ]);

        // ─── Setup ────────────────────────────────────────────────────────────

        $salesSetup = SalesSetup::first();
        if ($salesSetup) {
            $salesSetup->update([
                'invoice_nos' => $sinv->code,
                'posted_invoice_nos' => $psinv->code,
                'customer_nos' => $custNos->code,
                'member_nos' => $mbrNos->code,
                'receipt_nos' => $rcptNos->code,
            ]);
        } else {
            SalesSetup::create([
                'invoice_nos' => $sinv->code,
                'posted_invoice_nos' => $psinv->code,
                'customer_nos' => $custNos->code,
                'member_nos' => $mbrNos->code,
                'receipt_nos' => $rcptNos->code,
            ]);
        }

        $purchaseSetup = PurchaseSetup::first();
        if ($purchaseSetup) {
            $purchaseSetup->update([
                'invoice_nos' => $pinv->code,
                'posted_invoice_nos' => $ppinv->code,
                'vendor_nos' => $vendNos->code,
            ]);
        } else {
            PurchaseSetup::create([
                'invoice_nos' => $pinv->code,
                'posted_invoice_nos' => $ppinv->code,
                'vendor_nos' => $vendNos->code,
            ]);
        }
    }
}
