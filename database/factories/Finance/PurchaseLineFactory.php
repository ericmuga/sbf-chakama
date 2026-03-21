<?php

namespace Database\Factories\Finance;

use App\Models\Finance\PurchaseHeader;
use App\Models\Finance\PurchaseLine;
use App\Models\Finance\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseLine>
 */
class PurchaseLineFactory extends Factory
{
    protected $model = PurchaseLine::class;

    public function definition(): array
    {
        $quantity = fake()->randomFloat(4, 1, 100);
        $unitPrice = fake()->randomFloat(4, 100, 10000);

        return [
            'purchase_header_id' => PurchaseHeader::factory(),
            'line_no' => fake()->unique()->numberBetween(1, 9999) * 10,
            'service_id' => Service::factory(),
            'description' => fake()->sentence(4),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'line_amount' => round($quantity * $unitPrice, 4),
        ];
    }
}
