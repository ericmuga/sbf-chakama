<?php

namespace Tests\Feature;

use App\Enums\EntityDimension;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChakamaPurchaseHeaderAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_chakama_admin_can_open_purchase_header_create_on_chakama_panel(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'entity' => EntityDimension::Chakama,
        ]);

        $this->actingAs($admin)
            ->get(route('filament.chakama.resources.finance.purchase-headers.create', ['project' => 1]))
            ->assertOk();
    }
}
