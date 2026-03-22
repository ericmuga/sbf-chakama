<?php

namespace App\Filament\Resources\Finance\NumberSeries;

use App\Filament\Resources\Finance\NumberSeries\Pages\CreateNumberSeries;
use App\Filament\Resources\Finance\NumberSeries\Pages\EditNumberSeries;
use App\Filament\Resources\Finance\NumberSeries\Pages\ListNumberSeries;
use App\Filament\Resources\Finance\NumberSeries\Schemas\NumberSeriesForm;
use App\Filament\Resources\Finance\NumberSeries\Tables\NumberSeriesTable;
use App\Models\Finance\NumberSeries;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class NumberSeriesResource extends Resource
{
    protected static ?string $model = NumberSeries::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHashtag;

    protected static UnitEnum|string|null $navigationGroup = 'Finance — Setup';

    protected static ?string $navigationLabel = 'Number Series';

    protected static ?int $navigationSort = 20;

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
        return NumberSeriesForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NumberSeriesTable::configure($table);
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
            'index' => ListNumberSeries::route('/'),
            'create' => CreateNumberSeries::route('/create'),
            'edit' => EditNumberSeries::route('/{record}/edit'),
        ];
    }
}
