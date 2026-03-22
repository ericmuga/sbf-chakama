<?php

namespace App\Filament\Resources\Finance\DirectExpenses;

use App\Filament\Resources\Finance\DirectExpenses\Pages\CreateDirectExpense;
use App\Filament\Resources\Finance\DirectExpenses\Pages\EditDirectExpense;
use App\Filament\Resources\Finance\DirectExpenses\Pages\ListDirectExpenses;
use App\Filament\Resources\Finance\DirectExpenses\Schemas\DirectExpenseForm;
use App\Filament\Resources\Finance\DirectExpenses\Tables\DirectExpensesTable;
use App\Models\Finance\DirectExpense;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class DirectExpenseResource extends Resource
{
    protected static ?string $model = DirectExpense::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static UnitEnum|string|null $navigationGroup = 'Finance — Expenses & Claims';

    protected static ?string $modelLabel = 'Club Expense';

    protected static ?string $pluralModelLabel = 'Club Expenses';

    protected static ?string $navigationLabel = 'Club Expenses';

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
        return DirectExpenseForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DirectExpensesTable::configure($table);
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
            'index' => ListDirectExpenses::route('/'),
            'create' => CreateDirectExpense::route('/create'),
            'edit' => EditDirectExpense::route('/{record}/edit'),
        ];
    }
}
