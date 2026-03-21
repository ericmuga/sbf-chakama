<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\NextOfKin;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NextOfKin>
 */
class NextOfKinFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => 'next_of_kin',
            'member_id' => Member::factory(),
            'name' => fake()->name(),
            'national_id' => fake()->unique()->numerify('########'),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->safeEmail(),
            'date_of_birth' => fake()->date(),
            'relationship' => 'Spouse',
            'contact_preference' => 'phone',
        ];
    }
}
