<?php

namespace App\Filament\Resources\Finance\ScheduledInvoices;

use App\Filament\Resources\Finance\ScheduledInvoices\Pages\CreateScheduledInvoice;
use App\Filament\Resources\Finance\ScheduledInvoices\Pages\EditScheduledInvoice;
use App\Filament\Resources\Finance\ScheduledInvoices\Pages\ListScheduledInvoices;
use App\Jobs\ProcessScheduledInvoiceJob;
use App\Models\Finance\ScheduledInvoice;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class ScheduledInvoiceResource extends Resource
{
    protected static ?string $model = ScheduledInvoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static UnitEnum|string|null $navigationGroup = 'Finance — Income & Deposits';

    protected static ?string $navigationLabel = 'Scheduled Invoices';

    protected static ?int $navigationSort = 15;

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
        return auth()->user()?->isAdmin() && $record->status === 'draft';
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->isAdmin() && $record->status === 'draft';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Invoice Details')->schema([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Textarea::make('description')
                    ->rows(2)
                    ->columnSpanFull(),
                Select::make('service_id')
                    ->label('Service')
                    ->relationship('service', 'description')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('customer_posting_group_id')
                    ->label('Customer Posting Group')
                    ->relationship('customerPostingGroup', 'description')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('amount')
                    ->label('Amount per Member (KES)')
                    ->numeric()
                    ->prefix('KES')
                    ->required()
                    ->minValue(0.01),
                DatePicker::make('scheduled_date')
                    ->label('Invoice / Posting Date')
                    ->default(today())
                    ->required(),
                DatePicker::make('due_date')
                    ->label('Due Date')
                    ->nullable(),
            ])->columns(2),

            Section::make('Notifications')->schema([
                Toggle::make('notify_members')
                    ->label('Send in-app notification to members')
                    ->default(true)
                    ->inline(false),
                Toggle::make('send_email')
                    ->label('Also send email notification')
                    ->helperText('Requires valid mail configuration.')
                    ->default(false)
                    ->inline(false),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no')->label('No')->sortable()->searchable(),
                TextColumn::make('title')->label('Title')->searchable()->limit(40),
                TextColumn::make('service.description')->label('Service'),
                TextColumn::make('amount')->label('Per Member')->money()->sortable(),
                TextColumn::make('scheduled_date')->label('Date')->date()->sortable(),
                TextColumn::make('member_count')->label('Members')->sortable(),
                TextColumn::make('total_invoiced')->label('Total Invoiced')->money()->sortable(),
                TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'processing' => 'warning',
                        'completed' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                IconColumn::make('notify_members')->label('Notify')->boolean(),
                IconColumn::make('send_email')->label('Email')->boolean(),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([
                EditAction::make(),
                Action::make('process')
                    ->label('Process Now')
                    ->icon(Heroicon::OutlinedBolt)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Process Scheduled Invoice')
                    ->modalDescription(fn (ScheduledInvoice $record): string => 'This will create invoices for all active, non-excluded members and post them. Amount per member: KES '.number_format((float) $record->amount, 2).'. This cannot be undone.'
                    )
                    ->action(function (ScheduledInvoice $record): void {
                        if (! $record->isProcessable()) {
                            Notification::make()->title('Already processed or in progress.')->warning()->send();

                            return;
                        }
                        ProcessScheduledInvoiceJob::dispatch($record->id);
                        Notification::make()->title('Processing started — you will be notified when complete.')->success()->send();
                    })
                    ->visible(fn (ScheduledInvoice $record): bool => $record->status === 'draft'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListScheduledInvoices::route('/'),
            'create' => CreateScheduledInvoice::route('/create'),
            'edit' => EditScheduledInvoice::route('/{record}/edit'),
        ];
    }
}
