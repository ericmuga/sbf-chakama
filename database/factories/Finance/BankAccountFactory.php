<?php

namespace Database\Factories\Finance;

use App\Models\Finance\BankAccount;
use App\Models\Finance\BankPostingGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankAccount>
 */
class BankAccountFactory extends Factory
{
    protected $model = BankAccount::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('BA-???###'),
            'name' => fake()->company().' Bank',
            'bank_account_no' => fake()->bankAccountNumber(),
            'bank_posting_group_id' => BankPostingGroup::factory(),
            'currency_code' => null,
        ];
    }
}
