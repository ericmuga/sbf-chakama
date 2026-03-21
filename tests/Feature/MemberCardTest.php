<?php

namespace Tests\Feature;

use App\Models\Dependant;
use App\Models\Member;
use App\Models\NextOfKin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_card_page_loads_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('member.card'));

        $response->assertStatus(200);
    }

    public function test_member_card_shows_member_details(): void
    {
        $user = User::factory()->create();
        $member = Member::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('member.card'));

        $response->assertStatus(200);
        $response->assertSee($member->no);
        $response->assertSee($member->identity_no);
        $response->assertSee($member->phone);
    }

    public function test_member_card_shows_next_of_kin(): void
    {
        $user = User::factory()->create();
        $member = Member::factory()->create(['user_id' => $user->id]);
        $nextOfKin = NextOfKin::factory()->create([
            'member_id' => $member->id,
            'name' => 'Jane Spouse',
            'relationship' => 'Spouse',
        ]);

        $response = $this->actingAs($user)->get(route('member.card'));

        $response->assertStatus(200);
        $response->assertSee('Jane Spouse');
        $response->assertSee('Spouse');
    }

    public function test_member_card_shows_dependants(): void
    {
        $user = User::factory()->create();
        $member = Member::factory()->create(['user_id' => $user->id]);
        $dependant = Dependant::factory()->create([
            'member_id' => $member->id,
            'name' => 'Little Child',
            'relationship' => 'Child',
        ]);

        $response = $this->actingAs($user)->get(route('member.card'));

        $response->assertStatus(200);
        $response->assertSee('Little Child');
        $response->assertSee('Child');
    }

    public function test_unauthenticated_user_cannot_see_member_card(): void
    {
        $response = $this->get(route('member.card'));

        $response->assertRedirect(route('login'));
    }
}
