<?php

namespace App\Filament\Exports\Chakama;

use App\Models\Finance\Customer;
use App\Models\Finance\CustomerLedgerEntry;
use App\Models\Member;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class ChakamaMemberReportExporter extends Exporter
{
    protected static ?string $model = Member::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('no')
                ->label('Member No'),
            ExportColumn::make('name')
                ->label('Member Name'),
            ExportColumn::make('phone')
                ->label('Phone'),
            ExportColumn::make('email')
                ->label('Email'),
            ExportColumn::make('member_status')
                ->label('Status'),
            ExportColumn::make('total_billed')
                ->label('Total Billed (KES)')
                ->state(fn (Member $record): float => static::getMemberStats($record)['total_billed']),
            ExportColumn::make('total_paid')
                ->label('Total Paid (KES)')
                ->state(fn (Member $record): float => static::getMemberStats($record)['total_paid']),
            ExportColumn::make('outstanding')
                ->label('Outstanding (KES)')
                ->state(fn (Member $record): float => static::getMemberStats($record)['outstanding']),
            ExportColumn::make('months_outstanding')
                ->label('Months Outstanding')
                ->state(fn (Member $record): int => static::getMemberStats($record)['months_outstanding']),
            ExportColumn::make('oldest_overdue_date')
                ->label('Oldest Due Date')
                ->state(fn (Member $record): string => static::getMemberStats($record)['oldest_due_date'] ?? '—'),
            ExportColumn::make('share_count')
                ->label('Total Shares')
                ->state(fn (Member $record): int => static::getMemberStats($record)['share_count']),
        ];
    }

    /** @return array{total_billed: float, total_paid: float, outstanding: float, months_outstanding: int, oldest_due_date: ?string, share_count: int} */
    private static function getMemberStats(Member $record): array
    {
        $customer = $record->customer_no
            ? Customer::where('no', $record->customer_no)->first()
            : null;

        if (! $customer) {
            return ['total_billed' => 0, 'total_paid' => 0, 'outstanding' => 0, 'months_outstanding' => 0, 'oldest_due_date' => null, 'share_count' => 0];
        }

        $entries = CustomerLedgerEntry::where('customer_id', $customer->id)
            ->where('document_type', 'invoice')
            ->get();

        $totalBilled = $entries->sum(fn ($e) => (float) $e->amount);
        $totalPaid = $entries->sum(fn ($e) => (float) $e->amount - (float) $e->remaining_amount);
        $outstanding = $entries->where('is_open', true)->sum(fn ($e) => (float) $e->remaining_amount);

        $openEntries = $entries->where('is_open', true)->where('remaining_amount', '>', 0);
        $oldestDue = $openEntries->min('due_date');
        $monthsOutstanding = $oldestDue ? (int) now()->startOfMonth()->diffInMonths($oldestDue) : 0;

        $shareCount = $record->shareSubscriptions?->sum('number_of_shares') ?? 0;

        return [
            'total_billed' => $totalBilled,
            'total_paid' => $totalPaid,
            'outstanding' => $outstanding,
            'months_outstanding' => max(0, $monthsOutstanding),
            'oldest_due_date' => $oldestDue?->format('d M Y'),
            'share_count' => $shareCount,
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Chakama member report exported: '.Number::format($export->successful_rows).' '.str('row')->plural($export->successful_rows).'.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed.';
        }

        return $body;
    }
}
