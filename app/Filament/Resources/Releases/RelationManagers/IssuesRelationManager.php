<?php

namespace App\Filament\Resources\Releases\RelationManagers;

use App\Filament\Resources\Issues\Schemas\IssueForm;
use Filament\Actions\AssociateAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class IssuesRelationManager extends RelationManager
{
    protected static string $relationship = 'issues';

    protected static ?string $title = 'Issues in This Release';

    public function form(Schema $schema): Schema
    {
        return IssueForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('title')->label('Issue')->wrap()->searchable(),
                TextColumn::make('portal_type')->label('Portal')->badge(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('resource')->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                CreateAction::make(),
                AssociateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DissociateAction::make(),
            ]);
    }
}
