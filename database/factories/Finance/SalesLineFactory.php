<?php

namespace Database\Factories\Finance;

use App\Models\Finance\CustomerPostingGroup;
use App\Models\Finance\GeneralPostingSetup;
use App\Models\Finance\SalesHeader;
use App\Models\Finance\SalesLine;
use App\Models\Finance\Service;
use App\Models\Finance\ServicePostingGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalesLine>
 */
class SalesLineFactory extends Factory
{
    protected $model = SalesLine::class;

    public function definition(): array
    {
        $quantity = fake()->randomFloat(4, 1, 100);
        $unitPrice = fake()->randomFloat(4, 100, 10000);

        return [
            'sales_header_id' => SalesHeader::factory(),
            'line_no' => fake()->unique()->numberBetween(1, 9999) * 10,
            'service_id' => Service::factory(),
            'description' => fake()->sentence(4),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'line_amount' => round($quantity * $unitPrice, 4),
            'customer_posting_group_id' => CustomerPostingGroup::factory(),
            'service_posting_group_id' => ServicePostingGroup::factory(),
            'general_posting_setup_id' => GeneralPostingSetup::factory(),
        ];
    }
}
