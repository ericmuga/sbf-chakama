<?php

namespace App\Filament\Resources\Finance\PurchaseHeaders;

use App\Filament\Resources\Finance\PurchaseHeaders\Pages\CreatePurchaseHeader;
use App\Filament\Resources\Finance\PurchaseHeaders\Pages\EditPurchaseHeader;
use App\Filament\Resources\Finance\PurchaseHeaders\Pages\ListPurchaseHeaders;
use App\Filament\Resources\Finance\PurchaseHeaders\Schemas\PurchaseHeaderForm;
use App\Filament\Resources\Finance\PurchaseHeaders\Tables\PurchaseHeadersTable;
use App\Models\Finance\PurchaseHeader;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class PurchaseHeaderResource extends Resource
{
    protected static ?string $model = PurchaseHeader::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static UnitEnum|string|null $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Purchases';

    protected static ?int $navigationSort = 40;

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
        return PurchaseHeaderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PurchaseHeadersTable::configure($table);
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
            'index' => ListPurchaseHeaders::route('/'),
            'create' => CreatePurchaseHeader::route('/create'),
            'edit' => EditPurchaseHeader::route('/{record}/edit'),
        ];
    }
}
