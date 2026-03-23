<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use App\Models\ProjectAttachment;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class AttachmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'attachments';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('file_name')
            ->columns([
                TextColumn::make('file_name'),
                TextColumn::make('mime_type'),
                TextColumn::make('file_size')
                    ->label('Size')
                    ->state(fn (ProjectAttachment $record): string => $record->fileSizeHuman()),
                TextColumn::make('uploader.name')
                    ->label('Uploaded By'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Action::make('upload')
                    ->label('Upload File')
                    ->schema([
                        FileUpload::make('file_path')
                            ->disk('public')
                            ->directory('project-attachments')
                            ->visibility('public')
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $path = $data['file_path'];
                        $disk = Storage::disk('public');
                        $fullPath = $disk->path($path);
                        $fileName = basename($path);
                        $fileSize = $disk->size($path);
                        $mimeType = $disk->mimeType($path);

                        ProjectAttachment::create([
                            'project_id' => $this->getOwnerRecord()->id,
                            'uploaded_by' => auth()->id(),
                            'file_name' => $fileName,
                            'file_path' => $path,
                            'file_size' => $fileSize,
                            'mime_type' => $mimeType,
                        ]);
                    }),
            ])
            ->recordActions([
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
