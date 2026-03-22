<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClaimNumberSeriesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('number_series')->insertOrIgnore([
            'code' => 'CLAIM',
            'description' => 'SBF Claim Numbers',
            'prefix' => 'CLM-',
            'last_no' => 0,
            'length' => 6,
            'is_manual_allowed' => false,
            'prevent_repeats' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
