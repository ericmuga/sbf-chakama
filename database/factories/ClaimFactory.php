<?php

namespace Database\Factories;

use App\Enums\ClaimStatus;
use App\Enums\ClaimType;
use App\Models\Claim;
use App\Models\Finance\NumberSeries;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Claim>
 */
class ClaimFactory extends Factory
{
    public function definition(): array
    {
        $series = NumberSeries::firstOrCreate(
            ['code' => 'CLAIM'],
            ['description' => 'Claims', 'prefix' => 'CLM-', 'length' => 6, 'last_no' => 0, 'is_active' => true]
        );

        return [
            'no' => 'CLM-'.fake()->unique()->numerify('######'),
            'member_id' => Member::factory(),
            'claim_type' => fake()->randomElement(ClaimType::cases())->value,
            'subject' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'claimed_amount' => fake()->randomFloat(2, 1000, 50000),
            'approved_amount' => null,
            'status' => ClaimStatus::Draft->value,
            'current_step' => 0,
            'payee_name' => fake()->name(),
            'number_series_code' => $series->code,
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => ClaimStatus::Draft->value]);
    }

    public function submitted(): static
    {
        return $this->state([
            'status' => ClaimStatus::Submitted->value,
            'submitted_at' => now(),
            'current_step' => 1,
        ]);
    }

    public function approved(): static
    {
        return $this->state([
            'status' => ClaimStatus::Approved->value,
            'submitted_at' => now()->subDays(5),
            'approved_at' => now(),
        ]);
    }
}
