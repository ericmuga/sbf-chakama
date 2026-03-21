<?php

namespace Database\Factories\Finance;

use App\Models\Finance\Customer;
use App\Models\Finance\CustomerPostingGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'no' => fake()->unique()->numerify('CUST-######'),
            'name' => fake()->company(),
            'customer_posting_group_id' => CustomerPostingGroup::factory(),
            'payment_terms_code' => null,
        ];
    }
}
