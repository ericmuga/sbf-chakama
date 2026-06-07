<?php

namespace App\Filament\Resources\Members;

use App\Filament\Resources\Members\Pages\CreateMember;
use App\Filament\Resources\Members\Pages\EditMember;
use App\Filament\Resources\Members\Pages\ListMembers;
use App\Filament\Resources\Members\RelationManagers\DependantsRelationManager;
use App\Filament\Resources\Members\RelationManagers\DocumentsRelationManager;
use App\Filament\Resources\Members\RelationManagers\NextOfKinRelationManager;
use App\Filament\Resources\Members\Schemas\MemberForm;
use App\Filament\Resources\Members\Tables\MembersTable;
use App\Models\Finance\Customer;
use App\Models\Finance\CustomerLedgerEntry;
use App\Models\Member;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MemberResource extends Resource
{
    protected static ?string $model = Member::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static \UnitEnum|string|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return MemberForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MembersTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->members();
    }

    public static function getRelations(): array
    {
        return [
            DependantsRelationManager::class,
            NextOfKinRelationManager::class,
            DocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMembers::route('/'),
            'create' => CreateMember::route('/create'),
            'edit' => EditMember::route('/{record}/edit'),
        ];
    }

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
        if (! (auth()->user()?->isAdmin() ?? false)) {
            return false;
        }

        // Prevent an admin from deleting their own member record
        if (auth()->user()?->member?->id === $record->id) {
            return false;
        }

        // Prevent deletion if the member has any ledger entries
        if ($record->customer_no) {
            $customer = Customer::where('no', $record->customer_no)->first();
            if ($customer && CustomerLedgerEntry::where('customer_id', $customer->id)->exists()) {
                return false;
            }
        }

        return true;
    }
}
