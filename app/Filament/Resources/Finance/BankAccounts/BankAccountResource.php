<?php

namespace App\Filament\Resources\Finance\BankAccounts;

use App\Filament\Resources\Finance\BankAccounts\Pages\CreateBankAccount;
use App\Filament\Resources\Finance\BankAccounts\Pages\EditBankAccount;
use App\Filament\Resources\Finance\BankAccounts\Pages\ListBankAccounts;
use App\Filament\Resources\Finance\BankAccounts\Schemas\BankAccountForm;
use App\Filament\Resources\Finance\BankAccounts\Tables\BankAccountsTable;
use App\Models\Finance\BankAccount;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class BankAccountResource extends Resource
{
    protected static ?string $model = BankAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static UnitEnum|string|null $navigationGroup = 'Finance — Setup';

    protected static ?string $navigationLabel = 'Bank Accounts';

    protected static ?int $navigationSort = 100;

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

    public static function form(Schema $schema): Schema
    {
        return BankAccountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BankAccountsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBankAccounts::route('/'),
            'create' => CreateBankAccount::route('/create'),
            'edit' => EditBankAccount::route('/{record}/edit'),
        ];
    }
}
