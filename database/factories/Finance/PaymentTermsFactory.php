<?php

namespace Database\Factories\Finance;

use App\Models\Finance\PaymentTerms;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentTerms>
 */
class PaymentTermsFactory extends Factory
{
    protected $model = PaymentTerms::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('PT-???'),
            'description' => fake()->sentence(3),
            'due_days' => fake()->numberBetween(0, 90),
        ];
    }
}
