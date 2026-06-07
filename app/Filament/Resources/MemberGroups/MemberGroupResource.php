<?php

namespace App\Filament\Resources\MemberGroups;

use App\Enums\MemberGroupMode;
use App\Filament\Resources\MemberGroups\Pages\ManageMemberGroups;
use App\Models\Member;
use App\Models\MemberGroup;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class MemberGroupResource extends Resource
{
    protected static ?string $model = MemberGroup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static \UnitEnum|string|null $navigationGroup = 'Chakama — Shares';

    protected static ?string $navigationLabel = 'Member Lists';

    protected static ?string $modelLabel = 'Member List';

    protected static ?string $pluralModelLabel = 'Member Lists';

    protected static ?int $navigationSort = 4;

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
        return $schema
            ->components([
                Section::make('Details')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->rows(2)
                            ->maxLength(500)
                            ->columnSpanFull(),
                        Select::make('mode')
                            ->options(MemberGroupMode::class)
                            ->default(MemberGroupMode::Include->value)
                            ->required()
                            ->live()
                            ->helperText(fn (Get $get): string => match ($get('mode')) {
                                MemberGroupMode::AllExcept->value => 'List will include every active Chakama member except those ticked below.',
                                default => 'List will include only the members ticked below.',
                            }),
                        Toggle::make('is_active')
                            ->default(true)
                            ->inline(false),
                    ]),
                Section::make(fn (Get $get): string => match ($get('mode')) {
                    MemberGroupMode::AllExcept->value => 'Members to Exclude',
                    default => 'Members to Include',
                })
                    ->schema([
                        CheckboxList::make('members')
                            ->relationship(name: 'members', titleAttribute: 'name')
                            ->options(
                                fn () => Member::query()
                                    ->where('is_chakama', true)
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(fn (Member $m): array => [$m->id => "{$m->name} ({$m->no})"])
                            )
                            ->searchable()
                            ->bulkToggleable()
                            ->columns(2)
                            ->hiddenLabel(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('members'))
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('mode')
                    ->badge()
                    ->formatStateUsing(fn (MemberGroupMode $state): string => $state->getLabel()),
                TextColumn::make('members_count')
                    ->label('Members Listed')
                    ->numeric()
                    ->alignCenter(),
                TextColumn::make('effective_count')
                    ->label('Effective Members')
                    ->state(fn (MemberGroup $record): int => $record->resolveMemberIds()->count())
                    ->alignCenter(),
                TextColumn::make('description')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('creator.name')
                    ->label('Created By')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMemberGroups::route('/'),
        ];
    }
}
