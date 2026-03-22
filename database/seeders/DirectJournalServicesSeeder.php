<?php

namespace Database\Seeders;

use App\Models\Finance\Service;
use App\Models\Finance\ServicePostingGroup;
use Illuminate\Database\Seeder;

class DirectJournalServicesSeeder extends Seeder
{
    public function run(): void
    {
        $expenseSpg = ServicePostingGroup::whereNotNull('expense_account_no')->first()
            ?? ServicePostingGroup::create([
                'code' => 'CLUB-EXP',
                'description' => 'Club Expenses',
                'expense_account_no' => '6000',
                'revenue_account_no' => null,
            ]);

        $incomeSpg = ServicePostingGroup::whereNotNull('revenue_account_no')->first()
            ?? ServicePostingGroup::create([
                'code' => 'CLUB-INC',
                'description' => 'Club Income',
                'expense_account_no' => null,
                'revenue_account_no' => '4000',
            ]);

        $expenseServices = [
            ['code' => 'BANK-CHG', 'description' => 'Bank Charges'],
            ['code' => 'BANK-INT-EXP', 'description' => 'Bank Interest Expense'],
            ['code' => 'PETTY-CASH', 'description' => 'Petty Cash Expenses'],
            ['code' => 'PRINTING', 'description' => 'Printing & Stationery'],
            ['code' => 'TRANSPORT', 'description' => 'Transport & Travelling'],
            ['code' => 'UTIL', 'description' => 'Utilities'],
            ['code' => 'MAINT', 'description' => 'Maintenance & Repairs'],
            ['code' => 'AUDIT', 'description' => 'Audit Fees'],
            ['code' => 'AGM-EXP', 'description' => 'AGM & Meeting Expenses'],
            ['code' => 'SALARIES', 'description' => 'Salaries & Wages'],
        ];

        foreach ($expenseServices as $data) {
            Service::firstOrCreate(
                ['code' => $data['code']],
                [
                    'description' => $data['description'],
                    'unit_price' => 0,
                    'is_purchasable' => true,
                    'is_sellable' => false,
                    'service_posting_group_id' => $expenseSpg->id,
                ]
            );
        }

        $incomeServices = [
            ['code' => 'BANK-INT-INC', 'description' => 'Bank Interest Received'],
            ['code' => 'DONATION', 'description' => 'Donations & Grants'],
            ['code' => 'PENALTIES', 'description' => 'Late Payment Penalties'],
            ['code' => 'MISC-INC', 'description' => 'Miscellaneous Income'],
        ];

        foreach ($incomeServices as $data) {
            Service::firstOrCreate(
                ['code' => $data['code']],
                [
                    'description' => $data['description'],
                    'unit_price' => 0,
                    'is_purchasable' => false,
                    'is_sellable' => true,
                    'service_posting_group_id' => $incomeSpg->id,
                ]
            );
        }
    }
}
