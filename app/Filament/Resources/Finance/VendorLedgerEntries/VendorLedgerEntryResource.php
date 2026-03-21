<?php

namespace App\Filament\Resources\Finance\VendorLedgerEntries;

use App\Filament\Resources\Finance\VendorLedgerEntries\Pages\ListVendorLedgerEntries;
use App\Filament\Resources\Finance\VendorLedgerEntries\Schemas\VendorLedgerEntryForm;
use App\Filament\Resources\Finance\VendorLedgerEntries\Tables\VendorLedgerEntriesTable;
use App\Models\Finance\VendorLedgerEntry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class VendorLedgerEntryResource extends Resource
{
    protected static ?string $model = VendorLedgerEntry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static UnitEnum|string|null $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Vendor Ledger';

    protected static ?int $navigationSort = 150;

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
        return VendorLedgerEntryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VendorLedgerEntriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVendorLedgerEntries::route('/'),
        ];
    }
}
