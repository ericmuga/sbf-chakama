<?php

namespace App\Filament\Resources\Finance\ServicePostingGroups;

use App\Filament\Resources\Finance\ServicePostingGroups\Pages\CreateServicePostingGroup;
use App\Filament\Resources\Finance\ServicePostingGroups\Pages\EditServicePostingGroup;
use App\Filament\Resources\Finance\ServicePostingGroups\Pages\ListServicePostingGroups;
use App\Filament\Resources\Finance\ServicePostingGroups\Schemas\ServicePostingGroupForm;
use App\Filament\Resources\Finance\ServicePostingGroups\Tables\ServicePostingGroupsTable;
use App\Models\Finance\ServicePostingGroup;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class ServicePostingGroupResource extends Resource
{
    protected static ?string $model = ServicePostingGroup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static UnitEnum|string|null $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Service Posting Groups';

    protected static ?int $navigationSort = 115;

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
        return ServicePostingGroupForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ServicePostingGroupsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListServicePostingGroups::route('/'),
            'create' => CreateServicePostingGroup::route('/create'),
            'edit' => EditServicePostingGroup::route('/{record}/edit'),
        ];
    }
}
