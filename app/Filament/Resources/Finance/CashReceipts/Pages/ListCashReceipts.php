<?php

namespace App\Filament\Resources\Finance\CashReceipts\Pages;

use App\Filament\Resources\Finance\CashReceipts\CashReceiptResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListCashReceipts extends ListRecords
{
    protected static string $resource = CashReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportExcel')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(function (): string {
                    $dateFilter = $this->getTableFilterState('posting_date') ?? [];
                    $methodFilter = $this->getTableFilterState('payment_method_id') ?? [];
                    $statusFilter = $this->getTableFilterState('status') ?? [];

                    return route('admin.reports.receipts.excel', array_filter([
                        'date_from' => $dateFilter['from'] ?? null,
                        'date_to' => $dateFilter['to'] ?? null,
                        'payment_method_id' => $methodFilter['value'] ?? null,
                        'status' => $statusFilter['value'] ?? null,
                    ]));
                })
                ->openUrlInNewTab(),
            Action::make('newReceipt')
                ->label('Post New Receipt')
                ->icon('heroicon-o-plus')
                ->url($this->getResource()::getUrl('create')),
        ];
    }
}
