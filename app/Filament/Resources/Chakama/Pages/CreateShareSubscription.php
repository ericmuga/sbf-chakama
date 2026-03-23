<?php

namespace App\Filament\Resources\Chakama\Pages;

use App\Filament\Resources\Chakama\ShareSubscriptionResource;
use App\Models\Member;
use App\Services\ShareService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateShareSubscription extends CreateRecord
{
    protected static string $resource = ShareSubscriptionResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $member = Member::findOrFail($data['member_id']);
        $service = app(ShareService::class);

        return $service->subscribe($member, $data);
    }
}
