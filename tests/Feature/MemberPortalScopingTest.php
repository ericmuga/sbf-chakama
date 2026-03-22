<?php

namespace Tests\Feature;

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

    public function test_non_sbf_member_cannot_access_portal(): void
    {
        $user = User::factory()->create();
        Member::factory()->for($user)->create(['is_sbf' => false]);

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

    public function test_admin_can_access_admin_panel(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $this->actingAs($user);

        $this->get(route('filament.admin.pages.dashboard'))
            ->assertOk();
    }

    public function test_non_admin_cannot_access_admin_panel(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user);

        $this->get(route('filament.admin.pages.dashboard'))
            ->assertForbidden();
    }
}
