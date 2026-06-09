<?php

namespace Tests\Feature;

use App\Enums\EntityDimension;
use App\Filament\Resources\Finance\GeneralPostingSetups\Pages\CreateGeneralPostingSetup;
use App\Models\Finance\CustomerPostingGroup;
use App\Models\Finance\GlAccount;
use App\Models\Finance\ServicePostingGroup;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GeneralPostingSetupRelationshipFormTest extends TestCase
{
    use RefreshDatabase;

    private CustomerPostingGroup $customerPostingGroup;

    private ServicePostingGroup $servicePostingGroup;

    private GlAccount $salesAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customerPostingGroup = CustomerPostingGroup::factory()->create();
        $this->servicePostingGroup = ServicePostingGroup::factory()->create();
        $this->salesAccount = GlAccount::create([
            'no' => '4000',
            'name' => 'Sales Revenue',
            'account_type' => 'Posting',
        ]);
    }

    private function fillAndCreate(): void
    {
        Livewire::test(CreateGeneralPostingSetup::class)
            ->fillForm([
                'customer_posting_group_id' => $this->customerPostingGroup->id,
                'service_posting_group_id' => $this->servicePostingGroup->id,
                'sales_account_no' => $this->salesAccount->no,
            ])
            ->call('create')
            ->assertHasNoFormErrors();
    }

    public function test_sbf_panel_saves_gl_account_no_from_relationship_select(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'entity' => EntityDimension::Sbf]);
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('sbf'));

        $this->fillAndCreate();

        $this->assertDatabaseHas('general_posting_setups', [
            'customer_posting_group_id' => $this->customerPostingGroup->id,
            'service_posting_group_id' => $this->servicePostingGroup->id,
            'sales_account_no' => '4000',
        ]);
    }

    public function test_chakama_panel_can_create_general_posting_setup(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'entity' => EntityDimension::Chakama]);
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('chakama'));

        $this->fillAndCreate();

        $this->assertDatabaseHas('general_posting_setups', [
            'sales_account_no' => '4000',
        ]);
    }
}
