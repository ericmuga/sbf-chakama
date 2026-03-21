<?php

namespace App\Filament\Resources\Finance\CustomerLedgerEntries\Pages;

use App\Filament\Resources\Finance\CustomerLedgerEntries\CustomerLedgerEntryResource;
use App\Models\Finance\Customer;
use App\Models\Finance\CustomerLedgerEntry;
use App\Services\Finance\LedgerApplicationService;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;

class ListCustomerLedgerEntries extends ListRecords
{
    protected static string $resource = CustomerLedgerEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('applyCustomerEntries')
                ->label('Apply Entries')
                ->icon(Heroicon::OutlinedArrowsRightLeft)
                ->color('primary')
                ->modalHeading('Apply Customer Entries')
                ->modalDescription('Select a customer and source entry, then specify amounts to apply against opposite-sign entries.')
                ->schema([
                    Select::make('customer_id')
                        ->label('Customer')
                        ->options(Customer::query()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (Set $set): void {
                            $set('source_entry_id', null);
                            $set('applications', []);
                        }),

                    Select::make('source_entry_id')
                        ->label('Source Entry')
                        ->options(fn (Get $get): array => $get('customer_id')
                            ? CustomerLedgerEntry::where('customer_id', $get('customer_id'))
                                ->where('is_open', true)
                                ->orderBy('entry_no', 'desc')
                                ->get()
                                ->mapWithKeys(fn (CustomerLedgerEntry $e): array => [
                                    $e->id => "#$e->entry_no — {$e->document_type} {$e->document_no} (Remaining: ".number_format(abs((float) $e->remaining_amount), 2).')',
                                ])
                                ->toArray()
                            : [])
                        ->searchable()
                        ->required()
                        ->live()
                        ->hidden(fn (Get $get): bool => ! $get('customer_id'))
                        ->afterStateUpdated(function (?int $state, Set $set, Get $get): void {
                            $set('applications', []);

                            if (! $state) {
                                return;
                            }

                            $source = CustomerLedgerEntry::find($state);

                            if (! $source) {
                                return;
                            }

                            $oppositeEntries = CustomerLedgerEntry::where('customer_id', $get('customer_id'))
                                ->where('is_open', true)
                                ->where('id', '!=', $state)
                                ->where(function ($query) use ($source): void {
                                    if ($source->amount > 0) {
                                        $query->where('amount', '<', 0);
                                    } else {
                                        $query->where('amount', '>', 0);
                                    }
                                })
                                ->orderBy('due_date')
                                ->get();

                            $set('applications', $oppositeEntries->map(fn (CustomerLedgerEntry $entry): array => [
                                'customer_ledger_entry_id' => $entry->id,
                                'entry_no' => '#'.$entry->entry_no,
                                'document_type' => ucfirst($entry->document_type),
                                'document_no' => $entry->document_no,
                                'due_date' => $entry->due_date?->format('Y-m-d'),
                                'remaining_amount' => number_format(abs((float) $entry->remaining_amount), 2),
                                'amount_applied' => null,
                            ])->toArray());
                        }),

                    Repeater::make('applications')
                        ->label('Entries to Apply')
                        ->schema([
                            Hidden::make('customer_ledger_entry_id'),
                            TextInput::make('entry_no')
                                ->label('Entry No')
                                ->disabled()
                                ->dehydrated(false),
                            TextInput::make('document_type')
                                ->label('Type')
                                ->disabled()
                                ->dehydrated(false),
                            TextInput::make('document_no')
                                ->label('Document No')
                                ->disabled()
                                ->dehydrated(false),
                            TextInput::make('due_date')
                                ->label('Due Date')
                                ->disabled()
                                ->dehydrated(false),
                            TextInput::make('remaining_amount')
                                ->label('Outstanding')
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
                        ])
                        ->columns(4)
                        ->columnSpanFull()
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false)
                        ->hidden(fn (Get $get): bool => ! $get('source_entry_id')),
                ])
                ->action(function (array $data): void {
                    $source = CustomerLedgerEntry::find($data['source_entry_id']);

                    if (! $source) {
                        Notification::make()->title('Source entry not found.')->danger()->send();

                        return;
                    }

                    try {
                        app(LedgerApplicationService::class)->applyCustomerEntries($source, $data['applications'] ?? []);
                        Notification::make()->title('Entries applied successfully')->success()->send();
                    } catch (\RuntimeException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }
}
