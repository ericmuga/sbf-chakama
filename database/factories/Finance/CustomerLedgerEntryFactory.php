<?php

namespace Database\Factories\Finance;

use App\Models\Finance\Customer;
use App\Models\Finance\CustomerLedgerEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerLedgerEntry>
 */
class CustomerLedgerEntryFactory extends Factory
{
    protected $model = CustomerLedgerEntry::class;

    public function definition(): array
    {
        $amount = fake()->randomFloat(4, 100, 100000);
        $postingDate = fake()->dateTimeBetween('-1 year', 'now');

        return [
            'entry_no' => fake()->unique()->numberBetween(1, 999999),
            'customer_id' => Customer::factory(),
            'document_type' => 'Invoice',
            'document_no' => fake()->numerify('SINV-######'),
            'posting_date' => $postingDate,
            'due_date' => fake()->optional()->dateTimeBetween($postingDate, '+60 days'),
            'amount' => $amount,
            'remaining_amount' => $amount,
            'is_open' => true,
        ];
    }
}
