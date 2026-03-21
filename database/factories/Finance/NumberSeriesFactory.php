<?php

namespace Database\Factories\Finance;

use App\Models\Finance\NumberSeries;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NumberSeries>
 */
class NumberSeriesFactory extends Factory
{
    protected $model = NumberSeries::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('???-SERIES'),
            'description' => fake()->sentence(3),
            'prefix' => fake()->optional()->lexify('???'),
            'last_no' => 0,
            'last_date_used' => null,
            'length' => 6,
            'is_manual_allowed' => false,
            'prevent_repeats' => true,
            'is_active' => true,
        ];
    }
}
