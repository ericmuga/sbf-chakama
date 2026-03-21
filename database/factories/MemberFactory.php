<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => 'member',
            'user_id' => User::factory(),
            'no' => 'MEM-'.fake()->unique()->numerify('######'),
            'identity_no' => fake()->unique()->numerify('########'),
            'identity_type' => 'national_id',
            'phone' => fake()->phoneNumber(),
            'member_status' => 'active',
            'is_chakama' => false,
            'is_sbf' => false,
        ];
    }
}
