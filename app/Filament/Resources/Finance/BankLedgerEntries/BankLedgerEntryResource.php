<?php

namespace App\Filament\Resources\Finance\BankLedgerEntries;

use App\Filament\Resources\Finance\BankLedgerEntries\Pages\ListBankLedgerEntries;
use App\Filament\Resources\Finance\BankLedgerEntries\Tables\BankLedgerEntriesTable;
use App\Models\Finance\BankLedgerEntry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class BankLedgerEntryResource extends Resource
{
    protected static ?string $model = BankLedgerEntry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static UnitEnum|string|null $navigationGroup = 'Finance — Ledgers';

    protected static ?string $navigationLabel = 'Bank Ledger Entries';

    protected static ?int $navigationSort = 25;

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return BankLedgerEntriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBankLedgerEntries::route('/'),
        ];
    }
}
