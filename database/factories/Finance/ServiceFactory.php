<?php

namespace Database\Factories\Finance;

use App\Models\Finance\Service;
use App\Models\Finance\ServicePostingGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('SVC-???###'),
            'description' => fake()->sentence(4),
            'unit_price' => fake()->randomFloat(4, 100, 50000),
            'service_posting_group_id' => ServicePostingGroup::factory(),
        ];
    }
}
