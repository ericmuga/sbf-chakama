<?php

namespace Tests\Feature;

use App\Enums\EntityDimension;
use App\Filament\Resources\Finance\Vendors\VendorResource;
use App\Filament\Resources\Members\MemberResource;
use App\Filament\Resources\Members\Pages\EditMember;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Models\Finance\Vendor;
use App\Models\Member;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MemberUserVendorCrossLinkTest extends TestCase
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

    public function test_member_edit_page_links_to_linked_user_and_vendor(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create();
        $member = Member::factory()->create([
            'user_id' => $user->id,
            'vendor_no' => $vendor->no,
        ]);

        Livewire::test(EditMember::class, ['record' => $member->getRouteKey()])
            ->assertActionVisible('viewUser')
            ->assertActionHasUrl('viewUser', UserResource::getUrl('edit', ['record' => $user]))
            ->assertActionVisible('viewVendor')
            ->assertActionHasUrl('viewVendor', VendorResource::getUrl('edit', ['record' => $vendor]));
    }

    public function test_member_edit_page_hides_links_when_nothing_linked(): void
    {
        $member = Member::factory()->create([
            'user_id' => null,
            'vendor_no' => null,
        ]);

        Livewire::test(EditMember::class, ['record' => $member->getRouteKey()])
            ->assertActionHidden('viewUser')
            ->assertActionHidden('viewVendor');
    }

    public function test_user_edit_page_links_to_linked_member_and_vendor(): void
    {
        $vendor = Vendor::factory()->create();
        $user = User::factory()->create();
        $member = Member::factory()->create([
            'user_id' => $user->id,
            'vendor_no' => $vendor->no,
        ]);

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->assertActionVisible('viewMember')
            ->assertActionHasUrl('viewMember', MemberResource::getUrl('edit', ['record' => $member]))
            ->assertActionVisible('viewVendor')
            ->assertActionHasUrl('viewVendor', VendorResource::getUrl('edit', ['record' => $vendor]));
    }

    public function test_user_edit_page_hides_links_when_no_member_linked(): void
    {
        $user = User::factory()->create();

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->assertActionHidden('viewMember')
            ->assertActionHidden('viewVendor');
    }
}
