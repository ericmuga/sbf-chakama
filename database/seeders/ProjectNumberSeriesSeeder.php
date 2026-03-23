<?php

namespace Database\Seeders;

use App\Models\Finance\NumberSeries;
use App\Models\Finance\PurchaseSetup;
use Illuminate\Database\Seeder;

class ProjectNumberSeriesSeeder extends Seeder
{
    public function run(): void
    {
        $series = [
            [
                'code' => 'PROJ',
                'description' => 'Project Numbers',
                'prefix' => 'PROJ-',
                'last_no' => 0,
                'length' => 5,
                'is_active' => true,
                'prevent_repeats' => true,
                'is_manual_allowed' => false,
            ],
            [
                'code' => 'DCOST',
                'description' => 'Direct Cost Entry Numbers',
                'prefix' => 'DC-',
                'last_no' => 0,
                'length' => 6,
                'is_active' => true,
                'prevent_repeats' => true,
                'is_manual_allowed' => false,
            ],
        ];

        foreach ($series as $data) {
            NumberSeries::firstOrCreate(['code' => $data['code']], $data);
        }

        $purchaseSetup = PurchaseSetup::query()->first();

        if ($purchaseSetup) {
            $purchaseSetup->update([
                'project_nos' => $purchaseSetup->project_nos ?: 'PROJ',
                'direct_cost_nos' => $purchaseSetup->direct_cost_nos ?: 'DCOST',
            ]);
        }
    }
}
