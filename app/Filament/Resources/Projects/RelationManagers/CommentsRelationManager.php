<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use App\Models\ProjectComment;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Textarea::make('body')
                ->label('Comment')
                ->required()
                ->rows(3),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('body')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('user.name')
                    ->label('Author')
                    ->sortable(),
                TextColumn::make('body')
                    ->label('Comment')
                    ->limit(100)
                    ->wrap(),
                TextColumn::make('created_at')
                    ->label('Posted')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([])
            ->headerActions([
                Action::make('add_comment')
                    ->label('Add Comment')
                    ->schema([
                        Textarea::make('body')
                            ->label('Comment')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (array $data): void {
                        ProjectComment::create([
                            'project_id' => $this->getOwnerRecord()->id,
                            'user_id' => auth()->id(),
                            'body' => $data['body'],
                        ]);
                    }),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->visible(fn (ProjectComment $record): bool => $record->user_id === auth()->id() || (auth()->user()?->isAdmin() ?? false)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
