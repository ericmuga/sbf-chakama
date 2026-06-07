<?php

namespace Tests\Feature;

use App\Enums\MemberGroupMode;
use App\Models\Member;
use App\Models\MemberGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberGroupTest extends TestCase
{
    use RefreshDatabase;

    public function test_include_mode_resolves_only_attached_members(): void
    {
        $a = Member::factory()->create(['is_chakama' => true, 'member_status' => 'active']);
        $b = Member::factory()->create(['is_chakama' => true, 'member_status' => 'active']);
        $c = Member::factory()->create(['is_chakama' => true, 'member_status' => 'active']);

        $group = MemberGroup::create([
            'name' => 'Founders',
            'mode' => MemberGroupMode::Include,
            'is_active' => true,
        ]);
        $group->members()->attach([$a->id, $b->id]);

        $resolved = $group->resolveMemberIds()->all();

        $this->assertEqualsCanonicalizing([$a->id, $b->id], $resolved);
        $this->assertNotContains($c->id, $resolved);
    }

    public function test_all_except_mode_returns_active_chakama_members_minus_attached(): void
    {
        $included1 = Member::factory()->create(['is_chakama' => true, 'member_status' => 'active']);
        $included2 = Member::factory()->create(['is_chakama' => true, 'member_status' => 'active']);
        $excluded = Member::factory()->create(['is_chakama' => true, 'member_status' => 'active']);
        // not chakama — should never appear
        Member::factory()->create(['is_chakama' => false, 'member_status' => 'active']);
        // non-active — should never appear in all_except resolution
        Member::factory()->create(['is_chakama' => true, 'member_status' => 'lapsed']);

        $group = MemberGroup::create([
            'name' => 'Everyone except defaulters',
            'mode' => MemberGroupMode::AllExcept,
            'is_active' => true,
        ]);
        $group->members()->attach($excluded->id);

        $resolved = $group->resolveMemberIds()->all();

        $this->assertEqualsCanonicalizing([$included1->id, $included2->id], $resolved);
        $this->assertNotContains($excluded->id, $resolved);
    }
}
