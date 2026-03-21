<?php

namespace App\Filament\Resources\Finance\CustomerPostingGroups;

use App\Filament\Resources\Finance\CustomerPostingGroups\Pages\CreateCustomerPostingGroup;
use App\Filament\Resources\Finance\CustomerPostingGroups\Pages\EditCustomerPostingGroup;
use App\Filament\Resources\Finance\CustomerPostingGroups\Pages\ListCustomerPostingGroups;
use App\Filament\Resources\Finance\CustomerPostingGroups\Schemas\CustomerPostingGroupForm;
use App\Filament\Resources\Finance\CustomerPostingGroups\Tables\CustomerPostingGroupsTable;
use App\Models\Finance\CustomerPostingGroup;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class CustomerPostingGroupResource extends Resource
{
    protected static ?string $model = CustomerPostingGroup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static UnitEnum|string|null $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Customer Posting Groups';

    protected static ?int $navigationSort = 110;

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
        return CustomerPostingGroupForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomerPostingGroupsTable::configure($table);
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
            'index' => ListCustomerPostingGroups::route('/'),
            'create' => CreateCustomerPostingGroup::route('/create'),
            'edit' => EditCustomerPostingGroup::route('/{record}/edit'),
        ];
    }
}
