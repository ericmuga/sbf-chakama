<?php

namespace App\Filament\Resources\ClaimApprovalTemplates\Pages;

use App\Filament\Resources\ClaimApprovalTemplates\ClaimApprovalTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListClaimApprovalTemplates extends ListRecords
{
    protected static string $resource = ClaimApprovalTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
