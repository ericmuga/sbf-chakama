<?php

namespace App\Filament\Resources\Finance\CashReceipts;

use App\Filament\Resources\Finance\CashReceipts\Pages\CreateCashReceipt;
use App\Filament\Resources\Finance\CashReceipts\Pages\EditCashReceipt;
use App\Filament\Resources\Finance\CashReceipts\Pages\ListCashReceipts;
use App\Filament\Resources\Finance\CashReceipts\Schemas\CashReceiptForm;
use App\Filament\Resources\Finance\CashReceipts\Tables\CashReceiptsTable;
use App\Models\Finance\CashReceipt;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class CashReceiptResource extends Resource
{
    protected static ?string $model = CashReceipt::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static UnitEnum|string|null $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Receipts';

    protected static ?int $navigationSort = 50;

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
        return CashReceiptForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CashReceiptsTable::configure($table);
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
            'index' => ListCashReceipts::route('/'),
            'create' => CreateCashReceipt::route('/create'),
            'edit' => EditCashReceipt::route('/{record}/edit'),
        ];
    }
}
