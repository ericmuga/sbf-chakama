<?php

namespace Database\Factories\Finance;

use App\Models\Finance\CustomerPostingGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerPostingGroup>
 */
class CustomerPostingGroupFactory extends Factory
{
    protected $model = CustomerPostingGroup::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('CUST-???'),
            'description' => fake()->sentence(3),
            'receivables_account_no' => fake()->numerify('11##'),
            'service_charge_account_no' => fake()->optional()->numerify('41##'),
        ];
    }
}
