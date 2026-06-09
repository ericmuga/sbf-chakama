<?php

namespace App\Exports;

use App\Models\Finance\Vendor;
use App\Models\Finance\VendorLedgerEntry;
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

class VendorListExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(
        private ?int $postingGroupId = null,
        private bool $onlyWithBalance = false,
    ) {}

    public function title(): string
    {
        return 'Vendors';
    }

    public function collection(): Collection
    {
        return Vendor::query()
            ->with(['vendorPostingGroup', 'paymentTerms'])
            ->withSum('vendorLedgerEntries as balance_sum', 'amount')
            ->when($this->postingGroupId, fn ($q) => $q->where('vendor_posting_group_id', $this->postingGroupId))
            ->when($this->onlyWithBalance, fn ($q) => $q->whereIn(
                'id',
                VendorLedgerEntry::query()
                    ->groupBy('vendor_id')
                    ->havingRaw('SUM(amount) <> 0')
                    ->select('vendor_id')
            ))
            ->orderBy('no')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Vendor No', 'Name', 'Posting Group', 'Payment Terms',
            'Balance (KES)', 'Direction',
        ];
    }

    public function map($vendor): array
    {
        $balance = (float) ($vendor->balance_sum ?? 0);

        return [
            $vendor->no,
            $vendor->name,
            $vendor->vendorPostingGroup?->description,
            $vendor->paymentTerms?->description ?? $vendor->payment_terms_code,
            number_format(abs($balance), 2),
            $balance > 0 ? 'DR' : ($balance < 0 ? 'CR' : 'NIL'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = $sheet->getHighestRow();

        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getStyle("A1:F{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']],
            ],
        ]);

        $sheet->getStyle("E2:E{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        return [];
    }
}
