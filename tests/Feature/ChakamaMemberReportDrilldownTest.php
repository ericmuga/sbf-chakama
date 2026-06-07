<?php

namespace Tests\Feature;

use App\Enums\EntityDimension;
use App\Filament\Resources\Chakama\ChakamaMemberReports\ChakamaMemberReportResource;
use App\Filament\Resources\Chakama\ChakamaMemberReports\Pages\ListChakamaMemberReports;
use App\Filament\Resources\Members\MemberResource;
use App\Models\Member;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ChakamaMemberReportDrilldownTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::factory()->create([
            'is_admin' => true,
            'entity' => EntityDimension::Chakama,
        ]);

        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('chakama'));
    }

    public function test_report_table_renders_and_member_no_links_to_profile(): void
    {
        $member = Member::factory()->create(['is_chakama' => true]);

        Livewire::test(ListChakamaMemberReports::class)
            ->assertCanSeeTableRecords([$member])
            ->assertSeeHtml(ChakamaMemberReportResource::getUrl('index'));

        $this->assertStringContainsString(
            (string) $member->id,
            MemberResource::getUrl('edit', ['record' => $member]),
        );
    }

    public function test_drilldown_modal_actions_are_callable(): void
    {
        $member = Member::factory()->create(['is_chakama' => true]);

        Livewire::test(ListChakamaMemberReports::class)
            ->callTableColumnAction('opening_balance', $member->getKey())
            ->assertHasNoErrors();

        Livewire::test(ListChakamaMemberReports::class)
            ->callTableColumnAction('movement_in', $member->getKey())
            ->assertHasNoErrors();

        Livewire::test(ListChakamaMemberReports::class)
            ->callTableColumnAction('movement_out', $member->getKey())
            ->assertHasNoErrors();

        Livewire::test(ListChakamaMemberReports::class)
            ->callTableColumnAction('closing_balance', $member->getKey())
            ->assertHasNoErrors();

        Livewire::test(ListChakamaMemberReports::class)
            ->callTableColumnAction('share_count', $member->getKey())
            ->assertHasNoErrors();
    }
}
