<?php

namespace Database\Factories\Finance;

use App\Models\Finance\ServicePostingGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServicePostingGroup>
 */
class ServicePostingGroupFactory extends Factory
{
    protected $model = ServicePostingGroup::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('SRV-???'),
            'description' => fake()->sentence(3),
            'revenue_account_no' => fake()->numerify('40##'),
        ];
    }
}
