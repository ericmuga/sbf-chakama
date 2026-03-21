<?php

namespace App\Filament\Resources\Finance;

use App\Filament\Resources\Finance\CustomerResource\Pages\CreateCustomer;
use App\Filament\Resources\Finance\CustomerResource\Pages\EditCustomer;
use App\Filament\Resources\Finance\CustomerResource\Pages\ListCustomers;
use App\Models\Finance\Customer;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static UnitEnum|string|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('no')
                    ->label('Customer No')
                    ->maxLength(50)
                    ->unique(ignoreRecord: true),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('customer_posting_group_id')
                    ->label('Customer Posting Group')
                    ->relationship('customerPostingGroup', 'description')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('payment_terms_code')
                    ->label('Payment Terms')
                    ->relationship('paymentTerms', 'description')
                    ->searchable()
                    ->preload(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no')
                    ->label('Customer No')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customerPostingGroup.description')
                    ->label('Posting Group')
                    ->badge(),
                TextColumn::make('payment_terms_code')
                    ->label('Payment Terms'),
            ])
            ->defaultSort('no')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'create' => CreateCustomer::route('/create'),
            'edit' => EditCustomer::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }
}
