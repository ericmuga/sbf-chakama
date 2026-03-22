<?php

namespace App\Filament\Resources\ClaimApprovalTemplates\Pages;

use App\Filament\Resources\ClaimApprovalTemplates\ClaimApprovalTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditClaimApprovalTemplate extends EditRecord
{
    protected static string $resource = ClaimApprovalTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
