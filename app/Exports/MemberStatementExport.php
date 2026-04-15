<?php

namespace App\Exports;

use App\Models\Member;
use App\Services\Reports\MemberStatementService;
use Carbon\Carbon;
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

class MemberStatementExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    private array $data;

    public function __construct(
        private readonly Member $member,
        private readonly ?string $dateFrom,
        private readonly ?string $dateTo,
    ) {
        $this->data = app(MemberStatementService::class)->build($member, $dateFrom, $dateTo);
    }

    public function title(): string
    {
        return 'Statement';
    }

    public function collection(): Collection
    {
        $rows = collect();

        // Opening balance row
        if ($this->data['date_from'] && $this->data['opening'] != 0) {
            $rows->push((object) [
                'posting_date' => Carbon::parse($this->data['date_from'])->subDay()->format('d M Y'),
                'document_type' => 'Opening Balance',
                'document_no' => '—',
                'amount' => null,
                'credit' => null,
                'running_balance' => $this->data['opening'],
            ]);
        }

        foreach ($this->data['entries'] as $entry) {
            $rows->push($entry);
        }

        // Totals row
        $rows->push((object) [
            'posting_date' => null,
            'document_type' => 'TOTALS',
            'document_no' => null,
            'amount' => $this->data['total_debits'],
            'credit' => $this->data['total_credits'],
            'running_balance' => $this->data['closing'],
        ]);

        return $rows;
    }

    public function headings(): array
    {
        return ['Date', 'Type', 'Document No', 'Debit (KES)', 'Credit (KES)', 'Running Balance (KES)'];
    }

    public function map($row): array
    {
        $amount = (float) ($row->amount ?? 0);

        return [
            $row->posting_date instanceof Carbon
                ? $row->posting_date->format('d M Y')
                : ($row->posting_date ?? ''),
            $row->document_type ?? '',
            $row->document_no ?? '',
            $amount > 0 ? number_format($amount, 2) : '',
            $amount < 0 ? number_format(abs($amount), 2) : (isset($row->credit) && $row->credit ? number_format((float) $row->credit, 2) : ''),
            isset($row->running_balance) ? number_format((float) $row->running_balance, 2) : '',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = $sheet->getHighestRow();

        // Header row style
        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Totals row
        $sheet->getStyle("A{$lastRow}:F{$lastRow}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8F0FE']],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_MEDIUM],
            ],
        ]);

        // All borders
        $sheet->getStyle("A1:F{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']],
            ],
        ]);

        // Right-align numeric columns
        $sheet->getStyle("D2:F{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        return [];
    }
}
