<?php

namespace Tests\Feature;

use App\Enums\EntityDimension;
use App\Models\Claim;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberPortalScopingTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_only_see_own_claims(): void
    {
        $user = User::factory()->create();
        $member = Member::factory()->for($user)->create(['is_sbf' => true]);
        $claim = Claim::factory()->create(['member_id' => $member->id]);

        $otherMember = Member::factory()->create(['is_sbf' => true]);
        $otherClaim = Claim::factory()->create(['member_id' => $otherMember->id]);

        $this->actingAs($user);

        $this->get(route('filament.member.resources.claims.index'))
            ->assertOk()
            ->assertSee($claim->no)
            ->assertDontSee($otherClaim->no);
    }

    public function test_sbf_member_can_access_unified_portal(): void
    {
        $user = User::factory()->create();
        Member::factory()->for($user)->create(['is_sbf' => true, 'is_chakama' => false]);

        $this->actingAs($user);

        $this->get(route('filament.member.pages.member-dashboard'))
            ->assertOk();
    }

    public function test_chakama_only_member_is_denied_sbf_portal(): void
    {
        $user = User::factory()->create();
        Member::factory()->for($user)->create(['is_sbf' => false, 'is_chakama' => true]);

        $this->actingAs($user);

        $this->get(route('filament.member.pages.member-dashboard'))
            ->assertForbidden();
    }

    public function test_chakama_only_member_can_access_chakama_portal(): void
    {
        $user = User::factory()->create();
        Member::factory()->for($user)->create(['is_sbf' => false, 'is_chakama' => true]);

        $this->actingAs($user);

        $this->get(route('filament.chakama-portal.pages.member-dashboard'))
            ->assertOk();
    }

    public function test_dual_member_can_access_unified_portal(): void
    {
        $user = User::factory()->create();
        Member::factory()->for($user)->create(['is_sbf' => true, 'is_chakama' => true]);

        $this->actingAs($user);

        $this->get(route('filament.member.pages.member-dashboard'))
            ->assertOk();
    }

    public function test_non_member_user_cannot_access_portal(): void
    {
        $user = User::factory()->create();
        Member::factory()->for($user)->create(['is_sbf' => false, 'is_chakama' => false]);

        $this->actingAs($user);

        $this->get(route('filament.member.resources.claims.index'))
            ->assertForbidden();
    }

    public function test_user_without_member_record_cannot_access_portal(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->get(route('filament.member.resources.claims.index'))
            ->assertForbidden();
    }

    public function test_unauthenticated_user_is_redirected_to_member_login(): void
    {
        $this->get(route('filament.member.resources.claims.index'))
            ->assertRedirect(route('filament.member.auth.login'));
    }

    public function test_sbf_admin_can_access_sbf_admin_panel(): void
    {
        $user = User::factory()->create(['is_admin' => true, 'entity' => null]);

        $this->actingAs($user);

        $this->get(route('filament.sbf.pages.dashboard'))
            ->assertOk();
    }

    public function test_chakama_admin_can_access_chakama_admin_panel(): void
    {
        $user = User::factory()->create(['is_admin' => true, 'entity' => EntityDimension::Chakama]);

        $this->actingAs($user);

        $this->get(route('filament.chakama.pages.dashboard'))
            ->assertOk();
    }

    public function test_sbf_admin_cannot_access_chakama_admin_panel(): void
    {
        $user = User::factory()->create(['is_admin' => true, 'entity' => null]);

        $this->actingAs($user);

        $this->get(route('filament.chakama.pages.dashboard'))
            ->assertForbidden();
    }

    public function test_chakama_admin_cannot_access_sbf_admin_panel(): void
    {
        $user = User::factory()->create(['is_admin' => true, 'entity' => EntityDimension::Chakama]);

        $this->actingAs($user);

        $this->get(route('filament.sbf.pages.dashboard'))
            ->assertForbidden();
    }

    public function test_non_admin_cannot_access_admin_panel(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user);

        $this->get(route('filament.sbf.pages.dashboard'))
            ->assertForbidden();
    }
}
