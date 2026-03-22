<?php

namespace App\Filament\Resources\Finance\SalesHeaders;

use App\Filament\Resources\Finance\SalesHeaders\Pages\CreateSalesHeader;
use App\Filament\Resources\Finance\SalesHeaders\Pages\EditSalesHeader;
use App\Filament\Resources\Finance\SalesHeaders\Pages\ListSalesHeaders;
use App\Filament\Resources\Finance\SalesHeaders\Schemas\SalesHeaderForm;
use App\Filament\Resources\Finance\SalesHeaders\Tables\SalesHeadersTable;
use App\Models\Finance\SalesHeader;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class SalesHeaderResource extends Resource
{
    protected static ?string $model = SalesHeader::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static UnitEnum|string|null $navigationGroup = 'Finance — Income & Deposits';

    protected static ?string $modelLabel = 'Sales Document';

    protected static ?string $pluralModelLabel = 'Sales Documents';

    protected static ?string $navigationLabel = 'Member Deposits';

    protected static ?int $navigationSort = 10;

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
        return SalesHeaderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SalesHeadersTable::configure($table);
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
            'index' => ListSalesHeaders::route('/'),
            'create' => CreateSalesHeader::route('/create'),
            'edit' => EditSalesHeader::route('/{record}/edit'),
        ];
    }
}
