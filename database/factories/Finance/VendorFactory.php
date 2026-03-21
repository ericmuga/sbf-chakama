<?php

namespace Database\Factories\Finance;

use App\Models\Finance\Vendor;
use App\Models\Finance\VendorPostingGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vendor>
 */
class VendorFactory extends Factory
{
    protected $model = Vendor::class;

    public function definition(): array
    {
        return [
            'no' => fake()->unique()->numerify('VEND-######'),
            'name' => fake()->company(),
            'vendor_posting_group_id' => VendorPostingGroup::factory(),
            'payment_terms_code' => null,
        ];
    }
}
