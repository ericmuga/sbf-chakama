<?php

namespace App\Filament\Resources\Finance\GlAccounts;

use App\Filament\Resources\Finance\GlAccounts\Pages\CreateGlAccount;
use App\Filament\Resources\Finance\GlAccounts\Pages\EditGlAccount;
use App\Filament\Resources\Finance\GlAccounts\Pages\ListGlAccounts;
use App\Filament\Resources\Finance\GlAccounts\RelationManagers\GlEntriesRelationManager;
use App\Filament\Resources\Finance\GlAccounts\Schemas\GlAccountForm;
use App\Filament\Resources\Finance\GlAccounts\Tables\GlAccountsTable;
use App\Models\Finance\GlAccount;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class GlAccountResource extends Resource
{
    protected static ?string $model = GlAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

    protected static UnitEnum|string|null $navigationGroup = 'Finance — Setup';

    protected static ?string $navigationLabel = 'Chart of Accounts';

    protected static ?int $navigationSort = 10;

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
        return GlAccountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GlAccountsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            GlEntriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGlAccounts::route('/'),
            'create' => CreateGlAccount::route('/create'),
            'edit' => EditGlAccount::route('/{record}/edit'),
        ];
    }
}
