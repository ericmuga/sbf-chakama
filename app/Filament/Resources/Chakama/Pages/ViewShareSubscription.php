<?php

namespace App\Filament\Resources\Chakama\Pages;

use App\Enums\ShareStatus;
use App\Filament\Resources\Chakama\ShareSubscriptionResource;
use App\Models\Member;
use App\Models\ShareSubscription;
use App\Services\ShareService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ViewRecord;

class ViewShareSubscription extends ViewRecord
{
    protected static string $resource = ShareSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        /** @var ShareSubscription $record */
        $record = $this->getRecord();
        $service = app(ShareService::class);

        return [
            Action::make('activate')
                ->label('Activate')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->visible(fn () => $record->status === ShareStatus::PendingPayment)
                ->requiresConfirmation()
                ->action(fn () => $service->activateSubscription($record)),

            Action::make('suspend')
                ->label('Suspend')
                ->color('warning')
                ->icon('heroicon-o-pause-circle')
                ->visible(fn () => $record->status === ShareStatus::Active)
                ->schema([
                    Textarea::make('reason')
                        ->label('Reason for Suspension')
                        ->required()
                        ->rows(3),
                ])
                ->action(fn (array $data) => $service->suspendSubscription($record, $data['reason'])),

            Action::make('transfer')
                ->label('Transfer to Member')
                ->color('info')
                ->icon('heroicon-o-arrow-right-circle')
                ->visible(fn () => in_array($record->status, [ShareStatus::Active, ShareStatus::PendingPayment]))
                ->schema([
                    Select::make('new_member_id')
                        ->label('New Member')
                        ->options(
                            Member::query()
                                ->where('is_chakama', true)
                                ->where('id', '!=', $record->member_id)
                                ->pluck('name', 'id')
                        )
                        ->searchable()
                        ->required(),
                ])
                ->action(fn (array $data) => $service->transferSubscription(
                    $record,
                    Member::findOrFail($data['new_member_id'])
                )),
        ];
    }
}
