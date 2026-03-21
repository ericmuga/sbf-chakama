<?php

namespace App\Filament\Resources\Finance\GeneralPostingSetups;

use App\Filament\Resources\Finance\GeneralPostingSetups\Pages\CreateGeneralPostingSetup;
use App\Filament\Resources\Finance\GeneralPostingSetups\Pages\EditGeneralPostingSetup;
use App\Filament\Resources\Finance\GeneralPostingSetups\Pages\ListGeneralPostingSetups;
use App\Filament\Resources\Finance\GeneralPostingSetups\Schemas\GeneralPostingSetupForm;
use App\Filament\Resources\Finance\GeneralPostingSetups\Tables\GeneralPostingSetupsTable;
use App\Models\Finance\GeneralPostingSetup;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class GeneralPostingSetupResource extends Resource
{
    protected static ?string $model = GeneralPostingSetup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static UnitEnum|string|null $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'General Posting Setup';

    protected static ?int $navigationSort = 130;

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
        return GeneralPostingSetupForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GeneralPostingSetupsTable::configure($table);
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
            'index' => ListGeneralPostingSetups::route('/'),
            'create' => CreateGeneralPostingSetup::route('/create'),
            'edit' => EditGeneralPostingSetup::route('/{record}/edit'),
        ];
    }
}
