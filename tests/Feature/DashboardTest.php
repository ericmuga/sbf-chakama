<?php

namespace Tests\Feature;

use App\Models\Dependant;
use App\Models\Document;
use App\Models\Member;
use App\Models\NextOfKin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_dashboard_does_not_query_documents_when_rendering_member_card(): void
    {
        $user = User::factory()->create();
        $member = Member::factory()->for($user)->create();
        $dependant = Dependant::factory()->for($member)->create();
        $nextOfKin = NextOfKin::factory()->for($member)->create();

        Document::query()->create([
            'documentable_type' => Member::class,
            'documentable_id' => $member->id,
            'document_type' => 'id',
            'document_no' => 'MEM-DOC-001',
            'file_path' => 'documents/member/id.pdf',
        ]);

        Document::query()->create([
            'documentable_type' => Dependant::class,
            'documentable_id' => $dependant->id,
            'document_type' => 'birth_certificate',
            'document_no' => 'DEP-DOC-001',
            'file_path' => 'documents/dependant/birth-certificate.pdf',
        ]);

        Document::query()->create([
            'documentable_type' => NextOfKin::class,
            'documentable_id' => $nextOfKin->id,
            'document_type' => 'national_id',
            'document_no' => 'KIN-DOC-001',
            'file_path' => 'documents/next-of-kin/national-id.pdf',
        ]);

        $this->actingAs($user);

        DB::enableQueryLog();
        DB::flushQueryLog();

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee($dependant->name)
            ->assertSee($nextOfKin->name);

        $documentQueries = collect(DB::getQueryLog())
            ->pluck('query')
            ->filter(fn (string $query): bool => str_contains(strtolower($query), 'bus_documents'));

        $this->assertCount(0, $documentQueries);
    }
}
