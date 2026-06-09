<?php

namespace App\Exports;

use App\Models\Finance\Customer;
use App\Models\Finance\CustomerLedgerEntry;
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

class CustomerListExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(
        private ?int $postingGroupId = null,
        private bool $onlyWithBalance = false,
    ) {}

    public function title(): string
    {
        return 'Customers';
    }

    public function collection(): Collection
    {
        return Customer::query()
            ->with(['customerPostingGroup', 'paymentTerms'])
            ->withSum('customerLedgerEntries as balance_sum', 'amount')
            ->when($this->postingGroupId, fn ($q) => $q->where('customer_posting_group_id', $this->postingGroupId))
            ->when($this->onlyWithBalance, fn ($q) => $q->whereIn(
                'id',
                CustomerLedgerEntry::query()
                    ->groupBy('customer_id')
                    ->havingRaw('SUM(amount) <> 0')
                    ->select('customer_id')
            ))
            ->orderBy('no')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Customer No', 'Name', 'Posting Group', 'Payment Terms',
            'Balance (KES)', 'Direction',
        ];
    }

    public function map($customer): array
    {
        $balance = (float) ($customer->balance_sum ?? 0);

        return [
            $customer->no,
            $customer->name,
            $customer->customerPostingGroup?->description,
            $customer->paymentTerms?->description ?? $customer->payment_terms_code,
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
