<?php

namespace App\Exports;

use App\Models\Finance\Customer;
use App\Models\Finance\CustomerLedgerEntry;
use App\Models\Member;
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

class MemberListExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function title(): string
    {
        return 'Members';
    }

    public function collection(): Collection
    {
        return Member::query()
            ->members()
            ->with('user')
            ->where('is_chakama', true)
            ->orderBy('no')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Member No', 'Name', 'Identity No', 'Phone', 'Email',
            'Status', 'Customer No', 'Running Balance (KES)', 'Balance Direction',
        ];
    }

    public function map($member): array
    {
        $balance = 0.0;

        if ($member->customer_no) {
            $customer = Customer::where('no', $member->customer_no)->first();
            if ($customer) {
                $balance = (float) CustomerLedgerEntry::where('customer_id', $customer->id)->sum('amount');
            }
        }

        return [
            $member->no,
            $member->name,
            $member->identity_no,
            $member->phone,
            $member->email,
            $member->member_status,
            $member->customer_no,
            number_format(abs($balance), 2),
            $balance > 0 ? 'DR' : ($balance < 0 ? 'CR' : 'NIL'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = $sheet->getHighestRow();

        $sheet->getStyle('A1:I1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getStyle("A1:I{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']],
            ],
        ]);

        $sheet->getStyle("H2:H{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        return [];
    }
}
