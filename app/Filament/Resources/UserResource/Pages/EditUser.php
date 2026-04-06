<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Member;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /** @var array<string, mixed>|null */
    protected ?array $pendingMemberData = null;

    protected ?int $linkMemberId = null;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $member = $this->record->member;

        if ($member) {
            $data['has_member_profile'] = true;
            $data['link_member_id'] = $member->id;
            $data['member_no'] = $member->no; // read-only display only
            $data['identity_type'] = $member->identity_type;
            $data['identity_no'] = $member->identity_no;
            $data['member_phone'] = $member->phone;
            $data['member_status'] = $member->member_status;
            $data['is_chakama'] = $member->is_chakama;
            $data['is_sbf'] = $member->is_sbf;
            $data['exclude_from_billing'] = $member->exclude_from_billing;
            $data['customer_no'] = $member->customer_no;
            $data['vendor_no'] = $member->vendor_no;
        } else {
            $data['has_member_profile'] = false;
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->pendingMemberData = null;
        $this->linkMemberId = null;

        if ($data['has_member_profile'] ?? false) {
            $linkId = $data['link_member_id'] ?? null;

            if (filled($linkId)) {
                $this->linkMemberId = (int) $linkId;
            } else {
                $memberFields = [
                    'identity_type', 'identity_no', 'member_phone',
                    'member_status', 'is_chakama', 'is_sbf', 'exclude_from_billing',
                    'customer_no', 'vendor_no',
                ];

                $memberData = array_filter(
                    array_intersect_key($data, array_flip($memberFields)),
                    fn ($v) => $v !== null && $v !== ''
                );

                if (! empty($memberData)) {
                    if (isset($memberData['member_phone'])) {
                        $memberData['phone'] = $memberData['member_phone'];
                        unset($memberData['member_phone']);
                    }

                    $this->pendingMemberData = $memberData;
                }
            }
        }

        $extraFields = [
            'has_member_profile', 'link_member_id', 'member_no', // member_no is display-only (disabled+dehydrated=false)
            'identity_type', 'identity_no', 'member_phone', 'member_status',
            'is_chakama', 'is_sbf', 'exclude_from_billing', 'customer_no', 'vendor_no',
        ];

        foreach ($extraFields as $field) {
            unset($data[$field]);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->linkMemberId !== null) {
            $currentMember = $this->record->member;

            // De-link the old member if it's different
            if ($currentMember && $currentMember->id !== $this->linkMemberId) {
                $currentMember->update(['user_id' => null]);
            }

            Member::where('id', $this->linkMemberId)->update(['user_id' => $this->record->id]);

            return;
        }

        if ($this->pendingMemberData === null) {
            return;
        }

        $member = $this->record->member;

        if ($member) {
            $member->update($this->pendingMemberData);
        } else {
            $this->record->member()->create(
                array_merge($this->pendingMemberData, ['type' => 'member'])
            );
        }
    }
}
