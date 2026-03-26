<?php

namespace App\Filament\Resources\Chakama;

use App\Enums\FundWithdrawalStatus;
use App\Filament\Resources\Chakama\Pages\CreateFundWithdrawal;
use App\Filament\Resources\Chakama\Pages\EditFundWithdrawal;
use App\Filament\Resources\Chakama\Pages\ListFundWithdrawals;
use App\Filament\Resources\Chakama\Pages\ViewFundWithdrawal;
use App\Models\FundAccount;
use App\Models\FundWithdrawal;
use App\Models\Project;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FundWithdrawalResource extends Resource
{
    protected static ?string $model = FundWithdrawal::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static \UnitEnum|string|null $navigationGroup = 'Chakama — Funds';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Withdrawal Details')
                    ->columns(2)
                    ->schema([
                        Select::make('fund_account_id')
                            ->label('Fund Account')
                            ->options(FundAccount::active()->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        Select::make('project_id')
                            ->label('Related Project')
                            ->options(Project::query()->pluck('name', 'id'))
                            ->searchable()
                            ->nullable(),
                        Textarea::make('description')
                            ->required()
                            ->columnSpanFull()
                            ->rows(3),
                        TextInput::make('amount')
                            ->label('Amount (KES)')
                            ->numeric()
                            ->prefix('KES')
                            ->required()
                            ->minValue(0.01),
                    ]),
                Section::make('Payee Details')
                    ->columns(2)
                    ->schema([
                        TextInput::make('payee_name')
                            ->required()
                            ->maxLength(255),
                        Select::make('payment_method')
                            ->options([
                                'bank' => 'Bank Transfer',
                                'mpesa' => 'M-PESA',
                                'cash' => 'Cash',
                            ])
                            ->live()
                            ->required(),
                        TextInput::make('bank_name')
                            ->visible(fn (Get $get): bool => $get('payment_method') === 'bank'),
                        TextInput::make('bank_account_name')
                            ->visible(fn (Get $get): bool => $get('payment_method') === 'bank'),
                        TextInput::make('bank_account_no')
                            ->visible(fn (Get $get): bool => $get('payment_method') === 'bank'),
                        TextInput::make('bank_branch')
                            ->visible(fn (Get $get): bool => $get('payment_method') === 'bank'),
                        TextInput::make('mpesa_phone')
                            ->label('M-PESA Phone')
                            ->tel()
                            ->visible(fn (Get $get): bool => $get('payment_method') === 'mpesa'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('fundAccount.name')
                    ->label('Fund Account')
                    ->sortable(),
                TextColumn::make('description')
                    ->limit(40)
                    ->tooltip(fn (FundWithdrawal $record): string => $record->description),
                TextColumn::make('amount')
                    ->money('KES')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (FundWithdrawalStatus $state): string => $state->color()),
                TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(FundWithdrawalStatus::class),
                SelectFilter::make('fund_account_id')
                    ->label('Fund Account')
                    ->options(FundAccount::pluck('name', 'id')),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFundWithdrawals::route('/'),
            'create' => CreateFundWithdrawal::route('/create'),
            'view' => ViewFundWithdrawal::route('/{record}'),
            'edit' => EditFundWithdrawal::route('/{record}/edit'),
        ];
    }
}
