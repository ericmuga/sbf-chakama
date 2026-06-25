<?php

namespace Database\Factories;

use App\Enums\ReleaseStatus;
use App\Models\Release;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Release>
 */
class ReleaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'version' => 'v'.fake()->unique()->numerify('#.#.#'),
            'name' => fake()->optional()->sentence(3),
            'status' => fake()->randomElement(ReleaseStatus::cases()),
            'released_on' => fake()->optional()->date(),
            'notes' => fake()->optional()->paragraph(),
        ];
    }
}
