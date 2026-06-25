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
use App\Notifications\IssueClosedNotification;
use App\Notifications\IssueLoggedNotification;
use App\Services\IssueImportService;
use Database\Seeders\IssueSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
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
        $this->assertSame(5, Issue::where('status', IssueStatus::PendingQaReview)->count());
        $this->assertSame(1, Issue::where('status', IssueStatus::Open)->count());
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

    public function test_issues_can_be_exported_as_csv(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Issue::factory()->create(['title' => 'Exportable Issue']);

        $response = $this->get(route('admin.issues.export'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Exportable Issue', $response->streamedContent());
        $this->assertStringContainsString('date_actioned', $response->streamedContent());
    }

    public function test_issues_can_be_imported_from_csv(): void
    {
        $csv = "title,portal,type,details,status,date_actioned\n"
            ."Imported Bug,chakama,functional,Something broke,open,15/06/2026\n";

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $csv);
        rewind($handle);

        $result = app(IssueImportService::class)->importFromHandle($handle);
        fclose($handle);

        $this->assertSame(1, $result['imported']);
        $issue = Issue::where('title', 'Imported Bug')->first();
        $this->assertNotNull($issue);
        $this->assertSame('chakama', $issue->portal_type->value);
        $this->assertSame('2026-06-15', $issue->date_actioned->format('Y-m-d'));
    }

    public function test_developers_are_notified_when_an_issue_is_logged(): void
    {
        Notification::fake();

        $developer = User::factory()->create(['role' => UserRole::Developer]);
        User::factory()->create(['role' => UserRole::BusinessAnalyst]);

        Issue::factory()->create();

        Notification::assertSentTo($developer, IssueLoggedNotification::class);
    }

    public function test_business_analysts_are_notified_when_an_issue_is_closed(): void
    {
        Notification::fake();

        $ba = User::factory()->create(['role' => UserRole::BusinessAnalyst]);
        $issue = Issue::factory()->open()->create();

        $issue->update(['status' => IssueStatus::Closed]);

        Notification::assertSentTo($ba, IssueClosedNotification::class);
    }

    public function test_importing_updates_existing_issue_without_duplicating(): void
    {
        $csv = "title,details,status\nDup Issue,Same details,open\n";

        foreach (range(1, 2) as $_) {
            $handle = fopen('php://temp', 'r+');
            fwrite($handle, $csv);
            rewind($handle);
            app(IssueImportService::class)->importFromHandle($handle);
            fclose($handle);
        }

        $this->assertSame(1, Issue::where('title', 'Dup Issue')->count());
    }
}
