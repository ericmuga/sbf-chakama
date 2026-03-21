<?php

namespace Database\Factories\Finance;

use App\Models\Finance\CustomerPostingGroup;
use App\Models\Finance\GeneralPostingSetup;
use App\Models\Finance\ServicePostingGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GeneralPostingSetup>
 */
class GeneralPostingSetupFactory extends Factory
{
    protected $model = GeneralPostingSetup::class;

    public function definition(): array
    {
        return [
            'customer_posting_group_id' => CustomerPostingGroup::factory(),
            'service_posting_group_id' => ServicePostingGroup::factory(),
            'sales_account_no' => fake()->numerify('40##'),
        ];
    }
}
