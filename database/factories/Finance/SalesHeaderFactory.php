<?php

namespace Database\Factories\Finance;

use App\Models\Finance\Customer;
use App\Models\Finance\CustomerPostingGroup;
use App\Models\Finance\NumberSeries;
use App\Models\Finance\SalesHeader;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalesHeader>
 */
class SalesHeaderFactory extends Factory
{
    protected $model = SalesHeader::class;

    public function definition(): array
    {
        $postingDate = fake()->dateTimeBetween('-1 year', 'now');
        $numberSeries = NumberSeries::factory()->create();

        return [
            'no' => fake()->unique()->numerify('SINV-######'),
            'customer_id' => Customer::factory(),
            'document_type' => 'Invoice',
            'posting_date' => $postingDate,
            'due_date' => fake()->optional()->dateTimeBetween($postingDate, '+60 days'),
            'customer_posting_group_id' => CustomerPostingGroup::factory(),
            'number_series_code' => $numberSeries->code,
            'status' => 'Open',
        ];
    }
}
