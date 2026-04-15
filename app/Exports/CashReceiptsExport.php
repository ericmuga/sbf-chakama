<?php

namespace App\Exports;

use App\Models\Finance\CashReceipt;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CashReceiptsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(
        private readonly ?string $dateFrom,
        private readonly ?string $dateTo,
        private readonly ?int $paymentMethodId,
        private readonly ?string $status,
    ) {}

    public function title(): string
    {
        return 'Receipts';
    }

    public function collection(): Collection
    {
        return CashReceipt::query()
            ->with(['customer', 'paymentMethod', 'bankAccount', 'shareSubscription'])
            ->when($this->dateFrom, fn ($q) => $q->whereDate('posting_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('posting_date', '<=', $this->dateTo))
            ->when($this->paymentMethodId, fn ($q) => $q->where('payment_method_id', $this->paymentMethodId))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->orderBy('posting_date', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Receipt No', 'Date', 'Customer', 'Payment Method', 'Bank Account',
            'Amount (KES)', 'M-Pesa Receipt', 'M-Pesa Phone', 'Share Ref', 'Status', 'Description',
        ];
    }

    public function map($receipt): array
    {
        return [
            $receipt->no,
            $receipt->posting_date?->format('d M Y') ?? '',
            $receipt->customer?->name ?? '',
            $receipt->paymentMethod?->description ?? '',
            $receipt->bankAccount?->name ?? '',
            number_format((float) $receipt->amount, 2),
            $receipt->mpesa_receipt_no ?? '',
            $receipt->mpesa_phone ?? '',
            $receipt->shareSubscription?->no ?? '',
            ucfirst($receipt->status ?? ''),
            $receipt->description ?? '',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = $sheet->getHighestRow();

        $sheet->getStyle('A1:K1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getStyle("A1:K{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']],
            ],
        ]);

        $sheet->getStyle("F2:F{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        return [];
    }
}
