<?php

namespace App\Filament\Resources\Finance\CashReceipts\Schemas;

use App\Models\Finance\CustomerLedgerEntry;
use App\Models\Finance\PaymentMethod;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class CashReceiptForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('no')
                    ->label('Receipt No')
                    ->disabled()
                    ->dehydrated()
                    ->hidden(fn (string $operation): bool => $operation === 'create'),
                Select::make('customer_id')
                    ->label('Customer')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (?int $state, Set $set): void {
                        $set('allocations', []);

                        if ($state) {
                            $invoices = CustomerLedgerEntry::where('customer_id', $state)
                                ->where('document_type', 'invoice')
                                ->where('is_open', true)
                                ->orderBy('due_date')
                                ->get();

                            $set('allocations', $invoices->map(fn ($inv) => [
                                'customer_ledger_entry_id' => $inv->id,
                                'document_no' => $inv->document_no,
                                'due_date' => $inv->due_date?->format('Y-m-d'),
                                'remaining_amount' => number_format((float) $inv->remaining_amount, 2),
                                'amount_applied' => null,
                            ])->toArray());
                        }
                    }),
                Select::make('payment_method_id')
                    ->label('Payment Method')
                    ->options(PaymentMethod::query()->pluck('description', 'id'))
                    ->searchable()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (?int $state, Set $set): void {
                        if ($state) {
                            $method = PaymentMethod::find($state);
                            $set('bank_account_id', $method?->bank_account_id);
                        }
                    }),
                Select::make('bank_account_id')
                    ->label('Bank Account')
                    ->relationship('bankAccount', 'name')
                    ->searchable()
                    ->preload()
                    ->disabled()
                    ->dehydrated()
                    ->required(),
                DatePicker::make('posting_date')
                    ->default(now())
                    ->required(),
                TextInput::make('amount')
                    ->label('Total Amount Received')
                    ->numeric()
                    ->minValue(0)
                    ->required(),
                TextInput::make('description')
                    ->label('Description')
                    ->maxLength(255),
                Section::make('M-Pesa / STK Push Details')
                    ->description('Populated automatically from the Safaricom STK Push callback.')
                    ->columns(3)
                    ->schema([
                        TextInput::make('mpesa_receipt_no')
                            ->label('M-Pesa Receipt No')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('—'),
                        TextInput::make('mpesa_phone')
                            ->label('Paying From (Phone)')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('—'),
                        DateTimePicker::make('created_at')
                            ->label('Received At')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->hidden(fn (string $operation): bool => $operation === 'create'),
                Repeater::make('allocations')
                    ->label('Invoice Allocations')
                    ->schema([
                        TextInput::make('document_no')
                            ->label('Invoice No')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('due_date')
                            ->label('Due Date')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('remaining_amount')
                            ->label('Outstanding Balance')
                            ->disabled()
                            ->dehydrated(false)
                            ->prefix('KES'),
                        TextInput::make('amount_applied')
                            ->label('Amount to Apply')
                            ->numeric()
                            ->minValue(0)
                            ->live()
                            ->rules([
                                fn (Get $get): \Closure => function (string $attribute, mixed $value, \Closure $fail) use ($get): void {
                                    $remaining = (float) str_replace(',', '', $get('remaining_amount') ?? '0');
                                    if ((float) $value > $remaining) {
                                        $fail("Cannot apply more than the outstanding balance of {$remaining}.");
                                    }
                                },
                            ]),
                        Hidden::make('customer_ledger_entry_id'),
                    ])
                    ->columns(4)
                    ->columnSpanFull()
                    ->addable(false)
                    ->deletable(false)
                    ->reorderable(false)
                    ->hidden(fn (Get $get): bool => empty($get('customer_id'))),
            ]);
    }
}
