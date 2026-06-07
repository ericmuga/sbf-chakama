<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChakamaMemberReportPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_chakama_member_report_pdf(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        Member::factory()->count(3)->create(['is_chakama' => true]);

        $this->actingAs($admin)
            ->get(route('admin.reports.chakama-member-report.pdf'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_non_admin_cannot_download_chakama_member_report_pdf(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get(route('admin.reports.chakama-member-report.pdf'))
            ->assertForbidden();
    }

    public function test_unauthenticated_user_is_redirected(): void
    {
        $this->get(route('admin.reports.chakama-member-report.pdf'))
            ->assertRedirect();
    }
}
