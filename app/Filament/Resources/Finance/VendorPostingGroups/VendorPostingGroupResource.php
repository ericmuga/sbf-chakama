<?php

namespace App\Filament\Resources\Finance\VendorPostingGroups;

use App\Filament\Resources\Finance\VendorPostingGroups\Pages\CreateVendorPostingGroup;
use App\Filament\Resources\Finance\VendorPostingGroups\Pages\EditVendorPostingGroup;
use App\Filament\Resources\Finance\VendorPostingGroups\Pages\ListVendorPostingGroups;
use App\Filament\Resources\Finance\VendorPostingGroups\Schemas\VendorPostingGroupForm;
use App\Filament\Resources\Finance\VendorPostingGroups\Tables\VendorPostingGroupsTable;
use App\Models\Finance\VendorPostingGroup;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class VendorPostingGroupResource extends Resource
{
    protected static ?string $model = VendorPostingGroup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static UnitEnum|string|null $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Vendor Posting Groups';

    protected static ?int $navigationSort = 120;

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
        return VendorPostingGroupForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VendorPostingGroupsTable::configure($table);
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
            'index' => ListVendorPostingGroups::route('/'),
            'create' => CreateVendorPostingGroup::route('/create'),
            'edit' => EditVendorPostingGroup::route('/{record}/edit'),
        ];
    }
}
