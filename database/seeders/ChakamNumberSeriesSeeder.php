<?php

namespace Database\Seeders;

use App\Models\Finance\NumberSeries;
use Illuminate\Database\Seeder;

class ChakamNumberSeriesSeeder extends Seeder
{
    public function run(): void
    {
        $series = [
            [
                'code' => 'SHARE',
                'description' => 'Share Subscription Numbers',
                'prefix' => 'SHR-',
                'last_no' => 0,
                'length' => 6,
                'is_active' => true,
                'prevent_repeats' => true,
                'is_manual_allowed' => false,
            ],
            [
                'code' => 'FWITH',
                'description' => 'Fund Withdrawal Numbers',
                'prefix' => 'FW-',
                'last_no' => 0,
                'length' => 6,
                'is_active' => true,
                'prevent_repeats' => true,
                'is_manual_allowed' => false,
            ],
            [
                'code' => 'FUND',
                'description' => 'Fund Account Numbers',
                'prefix' => 'FUND-',
                'last_no' => 0,
                'length' => 4,
                'is_active' => true,
                'prevent_repeats' => true,
                'is_manual_allowed' => false,
            ],
        ];

        foreach ($series as $data) {
            NumberSeries::updateOrCreate(['code' => $data['code']], $data);
        }
    }
}
