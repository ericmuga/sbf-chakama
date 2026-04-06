<?php

namespace Tests\Feature;

use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_be_created_and_log_in(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Admin Candidate',
            'email' => 'candidate@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $user = User::query()->where('email', 'candidate@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertFalse($user->isAdmin());
    }

    public function test_user_can_be_set_and_unset_as_admin_from_backend(): void
    {
        $user = User::factory()->create();

        $user->promoteToAdmin();

        $this->assertTrue($user->refresh()->isAdmin());

        $user->demoteFromAdmin();

        $this->assertFalse($user->refresh()->isAdmin());
    }

    public function test_admin_can_view_admin_user_management_page(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertOk()
            ->assertSee('Users')
            ->assertSee($target->email);
    }

    public function test_non_admin_cannot_access_filament_admin_panel(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_admin_can_create_user_with_member_profile_from_filament_panel(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Filament Created User',
                'email' => 'filament-created@example.com',
                'password' => 'password',
                'is_admin' => false,
                'has_member_profile' => true,
                'identity_type' => 'national_id',
                'identity_no' => '12345678',
                'member_phone' => '0712345678',
                'member_status' => 'active',
                'is_chakama' => true,
                'is_sbf' => false,
            ])
            ->call('create')
            ->assertHasNoErrors();

        $user = User::query()->where('email', 'filament-created@example.com')->firstOrFail();

        $this->assertDatabaseHas(User::class, [
            'email' => 'filament-created@example.com',
            'is_admin' => false,
        ]);

        $this->assertDatabaseHas(Member::class, [
            'user_id' => $user->id,
            'identity_type' => 'national_id',
            'identity_no' => '12345678',
            'phone' => '0712345678',
            'member_status' => 'active',
            'is_chakama' => true,
            'is_sbf' => false,
        ]);
    }

    public function test_admin_can_set_a_user_as_admin_from_filament_panel(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create(['is_admin' => false]);

        Member::query()->create([
            'no' => 'MEM-SET-001',
            'user_id' => $target->id,
            'identity_type' => 'national_id',
            'identity_no' => '90000001',
            'phone' => '0700000001',
            'member_status' => 'active',
            'is_chakama' => true,
            'is_sbf' => true,
            'customer_no' => 'CUST-SET-001',
            'vendor_no' => 'VEND-SET-001',
        ]);

        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $target->getRouteKey()])
            ->fillForm([
                'name' => $target->name,
                'email' => $target->email,
                'password' => '',
                'is_admin' => true,
                'member' => [
                    'no' => 'MEM-SET-001',
                    'identity_type' => 'national_id',
                    'identity_no' => '90000001',
                    'phone' => '0700000001',
                    'member_status' => 'active',
                    'is_chakama' => true,
                    'is_sbf' => true,
                    'customer_no' => 'CUST-SET-001',
                    'vendor_no' => 'VEND-SET-001',
                ],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue($target->refresh()->isAdmin());
    }

    public function test_admin_can_unset_a_user_as_admin_from_filament_panel(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->admin()->create();

        Member::query()->create([
            'no' => 'MEM-UNSET-001',
            'user_id' => $target->id,
            'identity_type' => 'national_id',
            'identity_no' => '90000002',
            'phone' => '0700000002',
            'member_status' => 'active',
            'is_chakama' => true,
            'is_sbf' => false,
            'customer_no' => 'CUST-UNSET-001',
            'vendor_no' => 'VEND-UNSET-001',
        ]);

        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $target->getRouteKey()])
            ->fillForm([
                'name' => $target->name,
                'email' => $target->email,
                'password' => '',
                'is_admin' => false,
                'member' => [
                    'no' => 'MEM-UNSET-001',
                    'identity_type' => 'national_id',
                    'identity_no' => '90000002',
                    'phone' => '0700000002',
                    'member_status' => 'active',
                    'is_chakama' => true,
                    'is_sbf' => false,
                    'customer_no' => 'CUST-UNSET-001',
                    'vendor_no' => 'VEND-UNSET-001',
                ],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertFalse($target->refresh()->isAdmin());
    }
}
