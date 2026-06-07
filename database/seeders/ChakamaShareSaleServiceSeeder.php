<?php

namespace Database\Seeders;

use App\Models\Finance\CustomerPostingGroup;
use App\Models\Finance\GeneralPostingSetup;
use App\Models\Finance\GlAccount;
use App\Models\Finance\Service;
use App\Models\Finance\ServicePostingGroup;
use App\Models\ShareBillingSchedule;
use Illuminate\Database\Seeder;

class ChakamaShareSaleServiceSeeder extends Seeder
{
    public function run(): void
    {
        $revenueAccount = GlAccount::updateOrCreate(
            ['no' => '4300'],
            [
                'name' => 'Chakama Share Sales',
                'account_type' => 'Posting',
            ]
        );

        $spg = ServicePostingGroup::updateOrCreate(
            ['code' => 'CHAKAMA-SHARES'],
            [
                'description' => 'Chakama Share Sales',
                'revenue_account_no' => $revenueAccount->no,
            ]
        );

        $service = Service::updateOrCreate(
            ['code' => 'CHAKAMA-SHARE'],
            [
                'description' => 'Chakama Share Sale',
                'unit_price' => 0,
                'is_sellable' => true,
                'is_purchasable' => false,
                'service_posting_group_id' => $spg->id,
            ]
        );

        // Ensure a General Posting Setup row for every customer posting group so
        // any Chakama member's invoice can resolve a sales account.
        foreach (CustomerPostingGroup::all() as $cpg) {
            GeneralPostingSetup::updateOrCreate(
                [
                    'customer_posting_group_id' => $cpg->id,
                    'service_posting_group_id' => $spg->id,
                ],
                ['sales_account_no' => $revenueAccount->no]
            );
        }

        // Back-fill any existing schedules that have no service yet.
        ShareBillingSchedule::whereNull('service_id')->update(['service_id' => $service->id]);
    }
}
