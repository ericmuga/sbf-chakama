<?php

namespace App\Filament\Resources\Members\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('document_type')
                    ->label('Document Type')
                    ->options([
                        'national_id' => 'National ID',
                        'pin' => 'PIN Certificate',
                        'passport' => 'Passport',
                        'birth_cert' => 'Birth Certificate',
                    ]),
                TextInput::make('document_no')
                    ->label('Document Number')
                    ->maxLength(100),
                FileUpload::make('file_path')
                    ->label('File')
                    ->disk('local')
                    ->directory('member-documents')
                    ->maxSize(5120)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('document_type')
            ->columns([
                TextColumn::make('document_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'national_id' => 'National ID',
                        'pin' => 'PIN Certificate',
                        'passport' => 'Passport',
                        'birth_cert' => 'Birth Certificate',
                        default => $state ?? '—',
                    }),
                TextColumn::make('document_no')
                    ->label('Document No')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('original_name')
                    ->label('File')
                    ->placeholder('No file'),
                TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
