<?php

namespace Database\Factories\Finance;

use App\Models\Finance\NumberSeries;
use App\Models\Finance\PurchaseHeader;
use App\Models\Finance\Vendor;
use App\Models\Finance\VendorPostingGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseHeader>
 */
class PurchaseHeaderFactory extends Factory
{
    protected $model = PurchaseHeader::class;

    public function definition(): array
    {
        $postingDate = fake()->dateTimeBetween('-1 year', 'now');
        $numberSeries = NumberSeries::factory()->create();

        return [
            'no' => fake()->unique()->numerify('PINV-######'),
            'vendor_id' => Vendor::factory(),
            'posting_date' => $postingDate,
            'due_date' => fake()->optional()->dateTimeBetween($postingDate, '+60 days'),
            'vendor_posting_group_id' => VendorPostingGroup::factory(),
            'number_series_code' => $numberSeries->code,
            'status' => 'Open',
        ];
    }
}
