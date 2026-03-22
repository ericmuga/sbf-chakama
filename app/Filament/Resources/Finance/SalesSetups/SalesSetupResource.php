<?php

namespace App\Filament\Resources\Finance\SalesSetups;

use App\Filament\Resources\Finance\SalesSetups\Pages\CreateSalesSetup;
use App\Filament\Resources\Finance\SalesSetups\Pages\EditSalesSetup;
use App\Filament\Resources\Finance\SalesSetups\Pages\ListSalesSetups;
use App\Filament\Resources\Finance\SalesSetups\Schemas\SalesSetupForm;
use App\Filament\Resources\Finance\SalesSetups\Tables\SalesSetupsTable;
use App\Models\Finance\SalesSetup;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class SalesSetupResource extends Resource
{
    protected static ?string $model = SalesSetup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static UnitEnum|string|null $navigationGroup = 'Finance — Setup';

    protected static ?string $navigationLabel = 'Sales Setup';

    protected static ?int $navigationSort = 90;

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
        return SalesSetupForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SalesSetupsTable::configure($table);
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
            'index' => ListSalesSetups::route('/'),
            'create' => CreateSalesSetup::route('/create'),
            'edit' => EditSalesSetup::route('/{record}/edit'),
        ];
    }
}
