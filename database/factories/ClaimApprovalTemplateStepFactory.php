<?php

namespace Database\Factories;

use App\Models\ClaimApprovalTemplate;
use App\Models\ClaimApprovalTemplateStep;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClaimApprovalTemplateStep>
 */
class ClaimApprovalTemplateStepFactory extends Factory
{
    public function definition(): array
    {
        return [
            'template_id' => ClaimApprovalTemplate::factory(),
            'step_order' => 1,
            'label' => fake()->words(2, true),
            'approver_user_id' => User::factory(),
        ];
    }
}
