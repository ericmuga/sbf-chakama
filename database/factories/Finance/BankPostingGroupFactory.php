<?php

namespace Database\Factories\Finance;

use App\Models\Finance\BankPostingGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankPostingGroup>
 */
class BankPostingGroupFactory extends Factory
{
    protected $model = BankPostingGroup::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('BANK-???'),
            'description' => fake()->sentence(3),
            'bank_account_gl_no' => fake()->numerify('10##'),
        ];
    }
}
