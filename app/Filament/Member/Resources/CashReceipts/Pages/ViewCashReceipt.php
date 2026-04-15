<?php

namespace App\Filament\Member\Resources\CashReceipts\Pages;

use App\Filament\Member\Resources\CashReceipts\CashReceiptResource;
use App\Models\Finance\CashReceipt;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewCashReceipt extends ViewRecord
{
    protected static string $resource = CashReceiptResource::class;

    protected function getHeaderActions(): array
    {
        /** @var CashReceipt $record */
        $record = $this->getRecord();

        return [
            Action::make('downloadPdf')
                ->label('Download Receipt')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->visible(fn (): bool => strtolower($record->status) === 'posted')
                ->url(fn (): string => route('admin.reports.receipt.pdf', $record))
                ->openUrlInNewTab(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Receipt Details')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('no')
                            ->label('Receipt No'),
                        TextEntry::make('posting_date')
                            ->label('Posting Date')
                            ->date('d M Y'),
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('paymentMethod.description')
                            ->label('Payment Method'),
                        TextEntry::make('bankAccount.name')
                            ->label('Bank Account'),
                        TextEntry::make('amount')
                            ->label('Amount (KES)')
                            ->numeric(decimalPlaces: 2),
                        TextEntry::make('description')
                            ->label('Description')
                            ->columnSpan(3)
                            ->placeholder('—'),
                    ]),
                Section::make('M-Pesa / STK Push Details')
                    ->description('Details received from the Safaricom STK Push API.')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('mpesa_receipt_no')
                            ->label('M-Pesa Receipt No')
                            ->placeholder('—'),
                        TextEntry::make('mpesa_phone')
                            ->label('Paying From (Phone)')
                            ->placeholder('—'),
                        TextEntry::make('created_at')
                            ->label('Received At')
                            ->dateTime('d M Y H:i:s')
                            ->placeholder('—'),
                    ]),
            ]);
    }
}
