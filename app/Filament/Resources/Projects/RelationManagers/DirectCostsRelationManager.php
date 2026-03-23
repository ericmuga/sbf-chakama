<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use App\Enums\DirectCostStatus;
use App\Enums\DirectCostType;
use App\Models\Finance\GlAccount;
use App\Models\Finance\Vendor;
use App\Models\ProjectDirectCost;
use App\Services\ProjectCostService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
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
use Illuminate\Support\Facades\Storage;

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
                            ->options(DirectCostType::class)
                            ->default(DirectCostType::Other->value)
                            ->required(),
                        Select::make('gl_account_no')
                            ->label('GL Account')
                            ->options(
                                GlAccount::query()
                                    ->where('account_type', 'Posting')
                                    ->orderBy('no')
                                    ->get()
                                    ->mapWithKeys(fn (GlAccount $account): array => [$account->no => $account->no.' - '.$account->name])
                            )
                            ->searchable()
                            ->required(),
                        Select::make('vendor_id')
                            ->label('Vendor')
                            ->options(Vendor::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable(),
                        Select::make('bank_account_id')
                            ->relationship('bankAccount', 'name')
                            ->searchable()
                            ->nullable(),
                        TextInput::make('receipt_number')
                            ->maxLength(100),
                        FileUpload::make('receipt_path')
                            ->disk('public')
                            ->directory('project-receipts/'.$this->getOwnerRecord()->no)
                            ->visibility('public')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png']),
                        DatePicker::make('posting_date')
                            ->default(today())
                            ->required(),
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
                            TextEntry::make('receipt_number'),
                            TextEntry::make('rejection_reason')
                                ->columnSpanFull(),
                            TextEntry::make('submitter.name')->label('Submitted By'),
                            TextEntry::make('approver.name')->label('Approved By'),
                            TextEntry::make('approved_at')->dateTime(),
                        ])->columns(2),
                    ]),
                Action::make('view_receipt')
                    ->label('View Receipt')
                    ->icon('heroicon-o-eye')
                    ->visible(fn (ProjectDirectCost $record): bool => filled($record->receipt_path))
                    ->url(fn (ProjectDirectCost $record): string => Storage::disk('public')->url($record->receipt_path))
                    ->openUrlInNewTab(),
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
                Action::make('void_cost')
                    ->label('Void')
                    ->color('gray')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->visible(fn (ProjectDirectCost $record): bool => $record->status === DirectCostStatus::Posted)
                    ->requiresConfirmation()
                    ->action(function (ProjectDirectCost $record): void {
                        app(ProjectCostService::class)->voidDirectCost($record, auth()->user());
                        Notification::make()->success()->title('Direct cost voided.')->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
