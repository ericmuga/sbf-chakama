<?php

namespace Database\Factories;

use App\Models\Dependant;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dependant>
 */
class DependantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => 'dependant',
            'member_id' => Member::factory(),
            'name' => fake()->name(),
            'identity_no' => fake()->unique()->numerify('########'),
            'identity_type' => 'birth_cert_no',
            'phone' => fake()->phoneNumber(),
            'email' => fake()->safeEmail(),
            'date_of_birth' => fake()->date(),
            'relationship' => 'Child',
        ];
    }
}
