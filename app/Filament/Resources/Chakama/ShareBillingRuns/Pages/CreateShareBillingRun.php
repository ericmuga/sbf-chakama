<?php

namespace App\Filament\Resources\Chakama\ShareBillingRuns\Pages;

use App\Filament\Resources\Chakama\ShareBillingRuns\ShareBillingRunResource;
use Filament\Resources\Pages\CreateRecord;

class CreateShareBillingRun extends CreateRecord
{
    protected static string $resource = ShareBillingRunResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['status'] = 'draft';

        return $data;
    }
}
