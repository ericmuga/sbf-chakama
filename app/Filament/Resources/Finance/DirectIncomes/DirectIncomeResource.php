<?php

namespace App\Filament\Resources\Finance\DirectIncomes;

use App\Filament\Resources\Finance\DirectIncomes\Pages\CreateDirectIncome;
use App\Filament\Resources\Finance\DirectIncomes\Pages\EditDirectIncome;
use App\Filament\Resources\Finance\DirectIncomes\Pages\ListDirectIncomes;
use App\Filament\Resources\Finance\DirectIncomes\Schemas\DirectIncomeForm;
use App\Filament\Resources\Finance\DirectIncomes\Tables\DirectIncomesTable;
use App\Models\Finance\DirectIncome;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class DirectIncomeResource extends Resource
{
    protected static ?string $model = DirectIncome::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowTrendingUp;

    protected static UnitEnum|string|null $navigationGroup = 'Finance — Income & Deposits';

    protected static ?string $modelLabel = 'Club Income';

    protected static ?string $pluralModelLabel = 'Club Incomes';

    protected static ?string $navigationLabel = 'Club Incomes';

    protected static ?int $navigationSort = 25;

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
        return DirectIncomeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DirectIncomesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\GlEntriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDirectIncomes::route('/'),
            'create' => CreateDirectIncome::route('/create'),
            'edit' => EditDirectIncome::route('/{record}/edit'),
        ];
    }
}
