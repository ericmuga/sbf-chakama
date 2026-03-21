<?php

namespace App\Filament\Resources\Finance\VendorPayments\Schemas;

use App\Models\Finance\PaymentMethod;
use App\Models\Finance\VendorLedgerEntry;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class VendorPaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('no')
                    ->label('Payment No')
                    ->disabled()
                    ->dehydrated()
                    ->hidden(fn (string $operation): bool => $operation === 'create'),
                Select::make('vendor_id')
                    ->label('Vendor')
                    ->relationship('vendor', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (?int $state, Set $set): void {
                        $set('allocations', []);

                        if ($state) {
                            $invoices = VendorLedgerEntry::where('vendor_id', $state)
                                ->where('document_type', 'invoice')
                                ->where('is_open', true)
                                ->orderBy('due_date')
                                ->get();

                            $set('allocations', $invoices->map(fn (VendorLedgerEntry $inv): array => [
                                'vendor_ledger_entry_id' => $inv->id,
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
                    ->label('Total Amount Paid')
                    ->numeric()
                    ->minValue(0)
                    ->required(),
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
                        Hidden::make('vendor_ledger_entry_id'),
                    ])
                    ->columns(4)
                    ->columnSpanFull()
                    ->addable(false)
                    ->deletable(false)
                    ->reorderable(false)
                    ->hidden(fn (Get $get): bool => empty($get('vendor_id'))),
            ]);
    }
}
