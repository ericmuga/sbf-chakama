<?php

namespace Database\Factories;

use App\Models\ClaimApprovalTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClaimApprovalTemplate>
 */
class ClaimApprovalTemplateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'claim_type' => null,
            'is_default' => false,
            'is_active' => true,
        ];
    }

    public function default(): static
    {
        return $this->state(['is_default' => true]);
    }
}
