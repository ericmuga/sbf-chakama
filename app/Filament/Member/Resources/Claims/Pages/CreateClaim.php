<?php

namespace App\Filament\Member\Resources\Claims\Pages;

use App\Filament\Member\Resources\Claims\ClaimResource;
use App\Services\ClaimService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateClaim extends CreateRecord
{
    protected static string $resource = ClaimResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $member = auth()->user()->member;

        return app(ClaimService::class)->createClaim($member, $data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
