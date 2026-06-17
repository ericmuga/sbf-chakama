<?php

namespace Tests\Feature\Finance;

use App\Filament\Resources\Finance\BankPostingGroups\BankPostingGroupResource;
use App\Filament\Resources\Finance\BankPostingGroups\Pages\CreateBankPostingGroup;
use App\Models\Finance\BankAccount;
use App\Models\Finance\BankPostingGroup;
use App\Models\Finance\GlAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BankPostingGroupResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_admin_can_create_bank_posting_group_linked_to_gl_account(): void
    {
        GlAccount::create(['no' => '1050', 'name' => 'Bank - Equity', 'account_type' => 'asset']);

        Livewire::test(CreateBankPostingGroup::class)
            ->fillForm([
                'code' => 'MAIN-BANK',
                'description' => 'Main Bank Account',
                'bank_account_gl_no' => '1050',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('bank_posting_groups', [
            'code' => 'MAIN-BANK',
            'description' => 'Main Bank Account',
            'bank_account_gl_no' => '1050',
        ]);
    }

    public function test_gl_account_is_required(): void
    {
        Livewire::test(CreateBankPostingGroup::class)
            ->fillForm([
                'code' => 'MAIN-BANK',
                'description' => 'Main Bank Account',
                'bank_account_gl_no' => null,
            ])
            ->call('create')
            ->assertHasFormErrors(['bank_account_gl_no' => 'required']);
    }

    public function test_posting_group_cannot_be_deleted_while_bank_accounts_are_linked(): void
    {
        $group = BankPostingGroup::factory()->create();

        $this->assertTrue(BankPostingGroupResource::canDelete($group));

        BankAccount::factory()->create(['bank_posting_group_id' => $group->id]);

        $this->assertFalse(BankPostingGroupResource::canDelete($group->fresh()));
    }
}
