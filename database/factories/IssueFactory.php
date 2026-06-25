<?php

namespace Database\Factories;

use App\Enums\IssueCategory;
use App\Enums\IssuePortal;
use App\Enums\IssueStatus;
use App\Models\Issue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Issue>
 */
class IssueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'portal_type' => fake()->randomElement(IssuePortal::cases()),
            'details' => fake()->paragraph(),
            'issue_owner' => fake()->randomLetter().fake()->randomLetter(),
            'category' => fake()->randomElement(IssueCategory::cases()),
            'resource' => fake()->randomLetter().fake()->randomLetter(),
            'date_assigned' => fake()->optional()->date(),
            'status' => fake()->randomElement(IssueStatus::cases()),
            'closure_date' => null,
            'comments' => fake()->optional()->sentence(),
            'reviewed_date' => null,
            'qa_test_result' => fake()->optional()->randomElement(['Pass', 'Fail']),
            'release_id' => null,
        ];
    }

    public function closed(): static
    {
        return $this->state(['status' => IssueStatus::Closed, 'qa_test_result' => 'Pass']);
    }

    public function open(): static
    {
        return $this->state(['status' => IssueStatus::Open]);
    }
}
