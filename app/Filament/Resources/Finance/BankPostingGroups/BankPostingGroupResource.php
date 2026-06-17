<?php

namespace App\Filament\Resources\Finance\BankPostingGroups;

use App\Filament\Resources\Finance\BankPostingGroups\Pages\CreateBankPostingGroup;
use App\Filament\Resources\Finance\BankPostingGroups\Pages\EditBankPostingGroup;
use App\Filament\Resources\Finance\BankPostingGroups\Pages\ListBankPostingGroups;
use App\Filament\Resources\Finance\BankPostingGroups\RelationManagers\BankAccountsRelationManager;
use App\Filament\Resources\Finance\BankPostingGroups\Schemas\BankPostingGroupForm;
use App\Filament\Resources\Finance\BankPostingGroups\Tables\BankPostingGroupsTable;
use App\Models\Finance\BankPostingGroup;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class BankPostingGroupResource extends Resource
{
    protected static ?string $model = BankPostingGroup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static UnitEnum|string|null $navigationGroup = 'Finance — Setup';

    protected static ?string $navigationLabel = 'Bank Posting Groups';

    protected static ?int $navigationSort = 70;

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
        if (! (auth()->user()?->isAdmin() ?? false)) {
            return false;
        }

        // Prevent deletion while bank accounts are still linked to this group.
        return ! $record->bankAccounts()->exists();
    }

    public static function form(Schema $schema): Schema
    {
        return BankPostingGroupForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BankPostingGroupsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            BankAccountsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBankPostingGroups::route('/'),
            'create' => CreateBankPostingGroup::route('/create'),
            'edit' => EditBankPostingGroup::route('/{record}/edit'),
        ];
    }
}
