<?php

namespace App\Filament\Resources\Finance\CustomerLedgerEntries\Pages;

use App\Filament\Resources\Finance\CustomerLedgerEntries\CustomerLedgerEntryResource;
use App\Models\Finance\CustomerLedgerEntry;
use App\Services\Finance\LedgerApplicationService;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

class EditCustomerLedgerEntry extends EditRecord
{
    protected static string $resource = CustomerLedgerEntryResource::class;

    protected function getFormActions(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('applyEntries')
                ->label('Apply Entries')
                ->icon(Heroicon::OutlinedArrowsRightLeft)
                ->color('primary')
                ->modalHeading('Apply Entries')
                ->modalDescription('Apply opposite-sign entries for this customer to reduce outstanding balances.')
                ->fillForm(fn (CustomerLedgerEntry $record): array => [
                    'applications' => CustomerLedgerEntry::where('customer_id', $record->customer_id)
                        ->where('is_open', true)
                        ->where('id', '!=', $record->id)
                        ->where(function ($query) use ($record): void {
                            if ($record->amount > 0) {
                                $query->where('amount', '<', 0);
                            } else {
                                $query->where('amount', '>', 0);
                            }
                        })
                        ->orderBy('due_date')
                        ->get()
                        ->map(fn (CustomerLedgerEntry $entry): array => [
                            'customer_ledger_entry_id' => $entry->id,
                            'entry_no' => '#'.$entry->entry_no,
                            'document_type' => ucfirst($entry->document_type),
                            'document_no' => $entry->document_no,
                            'due_date' => $entry->due_date?->format('Y-m-d'),
                            'remaining_amount' => number_format(abs((float) $entry->remaining_amount), 2),
                            'amount_applied' => null,
                        ])
                        ->toArray(),
                ])
                ->schema([
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
                        ->reorderable(false),
                ])
                ->action(function (CustomerLedgerEntry $record, array $data): void {
                    try {
                        app(LedgerApplicationService::class)->applyCustomerEntries($record, $data['applications'] ?? []);
                        Notification::make()->title('Entries applied successfully')->success()->send();
                        $this->refreshFormData(['remaining_amount', 'is_open']);
                    } catch (\RuntimeException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                })
                ->hidden(fn (CustomerLedgerEntry $record): bool => ! $record->is_open),
        ];
    }
}
