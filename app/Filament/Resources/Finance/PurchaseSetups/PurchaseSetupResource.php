<?php

namespace App\Filament\Resources\Finance\PurchaseSetups;

use App\Filament\Resources\Finance\PurchaseSetups\Pages\CreatePurchaseSetup;
use App\Filament\Resources\Finance\PurchaseSetups\Pages\EditPurchaseSetup;
use App\Filament\Resources\Finance\PurchaseSetups\Pages\ListPurchaseSetups;
use App\Filament\Resources\Finance\PurchaseSetups\Schemas\PurchaseSetupForm;
use App\Filament\Resources\Finance\PurchaseSetups\Tables\PurchaseSetupsTable;
use App\Models\Finance\PurchaseSetup;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class PurchaseSetupResource extends Resource
{
    protected static ?string $model = PurchaseSetup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static UnitEnum|string|null $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Purchase Setup';

    protected static ?int $navigationSort = 80;

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
        return PurchaseSetupForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PurchaseSetupsTable::configure($table);
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
            'index' => ListPurchaseSetups::route('/'),
            'create' => CreatePurchaseSetup::route('/create'),
            'edit' => EditPurchaseSetup::route('/{record}/edit'),
        ];
    }
}
