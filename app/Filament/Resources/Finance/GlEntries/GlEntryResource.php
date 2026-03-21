<?php

namespace App\Filament\Resources\Finance\GlEntries;

use App\Filament\Resources\Finance\GlEntries\Pages\ListGlEntries;
use App\Filament\Resources\Finance\GlEntries\Schemas\GlEntryForm;
use App\Filament\Resources\Finance\GlEntries\Tables\GlEntriesTable;
use App\Models\Finance\GlEntry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class GlEntryResource extends Resource
{
    protected static ?string $model = GlEntry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static UnitEnum|string|null $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'G/L Entries';

    protected static ?int $navigationSort = 160;

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
        return GlEntryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GlEntriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGlEntries::route('/'),
        ];
    }
}
