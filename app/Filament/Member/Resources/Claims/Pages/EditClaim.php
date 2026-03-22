<?php

namespace App\Filament\Member\Resources\Claims\Pages;

use App\Enums\ClaimStatus;
use App\Filament\Member\Resources\Claims\ClaimResource;
use App\Models\Claim;
use Filament\Resources\Pages\EditRecord;

class EditClaim extends EditRecord
{
    protected static string $resource = ClaimResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        if ($this->getRecord()->status !== ClaimStatus::Draft) {
            $this->redirect($this->getResource()::getUrl('view', ['record' => $record]));
        }
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    public function canEdit(Claim $record): bool
    {
        return $record->status === ClaimStatus::Draft;
    }
}
