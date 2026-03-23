<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use App\Enums\DirectCostStatus;
use App\Enums\DirectCostType;
use App\Models\Finance\GlAccount;
use App\Models\ProjectDirectCost;
use App\Services\ProjectCostService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DirectCostsRelationManager extends RelationManager
{
    protected static string $relationship = 'directCosts';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('no')
            ->columns([
                TextColumn::make('no')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->limit(40),
                TextColumn::make('cost_type')
                    ->badge(),
                TextColumn::make('amount')
                    ->money('KES')
                    ->sortable(),
                TextColumn::make('gl_account_no')
                    ->label('GL Account'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('submitter.name')
                    ->label('Submitted By'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(DirectCostStatus::class),
                SelectFilter::make('cost_type')
                    ->options(DirectCostType::class),
            ])
            ->headerActions([
                CreateAction::make()
                    ->schema([
                        Textarea::make('description')
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('amount')
                            ->numeric()
                            ->required()
                            ->minValue(0),
                        Select::make('cost_type')
                            ->options(DirectCostType::class),
                        Select::make('gl_account_no')
                            ->label('GL Account')
                            ->options(
                                GlAccount::query()
                                    ->where('account_type', 'Posting')
                                    ->pluck('no', 'no')
                            )
                            ->searchable(),
                        Select::make('bank_account_id')
                            ->relationship('bankAccount', 'name')
                            ->searchable()
                            ->nullable(),
                        DatePicker::make('posting_date')
                            ->default(today()),
                    ])
                    ->using(function (array $data): ProjectDirectCost {
                        return app(ProjectCostService::class)->submitDirectCost(
                            $this->getOwnerRecord(),
                            $data,
                            auth()->user(),
                        );
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->schema([
                        Section::make()->schema([
                            TextEntry::make('no'),
                            TextEntry::make('description'),
                            TextEntry::make('cost_type')->badge(),
                            TextEntry::make('amount')->money('KES'),
                            TextEntry::make('gl_account_no')->label('GL Account'),
                            TextEntry::make('status')->badge(),
                            TextEntry::make('posting_date')->date(),
                            TextEntry::make('submitter.name')->label('Submitted By'),
                            TextEntry::make('approver.name')->label('Approved By'),
                            TextEntry::make('approved_at')->dateTime(),
                        ])->columns(2),
                    ]),
                Action::make('approve')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (ProjectDirectCost $record): bool => $record->status === DirectCostStatus::Pending)
                    ->requiresConfirmation()
                    ->action(function (ProjectDirectCost $record): void {
                        app(ProjectCostService::class)->approveDirectCost($record, auth()->user());
                        Notification::make()->success()->title('Direct cost approved.')->send();
                    }),
                Action::make('post')
                    ->color('primary')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->visible(fn (ProjectDirectCost $record): bool => $record->status === DirectCostStatus::Approved)
                    ->requiresConfirmation()
                    ->action(function (ProjectDirectCost $record): void {
                        app(ProjectCostService::class)->postDirectCost($record, auth()->user());
                        Notification::make()->success()->title('Direct cost posted.')->send();
                    }),
                Action::make('reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->visible(fn (ProjectDirectCost $record): bool => $record->status === DirectCostStatus::Pending)
                    ->schema([
                        Textarea::make('reason')
                            ->required(),
                    ])
                    ->action(function (ProjectDirectCost $record, array $data): void {
                        app(ProjectCostService::class)->rejectDirectCost($record, auth()->user(), $data['reason']);
                        Notification::make()->warning()->title('Direct cost rejected.')->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
