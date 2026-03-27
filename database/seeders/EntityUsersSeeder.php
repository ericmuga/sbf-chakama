<?php

namespace Database\Seeders;

use App\Enums\EntityDimension;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Seeder;

class EntityUsersSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Chakama accounts ────────────────────────────────────────────────

        User::updateOrCreate(
            ['email' => 'admin@sobachakama.co.ke'],
            [
                'name' => 'Chakama Admin',
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
                'is_admin' => true,
                'entity' => EntityDimension::Chakama,
            ]
        );

        User::updateOrCreate(
            ['email' => 'treasurer@sobachakama.co.ke'],
            [
                'name' => 'Chakama Treasurer',
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
                'is_admin' => true,
                'entity' => EntityDimension::Chakama,
            ]
        );

        User::updateOrCreate(
            ['email' => 'chairman@sobachakama.co.ke'],
            [
                'name' => 'Chakama Chairman',
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
                'is_admin' => true,
                'entity' => EntityDimension::Chakama,
            ]
        );

        $chakamaPortalUser = User::updateOrCreate(
            ['email' => 'member@sobachakama.co.ke'],
            [
                'name' => 'John Mwangi',
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
                'is_admin' => false,
                'entity' => EntityDimension::Chakama,
            ]
        );

        Member::updateOrCreate(
            ['user_id' => $chakamaPortalUser->id],
            [
                'name' => 'John Mwangi',
                'type' => 'member',
                'identity_no' => '11223344',
                'identity_type' => 'national_id',
                'phone' => '0722100001',
                'is_chakama' => true,
                'is_sbf' => false,
            ]
        );

        // ─── SBF accounts ─────────────────────────────────────────────────────

        User::updateOrCreate(
            ['email' => 'admin@sobasbf.co.ke'],
            [
                'name' => 'SBF Admin',
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
                'is_admin' => true,
                'entity' => EntityDimension::Sbf,
            ]
        );

        User::updateOrCreate(
            ['email' => 'treasurer@sobasbf.co.ke'],
            [
                'name' => 'SBF Treasurer',
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
                'is_admin' => true,
                'entity' => EntityDimension::Sbf,
            ]
        );

        User::updateOrCreate(
            ['email' => 'chairman@sobasbf.co.ke'],
            [
                'name' => 'SBF Chairman',
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
                'is_admin' => true,
                'entity' => EntityDimension::Sbf,
            ]
        );

        $sbfPortalUser = User::updateOrCreate(
            ['email' => 'member@sobasbf.co.ke'],
            [
                'name' => 'Grace Ochieng',
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
                'is_admin' => false,
                'entity' => EntityDimension::Sbf,
            ]
        );

        Member::updateOrCreate(
            ['user_id' => $sbfPortalUser->id],
            [
                'name' => 'Grace Ochieng',
                'type' => 'member',
                'identity_no' => '55667788',
                'identity_type' => 'national_id',
                'phone' => '0733200002',
                'is_chakama' => false,
                'is_sbf' => true,
            ]
        );
    }
}
