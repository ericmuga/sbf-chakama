<?php

namespace App\Filament\Resources\Finance\CustomerLedgerEntries;

use App\Filament\Resources\Finance\CustomerLedgerEntries\Pages\EditCustomerLedgerEntry;
use App\Filament\Resources\Finance\CustomerLedgerEntries\Pages\ListCustomerLedgerEntries;
use App\Filament\Resources\Finance\CustomerLedgerEntries\Schemas\CustomerLedgerEntryForm;
use App\Filament\Resources\Finance\CustomerLedgerEntries\Tables\CustomerLedgerEntriesTable;
use App\Models\Finance\CustomerLedgerEntry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class CustomerLedgerEntryResource extends Resource
{
    protected static ?string $model = CustomerLedgerEntry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static UnitEnum|string|null $navigationGroup = 'Finance — Ledgers';

    protected static ?string $navigationLabel = 'Member Ledger';

    protected static ?string $modelLabel = 'Member Ledger Entry';

    protected static ?string $pluralModelLabel = 'Member Ledger';

    protected static ?int $navigationSort = 10;

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
        return (auth()->user()?->isAdmin() ?? false) && $record->is_open;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return CustomerLedgerEntryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomerLedgerEntriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomerLedgerEntries::route('/'),
            'edit' => EditCustomerLedgerEntry::route('/{record}/edit'),
        ];
    }
}
