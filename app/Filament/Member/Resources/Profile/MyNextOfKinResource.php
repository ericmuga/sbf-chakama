<?php

namespace App\Filament\Member\Resources\Profile;

use App\Filament\Member\Resources\Profile\Pages\ListMyNextOfKin;
use App\Models\NextOfKin;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MyNextOfKinResource extends Resource
{
    protected static ?string $model = NextOfKin::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'My Next of Kin';

    protected static \UnitEnum|string|null $navigationGroup = 'My Profile';

    protected static ?int $navigationSort = 21;

    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        return auth()->user()?->member?->is_chakama ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->member?->is_chakama ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        $member = auth()->user()?->member;

        return parent::getEloquentQuery()
            ->when($member, fn ($q) => $q->where('member_id', $member->id))
            ->unless($member, fn ($q) => $q->whereRaw('1 = 0'));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),
            Select::make('identity_type')
                ->label('Identity Type')
                ->options([
                    'national_id' => 'National ID',
                    'passport_no' => 'Passport No',
                    'birth_cert_no' => 'Birth Certificate No',
                    'driving_licence_no' => 'Driving Licence No',
                    'pin_no' => 'PIN No',
                ])
                ->required(),
            TextInput::make('identity_no')
                ->label('Identity Number')
                ->required()
                ->maxLength(50),
            TextInput::make('phone')
                ->tel()
                ->maxLength(20),
            TextInput::make('email')
                ->email()
                ->maxLength(255),
            DatePicker::make('date_of_birth')
                ->label('Date of Birth'),
            TextInput::make('relationship')
                ->maxLength(100),
            Select::make('contact_preference')
                ->label('Contact Preference')
                ->options([
                    'phone' => 'Phone',
                    'email' => 'Email',
                    'both' => 'Both',
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('relationship')
                    ->searchable(),
                TextColumn::make('identity_type')
                    ->label('ID Type')
                    ->badge(),
                TextColumn::make('identity_no')
                    ->label('ID No')
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('contact_preference')
                    ->label('Contact Preference')
                    ->badge(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('id', 'asc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMyNextOfKin::route('/'),
        ];
    }
}
