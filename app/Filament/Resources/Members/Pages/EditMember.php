<?php

namespace App\Filament\Resources\Members\Pages;

use App\Filament\Resources\Finance\Vendors\VendorResource;
use App\Filament\Resources\Members\MemberResource;
use App\Filament\Resources\UserResource;
use App\Filament\Widgets\Members\MemberLedgerWidget;
use App\Models\Member;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditMember extends EditRecord
{
    protected static string $resource = MemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewUser')
                ->label('View User Account')
                ->icon(Heroicon::OutlinedUserCircle)
                ->color('gray')
                ->visible(fn (Member $record): bool => (bool) $record->user_id)
                ->url(fn (Member $record): string => UserResource::getUrl('edit', ['record' => $record->user_id])),
            Action::make('viewVendor')
                ->label('View Vendor Record')
                ->icon(Heroicon::OutlinedBuildingStorefront)
                ->color('gray')
                ->visible(fn (Member $record): bool => (bool) $record->financeVendor)
                ->url(fn (Member $record): string => VendorResource::getUrl('edit', ['record' => $record->financeVendor])),
            DeleteAction::make(),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            MemberLedgerWidget::make(['record' => $this->record]),
        ];
    }
}
