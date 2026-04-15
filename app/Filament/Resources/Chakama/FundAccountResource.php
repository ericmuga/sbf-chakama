<?php

namespace App\Filament\Resources\Chakama;

use App\Filament\Resources\Chakama\Pages\CreateFundAccount;
use App\Filament\Resources\Chakama\Pages\EditFundAccount;
use App\Filament\Resources\Chakama\Pages\ListFundAccounts;
use App\Filament\Resources\Chakama\Pages\ViewFundAccount;
use App\Filament\Resources\Chakama\RelationManagers\FundTransactionsRelationManager;
use App\Models\Finance\GlAccount;
use App\Models\FundAccount;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FundAccountResource extends Resource
{
    protected static ?string $model = FundAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static \UnitEnum|string|null $navigationGroup = 'Chakama — Settings';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Fund Account Details')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                        Select::make('gl_account_no')
                            ->label('GL Account')
                            ->options(
                                GlAccount::query()
                                    ->where('account_type', 'Posting')
                                    ->pluck('name', 'no')
                            )
                            ->searchable()
                            ->nullable(),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
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
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('balance')
                    ->money('KES')
                    ->sortable(),
                TextColumn::make('gl_account_no')
                    ->label('GL Account'),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->date()
                    ->sortable(),
            ])
            ->filters([])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            FundTransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFundAccounts::route('/'),
            'create' => CreateFundAccount::route('/create'),
            'view' => ViewFundAccount::route('/{record}'),
            'edit' => EditFundAccount::route('/{record}/edit'),
        ];
    }
}
