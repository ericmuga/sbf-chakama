<?php

namespace App\Filament\Resources;

use App\Enums\EntityDimension;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\Member;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static \UnitEnum|string|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Account')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                            ->maxLength(255),
                        Toggle::make('is_admin')
                            ->label('Admin user')
                            ->inline(false)
                            ->live(),
                        Select::make('entity')
                            ->label('Admin module')
                            ->options(EntityDimension::class)
                            ->placeholder('— SBF (default) —')
                            ->helperText('Controls which admin panel this admin can access. Leave blank for SBF access.')
                            ->visible(fn (Get $get): bool => (bool) $get('is_admin')),
                    ])
                    ->columns(2),
                Toggle::make('has_member_profile')
                    ->label('Attach member profile')
                    ->helperText('Enable to create or update a member profile linked to this user account.')
                    ->live()
                    ->inline(false),
                Section::make('Member profile')
                    ->schema([
                        Select::make('link_member_id')
                            ->label('Link to existing member')
                            ->placeholder('— leave blank to create a new member profile below —')
                            ->helperText('Select an existing member to link to this user account. If selected, the fields below are ignored.')
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search): array {
                                return Member::query()
                                    ->whereNull('user_id')
                                    ->where(function ($q) use ($search): void {
                                        $q->where('no', 'like', "%{$search}%")
                                            ->orWhere('name', 'like', "%{$search}%")
                                            ->orWhere('identity_no', 'like', "%{$search}%");
                                    })
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(fn (Member $m) => [$m->id => "{$m->no} — {$m->name}"])
                                    ->toArray();
                            })
                            ->getOptionLabelUsing(function ($value): ?string {
                                $member = Member::find($value);

                                return $member ? "{$member->no} — {$member->name}" : null;
                            })
                            ->live()
                            ->columnSpanFull(),
                        TextInput::make('member_no')
                            ->label('Member number')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto-generated on save')
                            ->visible(fn (Get $get): bool => ! filled($get('link_member_id'))),
                        Select::make('identity_type')
                            ->label('Identity Type')
                            ->options([
                                'national_id' => 'National ID',
                                'passport_no' => 'Passport No',
                                'birth_cert_no' => 'Birth Certificate No',
                                'driving_licence_no' => 'Driving Licence No',
                                'pin_no' => 'PIN No',
                            ])
                            ->visible(fn (Get $get): bool => ! filled($get('link_member_id'))),
                        TextInput::make('identity_no')
                            ->label('Identity Number')
                            ->maxLength(50)
                            ->visible(fn (Get $get): bool => ! filled($get('link_member_id'))),
                        TextInput::make('member_phone')
                            ->label('Phone')
                            ->tel()
                            ->maxLength(20)
                            ->visible(fn (Get $get): bool => ! filled($get('link_member_id'))),
                        Select::make('member_status')
                            ->label('Status')
                            ->options([
                                'active' => 'Active',
                                'lapsed' => 'Lapsed',
                                'suspended' => 'Suspended',
                            ])
                            ->visible(fn (Get $get): bool => ! filled($get('link_member_id'))),
                        Toggle::make('is_chakama')
                            ->label('Chakama member')
                            ->inline(false)
                            ->visible(fn (Get $get): bool => ! filled($get('link_member_id'))),
                        Toggle::make('is_sbf')
                            ->label('SBF member')
                            ->inline(false)
                            ->visible(fn (Get $get): bool => ! filled($get('link_member_id'))),
                        Toggle::make('exclude_from_billing')
                            ->label('Exclude from billing')
                            ->inline(false)
                            ->visible(fn (Get $get): bool => ! filled($get('link_member_id'))),
                        TextInput::make('customer_no')
                            ->maxLength(20)
                            ->visible(fn (Get $get): bool => ! filled($get('link_member_id'))),
                        TextInput::make('vendor_no')
                            ->maxLength(20)
                            ->visible(fn (Get $get): bool => ! filled($get('link_member_id'))),
                    ])
                    ->visible(fn (Get $get): bool => (bool) $get('has_member_profile'))
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('member.no')
                    ->label('Member no')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('member.member_status')
                    ->label('Status')
                    ->badge(),
                IconColumn::make('is_admin')
                    ->label('Admin')
                    ->boolean(),
                TextColumn::make('entity')
                    ->label('Module')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->label() ?? 'SBF (default)')
                    ->visible(fn (): bool => true),
                IconColumn::make('member.is_chakama')
                    ->label('Chakama')
                    ->boolean(),
                IconColumn::make('member.is_sbf')
                    ->label('SBF')
                    ->boolean(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
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
}
