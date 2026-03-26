<?php

namespace Database\Seeders;

use App\Enums\ShareBillingFrequency;
use App\Models\FundAccount;
use App\Models\ShareBillingSchedule;
use Illuminate\Database\Seeder;

class ChakamDataSeeder extends Seeder
{
    public function run(): void
    {
        $fund = FundAccount::updateOrCreate(
            ['no' => 'FUND-0001'],
            [
                'name' => 'Chakama Land Fund',
                'description' => 'Main fund account for Chakama Ranch land share subscriptions.',
                'gl_account_no' => '1050',
                'balance' => 0,
                'is_active' => true,
                'number_series_code' => 'FUND',
            ]
        );

        ShareBillingSchedule::updateOrCreate(
            ['name' => 'Standard Land Share'],
            [
                'price_per_share' => 100000.00,
                'acres_per_share' => 10,
                'billing_frequency' => ShareBillingFrequency::Once,
                'is_default' => true,
                'is_active' => true,
                'fund_account_id' => $fund->id,
            ]
        );
    }
}
