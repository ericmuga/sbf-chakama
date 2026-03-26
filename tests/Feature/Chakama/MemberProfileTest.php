<?php

namespace Tests\Feature\Chakama;

use App\Filament\Member\Resources\Profile\Pages\ListMyDependants;
use App\Filament\Member\Resources\Profile\Pages\ListMyNextOfKin;
use App\Models\Dependant;
use App\Models\Member;
use App\Models\NextOfKin;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MemberProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Member $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->member = Member::factory()->for($this->user)->create([
            'is_chakama' => true,
            'is_sbf' => true,
        ]);

        $this->actingAs($this->user);
        Filament::setCurrentPanel(Filament::getPanel('member'));
    }

    // --- Dependants ---

    public function test_chakama_member_can_access_my_dependants_page(): void
    {
        $this->get(route('filament.member.resources.profile.my-dependants.index'))
            ->assertOk();
    }

    public function test_non_chakama_member_cannot_access_my_dependants_page(): void
    {
        $user = User::factory()->create();
        Member::factory()->for($user)->create(['is_chakama' => false, 'is_sbf' => true]);
        $this->actingAs($user);

        $this->get(route('filament.member.resources.profile.my-dependants.index'))
            ->assertForbidden();
    }

    public function test_member_can_add_a_dependant_via_portal(): void
    {
        Livewire::test(ListMyDependants::class)
            ->callAction('create', [
                'name' => 'Test Child',
                'identity_type' => 'birth_cert_no',
                'identity_no' => 'BC-12345',
                'relationship' => 'Child',
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('bus_members', [
            'member_id' => $this->member->id,
            'type' => 'dependant',
            'name' => 'Test Child',
            'identity_no' => 'BC-12345',
        ]);
    }

    public function test_member_can_only_see_own_dependants(): void
    {
        $other = Member::factory()->create(['is_chakama' => true]);
        Dependant::factory()->create(['member_id' => $other->id, 'name' => 'Other Person Child']);

        $this->get(route('filament.member.resources.profile.my-dependants.index'))
            ->assertOk()
            ->assertDontSee('Other Person Child');
    }

    // --- Next of Kin ---

    public function test_chakama_member_can_access_my_next_of_kin_page(): void
    {
        $this->get(route('filament.member.resources.profile.my-next-of-kin.index'))
            ->assertOk();
    }

    public function test_non_chakama_member_cannot_access_my_next_of_kin_page(): void
    {
        $user = User::factory()->create();
        Member::factory()->for($user)->create(['is_chakama' => false, 'is_sbf' => true]);
        $this->actingAs($user);

        $this->get(route('filament.member.resources.profile.my-next-of-kin.index'))
            ->assertForbidden();
    }

    public function test_member_can_add_next_of_kin_via_portal(): void
    {
        Livewire::test(ListMyNextOfKin::class)
            ->callAction('create', [
                'name' => 'Jane Doe',
                'identity_type' => 'national_id',
                'identity_no' => '87654321',
                'relationship' => 'Spouse',
                'contact_preference' => 'phone',
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('bus_members', [
            'member_id' => $this->member->id,
            'type' => 'next_of_kin',
            'name' => 'Jane Doe',
            'identity_no' => '87654321',
        ]);
    }

    public function test_member_can_only_see_own_next_of_kin(): void
    {
        $other = Member::factory()->create(['is_chakama' => true]);
        NextOfKin::factory()->create(['member_id' => $other->id, 'name' => 'Other Person Kin']);

        $this->get(route('filament.member.resources.profile.my-next-of-kin.index'))
            ->assertOk()
            ->assertDontSee('Other Person Kin');
    }
}
