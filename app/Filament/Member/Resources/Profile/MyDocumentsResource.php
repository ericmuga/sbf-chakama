<?php

namespace App\Filament\Member\Resources\Profile;

use App\Filament\Member\Resources\Profile\Pages\ListMyDocuments;
use App\Models\Document;
use App\Models\Member;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MyDocumentsResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'My Documents';

    protected static \UnitEnum|string|null $navigationGroup = 'My Profile';

    protected static ?int $navigationSort = 30;

    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        $member = auth()->user()?->member;

        return ($member?->is_chakama || $member?->is_sbf) ?? false;
    }

    public static function canViewAny(): bool
    {
        $member = auth()->user()?->member;

        return ($member?->is_chakama || $member?->is_sbf) ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        $member = auth()->user()?->member;

        return parent::getEloquentQuery()
            ->where('documentable_type', Member::class)
            ->when($member, fn ($q) => $q->where('documentable_id', $member->id))
            ->unless($member, fn ($q) => $q->whereRaw('1 = 0'));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
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

    public static function table(Table $table): Table
    {
        return $table
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
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMyDocuments::route('/'),
        ];
    }
}
