<?php

namespace Tests\Feature;

use App\Enums\IssueStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Issues\IssueResource;
use App\Filament\Resources\Issues\Pages\CreateIssue;
use App\Filament\Resources\Issues\Pages\ListIssues;
use App\Filament\Resources\Releases\Pages\CreateRelease;
use App\Filament\Resources\Releases\ReleaseResource;
use App\Models\Issue;
use App\Models\Release;
use App\Models\User;
use Database\Seeders\IssueSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class IssueTrackerTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsPanelUser(User $user): User
    {
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('sbf'));

        return $user;
    }

    public function test_admin_can_access_the_issue_tracker(): void
    {
        $this->actingAsPanelUser(User::factory()->create(['is_admin' => true]));

        $this->assertTrue(IssueResource::canAccess());
        $this->assertTrue(ReleaseResource::canAccess());
    }

    public function test_developer_and_business_analyst_can_access(): void
    {
        $this->actingAsPanelUser(User::factory()->create(['is_admin' => true, 'role' => UserRole::Developer]));
        $this->assertTrue(IssueResource::canAccess());

        $this->actingAsPanelUser(User::factory()->create(['is_admin' => true, 'role' => UserRole::BusinessAnalyst]));
        $this->assertTrue(ReleaseResource::canAccess());
    }

    public function test_user_without_role_cannot_access(): void
    {
        $this->actingAsPanelUser(User::factory()->create(['is_admin' => false, 'role' => null]));

        $this->assertFalse(IssueResource::canAccess());
        $this->assertFalse(ReleaseResource::canAccess());
    }

    public function test_an_issue_can_be_created(): void
    {
        $this->actingAsPanelUser(User::factory()->create(['is_admin' => true]));

        Livewire::test(CreateIssue::class)
            ->fillForm([
                'title' => 'Posted Sales Invoice',
                'portal_type' => 'sbf',
                'category' => 'development',
                'status' => 'open',
                'details' => 'Introduce the sales amount on the page',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('app_issues', [
            'title' => 'Posted Sales Invoice',
            'status' => 'open',
        ]);
    }

    public function test_a_release_can_be_created_with_issues_listed(): void
    {
        $this->actingAsPanelUser(User::factory()->create(['is_admin' => true]));

        Livewire::test(CreateRelease::class)
            ->fillForm([
                'version' => 'v1.0.0',
                'name' => 'First release',
                'status' => 'released',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $release = Release::where('version', 'v1.0.0')->first();
        $this->assertNotNull($release);

        Issue::factory()->create(['release_id' => $release->id]);
        $this->assertSame(1, $release->refresh()->issues()->count());
    }

    public function test_issues_table_renders_records(): void
    {
        $this->actingAsPanelUser(User::factory()->create(['is_admin' => true]));
        $issues = Issue::factory()->count(3)->create();

        Livewire::test(ListIssues::class)
            ->assertCanSeeTableRecords($issues);
    }

    public function test_seeder_imports_the_twelve_tracker_issues(): void
    {
        (new IssueSeeder)->run();

        $this->assertSame(12, Issue::count());
        $this->assertSame(6, Issue::where('status', IssueStatus::Closed)->count());
        $this->assertSame(6, Issue::where('status', IssueStatus::Open)->count());
        $this->assertDatabaseHas('app_issues', [
            'title' => 'Share allocations',
            'portal_type' => 'chakama',
            'status' => 'open',
        ]);
    }

    public function test_seeder_is_idempotent(): void
    {
        (new IssueSeeder)->run();
        (new IssueSeeder)->run();

        $this->assertSame(12, Issue::count());
    }
}
