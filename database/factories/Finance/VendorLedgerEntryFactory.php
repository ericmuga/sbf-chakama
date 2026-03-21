<?php

namespace Database\Factories\Finance;

use App\Models\Finance\Vendor;
use App\Models\Finance\VendorLedgerEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorLedgerEntry>
 */
class VendorLedgerEntryFactory extends Factory
{
    protected $model = VendorLedgerEntry::class;

    public function definition(): array
    {
        $amount = fake()->randomFloat(4, 100, 100000);
        $postingDate = fake()->dateTimeBetween('-1 year', 'now');

        return [
            'entry_no' => fake()->unique()->numberBetween(1, 999999),
            'vendor_id' => Vendor::factory(),
            'document_type' => 'Invoice',
            'document_no' => fake()->numerify('PINV-######'),
            'posting_date' => $postingDate,
            'due_date' => fake()->optional()->dateTimeBetween($postingDate, '+60 days'),
            'amount' => $amount,
            'remaining_amount' => $amount,
            'is_open' => true,
        ];
    }
}
