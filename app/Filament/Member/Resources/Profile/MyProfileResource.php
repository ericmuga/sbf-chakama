<?php

namespace App\Filament\Member\Resources\Profile;

use App\Filament\Member\Resources\Profile\Pages\EditMyProfile;
use App\Models\Member;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class MyProfileResource extends Resource
{
    protected static ?string $model = Member::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUser;

    protected static ?string $navigationLabel = 'My Profile';

    protected static \UnitEnum|string|null $navigationGroup = 'My Profile';

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'my-profile';

    public static function getEloquentQuery(): Builder
    {
        $member = auth()->user()?->member;

        return parent::getEloquentQuery()
            ->when($member, fn ($q) => $q->where('id', $member->id))
            ->unless($member, fn ($q) => $q->whereRaw('1 = 0'));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Personal Details')
                ->schema([
                    TextInput::make('name')
                        ->label('Full Name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('email')
                        ->email()
                        ->maxLength(255),
                    TextInput::make('phone')
                        ->tel()
                        ->maxLength(20),
                    DatePicker::make('date_of_birth')
                        ->label('Date of Birth'),
                ])
                ->columns(2),
            Section::make('Identity')
                ->schema([
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
                ])
                ->columns(2),
            Section::make('Payment Details')
                ->schema([
                    TextInput::make('mpesa_phone')
                        ->label('M-Pesa Phone')
                        ->tel()
                        ->maxLength(20),
                    Select::make('preferred_payment_method')
                        ->label('Preferred Payment Method')
                        ->options([
                            'mpesa' => 'M-PESA',
                            'bank_transfer' => 'Bank Transfer',
                            'cheque' => 'Cheque',
                        ]),
                    TextInput::make('bank_name')
                        ->label('Bank Name')
                        ->maxLength(255),
                    TextInput::make('bank_branch')
                        ->label('Bank Branch')
                        ->maxLength(255),
                    TextInput::make('bank_account_name')
                        ->label('Account Name')
                        ->maxLength(255),
                    TextInput::make('bank_account_no')
                        ->label('Account Number')
                        ->maxLength(50),
                ])
                ->columns(2),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => EditMyProfile::route('/'),
        ];
    }
}
