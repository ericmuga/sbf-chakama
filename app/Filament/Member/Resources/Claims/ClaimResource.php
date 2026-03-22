<?php

namespace App\Filament\Member\Resources\Claims;

use App\Filament\Member\Resources\Claims\Pages\CreateClaim;
use App\Filament\Member\Resources\Claims\Pages\EditClaim;
use App\Filament\Member\Resources\Claims\Pages\ListClaims;
use App\Filament\Member\Resources\Claims\Pages\ViewClaim;
use App\Filament\Member\Resources\Claims\Schemas\ClaimForm;
use App\Filament\Member\Resources\Claims\Tables\ClaimsTable;
use App\Models\Claim;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ClaimResource extends Resource
{
    protected static ?string $model = Claim::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'My Claims';

    public static function getEloquentQuery(): Builder
    {
        $member = auth()->user()?->member;

        return parent::getEloquentQuery()
            ->when($member, fn ($q) => $q->where('member_id', $member->id))
            ->unless($member, fn ($q) => $q->whereRaw('1 = 0'));
    }

    public static function form(Schema $schema): Schema
    {
        return ClaimForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClaimsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClaims::route('/'),
            'create' => CreateClaim::route('/create'),
            'view' => ViewClaim::route('/{record}'),
            'edit' => EditClaim::route('/{record}/edit'),
        ];
    }
}
