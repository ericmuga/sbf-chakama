<?php

namespace App\Filament\Resources\ClaimApprovalTemplates;

use App\Filament\Resources\ClaimApprovalTemplates\Pages\CreateClaimApprovalTemplate;
use App\Filament\Resources\ClaimApprovalTemplates\Pages\EditClaimApprovalTemplate;
use App\Filament\Resources\ClaimApprovalTemplates\Pages\ListClaimApprovalTemplates;
use App\Filament\Resources\ClaimApprovalTemplates\RelationManagers\StepsRelationManager;
use App\Filament\Resources\ClaimApprovalTemplates\Schemas\ClaimApprovalTemplateForm;
use App\Filament\Resources\ClaimApprovalTemplates\Tables\ClaimApprovalTemplatesTable;
use App\Models\ClaimApprovalTemplate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ClaimApprovalTemplateResource extends Resource
{
    protected static ?string $model = ClaimApprovalTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static \UnitEnum|string|null $navigationGroup = 'Finance — Expenses & Claims';

    protected static ?int $navigationSort = 40;

    public static function form(Schema $schema): Schema
    {
        return ClaimApprovalTemplateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClaimApprovalTemplatesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            StepsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClaimApprovalTemplates::route('/'),
            'create' => CreateClaimApprovalTemplate::route('/create'),
            'edit' => EditClaimApprovalTemplate::route('/{record}/edit'),
        ];
    }
}
