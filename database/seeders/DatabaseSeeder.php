<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@sbfchakama.co.ke'],
            [
                'name' => 'SBF Chakama Admin',
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
                'is_admin' => true,
            ]
        );
    }
}
