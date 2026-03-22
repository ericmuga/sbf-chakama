<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Seeder;

class SampleMemberSeeder extends Seeder
{
    public function run(): void
    {
        // Portal user for Amina Wanjiru — SBF member
        $user = User::updateOrCreate(
            ['email' => 'member@sbfchakama.co.ke'],
            [
                'name' => 'Amina Wanjiru',
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
                'is_admin' => false,
            ]
        );

        // Create the member record (model booted() auto-creates Customer + Vendor)
        Member::updateOrCreate(
            ['user_id' => $user->id],
            [
                'name' => 'Amina Wanjiru',
                'type' => 'member',
                'identity_no' => '12345678',
                'identity_type' => 'national_id',
                'phone' => '0712345678',
                'mpesa_phone' => '254712345678',
                'member_status' => 'active',
                'is_chakama' => true,
                'is_sbf' => true,
                'bank_name' => 'KCB',
                'bank_account_name' => 'Amina Wanjiru',
                'bank_account_no' => '1234567890',
                'bank_branch' => 'Chakama Branch',
                'preferred_payment_method' => 'bank_transfer',
            ]
        );

        // Treasurer — approves at step 1
        User::updateOrCreate(
            ['email' => 'treasurer@sbfchakama.co.ke'],
            [
                'name' => 'Hassan Mwangi (Treasurer)',
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
                'is_admin' => true,
            ]
        );

        // Chairman — approves at step 2
        User::updateOrCreate(
            ['email' => 'chairman@sbfchakama.co.ke'],
            [
                'name' => 'Joseph Karanja (Chairman)',
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
                'is_admin' => true,
            ]
        );
    }
}
