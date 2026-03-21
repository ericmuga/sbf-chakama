<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateDownloadTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_members_template_downloads_as_csv(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('admin.templates.members'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('no,name,identity_type,identity_no', $content);
        $this->assertStringContainsString('MBR-001', $content);
    }

    public function test_dependants_template_downloads_as_csv(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('admin.templates.dependants'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('member_no,name,identity_type', $content);
    }

    public function test_next_of_kin_template_downloads_as_csv(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('admin.templates.next-of-kin'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('member_no,name,identity_type', $content);
        $this->assertStringContainsString('contact_preference', $content);
    }

    public function test_templates_require_authentication(): void
    {
        $this->get(route('admin.templates.members'))->assertRedirect(route('login'));
        $this->get(route('admin.templates.dependants'))->assertRedirect(route('login'));
        $this->get(route('admin.templates.next-of-kin'))->assertRedirect(route('login'));
    }
}
