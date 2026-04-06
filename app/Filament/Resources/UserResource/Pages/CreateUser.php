<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Member;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /** @var array<string, mixed>|null */
    protected ?array $pendingMemberData = null;

    protected ?int $linkMemberId = null;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
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
            'has_member_profile', 'link_member_id', 'identity_type',
            'identity_no', 'member_phone', 'member_status', 'is_chakama', 'is_sbf',
            'exclude_from_billing', 'customer_no', 'vendor_no',
        ];

        foreach ($extraFields as $field) {
            unset($data[$field]);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->linkMemberId !== null) {
            Member::where('id', $this->linkMemberId)->update(['user_id' => $this->record->id]);

            return;
        }

        if ($this->pendingMemberData !== null) {
            $this->record->member()->create(
                array_merge($this->pendingMemberData, ['type' => 'member'])
            );
        }
    }
}
