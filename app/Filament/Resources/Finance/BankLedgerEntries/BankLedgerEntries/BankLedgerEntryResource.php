<?php

namespace App\Filament\Resources\Finance\BankLedgerEntries\BankLedgerEntries;

use App\Filament\Resources\Finance\BankLedgerEntries\BankLedgerEntries\Pages\CreateBankLedgerEntry;
use App\Filament\Resources\Finance\BankLedgerEntries\BankLedgerEntries\Pages\EditBankLedgerEntry;
use App\Filament\Resources\Finance\BankLedgerEntries\BankLedgerEntries\Pages\ListBankLedgerEntries;
use App\Filament\Resources\Finance\BankLedgerEntries\BankLedgerEntries\Schemas\BankLedgerEntryForm;
use App\Filament\Resources\Finance\BankLedgerEntries\BankLedgerEntries\Tables\BankLedgerEntriesTable;
use App\Models\Finance\BankLedgerEntries\BankLedgerEntry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BankLedgerEntryResource extends Resource
{
    protected static ?string $model = BankLedgerEntry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return BankLedgerEntryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BankLedgerEntriesTable::configure($table);
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
            'index' => ListBankLedgerEntries::route('/'),
            'create' => CreateBankLedgerEntry::route('/create'),
            'edit' => EditBankLedgerEntry::route('/{record}/edit'),
        ];
    }
}
