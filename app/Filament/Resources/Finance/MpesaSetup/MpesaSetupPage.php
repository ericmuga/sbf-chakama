<?php

namespace App\Filament\Resources\Finance\MpesaSetup;

use App\Models\Finance\MpesaSetup;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class MpesaSetupPage extends Page
{
    protected string $view = 'filament.resources.finance.mpesa-setup.page';

    protected static \BackedEnum|string|null $navigationIcon = Heroicon::OutlinedDevicePhoneMobile;

    protected static UnitEnum|string|null $navigationGroup = 'Finance — Setup';

    protected static ?string $navigationLabel = 'M-Pesa Setup';

    protected static ?string $title = 'M-Pesa / Daraja Setup';

    protected static ?int $navigationSort = 120;

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(): void
    {
        $setup = MpesaSetup::first() ?? new MpesaSetup;
        $this->form->fill($setup->attributesToArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Environment')
                    ->description('Set to "Local (Test)" to use simulated payments without real Safaricom credentials.')
                    ->schema([
                        Select::make('mpesa_env')
                            ->label('M-Pesa Environment')
                            ->options([
                                'local' => '🧪  Local — Test mode (no real Safaricom calls)',
                                'sandbox' => '⚙️  Sandbox — Safaricom developer sandbox',
                                'production' => '🚀  Production — Live payments',
                            ])
                            ->required()
                            ->default('local'),

                        Select::make('transaction_type')
                            ->label('Transaction Type')
                            ->options([
                                'CustomerPayBillOnline' => 'Pay Bill',
                                'CustomerBuyGoodsOnline' => 'Buy Goods (Till)',
                            ])
                            ->required()
                            ->default('CustomerPayBillOnline'),
                    ])
                    ->columns(2),

                Section::make('Business Details')
                    ->description('Safaricom Daraja API credentials from developer.safaricom.co.ke')
                    ->schema([
                        TextInput::make('shortcode')
                            ->label('Business Short Code (Paybill / Till)')
                            ->placeholder('174379')
                            ->nullable(),

                        TextInput::make('callback_url')
                            ->label('Callback URL')
                            ->placeholder(config('app.url'))
                            ->helperText('Must be a publicly reachable HTTPS URL. Safaricom posts payment results here.')
                            ->url()
                            ->nullable(),
                    ])
                    ->columns(2),

                Section::make('API Credentials')
                    ->description('Keep these secret. They are encrypted in the browser.')
                    ->schema([
                        TextInput::make('consumer_key')
                            ->label('Consumer Key')
                            ->password()
                            ->revealable()
                            ->nullable(),

                        TextInput::make('consumer_secret')
                            ->label('Consumer Secret')
                            ->password()
                            ->revealable()
                            ->nullable(),

                        TextInput::make('passkey')
                            ->label('Lipa Na M-Pesa Online Pass Key')
                            ->password()
                            ->revealable()
                            ->columnSpanFull()
                            ->nullable(),
                    ])
                    ->columns(2),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Settings')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->action('save'),
        ];
    }

    public function save(): void
    {
        $state = $this->form->getState();

        MpesaSetup::updateOrCreate(['id' => 1], $state);

        Notification::make()
            ->success()
            ->title('M-Pesa settings saved')
            ->send();
    }
}
