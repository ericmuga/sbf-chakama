<?php

namespace App\Filament\Member\Resources\Claims\Tables;

use App\Enums\ClaimStatus;
use App\Enums\ClaimType;
use App\Filament\Member\Resources\Claims\ClaimResource;
use App\Models\Claim;
use App\Services\ClaimService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ClaimsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no')
                    ->label('Claim No')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('claim_type')
                    ->label('Type')
                    ->badge(),
                TextColumn::make('subject')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('claimed_amount')
                    ->label('Claimed (KES)')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('approved_amount')
                    ->label('Approved (KES)')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(ClaimStatus::class),
                SelectFilter::make('claim_type')
                    ->label('Type')
                    ->options(ClaimType::class),
            ])
            ->emptyStateHeading('You have no claims yet')
            ->emptyStateDescription('Submit a claim when you need assistance.')
            ->emptyStateActions([
                Action::make('create')
                    ->label('Submit a Claim')
                    ->url(fn () => ClaimResource::getUrl('create')),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (Claim $record) => $record->status === ClaimStatus::Draft),
                DeleteAction::make()
                    ->visible(fn (Claim $record) => $record->status === ClaimStatus::Draft),
                Action::make('submit')
                    ->label('Submit')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->visible(fn (Claim $record) => $record->status === ClaimStatus::Draft)
                    ->requiresConfirmation()
                    ->action(function (Claim $record): void {
                        app(ClaimService::class)->submitClaim($record, auth()->user());

                        Notification::make()
                            ->success()
                            ->title('Claim submitted for approval.')
                            ->send();
                    }),
                Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Claim $record) => in_array($record->status, [ClaimStatus::Draft, ClaimStatus::Submitted]))
                    ->schema([
                        Textarea::make('reason')
                            ->label('Cancellation Reason')
                            ->required()
                            ->maxLength(500),
                    ])
                    ->action(function (Claim $record, array $data): void {
                        app(ClaimService::class)->cancelClaim($record, auth()->user(), $data['reason']);

                        Notification::make()
                            ->warning()
                            ->title('Claim cancelled.')
                            ->send();
                    }),
            ])
            ->toolbarActions([]);
    }
}
