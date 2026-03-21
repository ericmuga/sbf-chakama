<?php

namespace Database\Factories\Finance;

use App\Models\Finance\VendorPostingGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorPostingGroup>
 */
class VendorPostingGroupFactory extends Factory
{
    protected $model = VendorPostingGroup::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('VEND-???'),
            'description' => fake()->sentence(3),
            'payables_account_no' => fake()->numerify('21##'),
        ];
    }
}
