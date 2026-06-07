<?php

namespace App\Filament\Resources\Chakama\ChakamaMemberReports;

use App\Filament\Exports\Chakama\ChakamaMemberReportExporter;
use App\Filament\Resources\Chakama\ChakamaMemberReports\Pages\ListChakamaMemberReports;
use App\Filament\Resources\Members\MemberResource;
use App\Models\Finance\Customer;
use App\Models\Finance\CustomerLedgerEntry;
use App\Models\Member;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ChakamaMemberReportResource extends Resource
{
    protected static ?string $model = Member::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static \UnitEnum|string|null $navigationGroup = 'Chakama — Reports';

    protected static ?string $navigationLabel = 'Member Billing Report';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('is_chakama', true)
            ->with(['shareSubscriptions']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no')
                    ->label('Member No')
                    ->searchable()
                    ->sortable()
                    ->color('primary')
                    ->weight('bold')
                    ->url(fn (Member $record): string => MemberResource::getUrl('edit', ['record' => $record])),
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->url(fn (Member $record): string => MemberResource::getUrl('edit', ['record' => $record])),
                TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable(),
                TextColumn::make('member_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive', 'suspended' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('opening_balance')
                    ->label('Opening (KES)')
                    ->state(fn (Member $record, $livewire): float => static::getPeriodStats($record, ...static::readPeriod($livewire))['opening_balance'])
                    ->money('KES')
                    ->alignRight()
                    ->color(fn (Member $record, $livewire): string => static::getPeriodStats($record, ...static::readPeriod($livewire))['opening_balance'] > 0 ? 'danger' : 'gray')
                    ->action(static::ledgerDrilldown('Opening Ledger', upToFromKey: true)),
                TextColumn::make('movement_in')
                    ->label('Charged (KES)')
                    ->state(fn (Member $record, $livewire): float => static::getPeriodStats($record, ...static::readPeriod($livewire))['movement_in'])
                    ->money('KES')
                    ->alignRight()
                    ->color('danger')
                    ->action(static::periodDrilldown('Charges in Period', invoicesOnly: true)),
                TextColumn::make('movement_out')
                    ->label('Paid (KES)')
                    ->state(fn (Member $record, $livewire): float => static::getPeriodStats($record, ...static::readPeriod($livewire))['movement_out'])
                    ->money('KES')
                    ->alignRight()
                    ->color('success')
                    ->action(static::periodDrilldown('Payments in Period', paymentsOnly: true)),
                TextColumn::make('closing_balance')
                    ->label('Closing (KES)')
                    ->state(fn (Member $record, $livewire): float => static::getPeriodStats($record, ...static::readPeriod($livewire))['closing_balance'])
                    ->money('KES')
                    ->alignRight()
                    ->weight('bold')
                    ->color(fn (Member $record, $livewire): string => static::getPeriodStats($record, ...static::readPeriod($livewire))['closing_balance'] > 0 ? 'danger' : 'success')
                    ->action(static::ledgerDrilldown('Closing Ledger', upToFromKey: false)),
                TextColumn::make('total_billed')
                    ->label('Total Billed (KES)')
                    ->state(fn (Member $record): float => static::getMemberBillingStats($record)['total_billed'])
                    ->money('KES')
                    ->alignRight()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total_paid')
                    ->label('Total Paid (KES)')
                    ->state(fn (Member $record): float => static::getMemberBillingStats($record)['total_paid'])
                    ->money('KES')
                    ->alignRight()
                    ->color('success')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('months_outstanding')
                    ->label('Months Overdue')
                    ->state(fn (Member $record): int => static::getMemberBillingStats($record)['months_outstanding'])
                    ->numeric()
                    ->alignCenter()
                    ->color(fn (Member $record): string => static::getMemberBillingStats($record)['months_outstanding'] > 0 ? 'danger' : 'success')
                    ->action(static::invoicesDrilldown('Overdue Invoices', openOnly: true)),
                TextColumn::make('oldest_due_date')
                    ->label('Oldest Due Date')
                    ->state(fn (Member $record): string => static::getMemberBillingStats($record)['oldest_due_date'] ?? '—')
                    ->sortable(false),
                TextColumn::make('share_count')
                    ->label('Shares')
                    ->state(fn (Member $record): int => (int) $record->shareSubscriptions->sum('number_of_shares'))
                    ->numeric()
                    ->alignCenter()
                    ->action(static::sharesDrilldown()),
            ])
            ->filters([
                Filter::make('period')
                    ->form([
                        DatePicker::make('date_from')
                            ->label('From')
                            ->default(now()->startOfYear()),
                        DatePicker::make('date_to')
                            ->label('To')
                            ->default(today()),
                    ])
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if (! empty($data['date_from'])) {
                            $indicators['date_from'] = 'From: '.Carbon::parse($data['date_from'])->format('d M Y');
                        }
                        if (! empty($data['date_to'])) {
                            $indicators['date_to'] = 'To: '.Carbon::parse($data['date_to'])->format('d M Y');
                        }

                        return $indicators;
                    }),
                SelectFilter::make('member_status')
                    ->options([
                        'active' => 'Active',
                        'lapsed' => 'Lapsed',
                        'suspended' => 'Suspended',
                    ]),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(ChakamaMemberReportExporter::class)
                    ->formats([ExportFormat::Xlsx, ExportFormat::Csv])
                    ->columnMappingColumns(2)
                    ->label('Export to Excel/CSV'),
                Action::make('print_pdf')
                    ->label('Print PDF')
                    ->icon(Heroicon::OutlinedDocumentArrowDown)
                    ->color('primary')
                    ->url(function ($livewire): string {
                        [$from, $to] = static::readPeriod($livewire);

                        return route('admin.reports.chakama-member-report.pdf', array_filter([
                            'date_from' => $from,
                            'date_to' => $to,
                        ]));
                    })
                    ->openUrlInNewTab(),
            ])
            ->recordActions([])
            ->defaultSort('name', 'asc');
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    public static function readPeriod(mixed $livewire): array
    {
        $filters = $livewire?->tableFilters['period'] ?? [];

        return [$filters['date_from'] ?? null, $filters['date_to'] ?? null];
    }

    /** @return array{opening_balance: float, movement_in: float, movement_out: float, closing_balance: float} */
    public static function getPeriodStats(Member $record, ?string $dateFrom, ?string $dateTo): array
    {
        $key = "periodStats-{$dateFrom}-{$dateTo}";

        if ($record->relationLoaded($key)) {
            return $record->getRelation($key);
        }

        $customer = $record->customer_no
            ? Customer::where('no', $record->customer_no)->first()
            : null;

        if (! $customer) {
            $empty = [
                'opening_balance' => 0.0,
                'movement_in' => 0.0,
                'movement_out' => 0.0,
                'closing_balance' => 0.0,
            ];
            $record->setRelation($key, $empty);

            return $empty;
        }

        $base = CustomerLedgerEntry::query()->where('customer_id', $customer->id);

        $opening = $dateFrom
            ? (float) (clone $base)->where('posting_date', '<', $dateFrom)->sum('amount')
            : 0.0;

        $periodEntries = (clone $base)
            ->when($dateFrom, fn ($q) => $q->where('posting_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->where('posting_date', '<=', $dateTo))
            ->get(['amount']);

        $movementIn = (float) $periodEntries->where('amount', '>', 0)->sum('amount');
        $movementOut = (float) abs($periodEntries->where('amount', '<', 0)->sum('amount'));

        $closing = $opening + $movementIn - $movementOut;

        $stats = [
            'opening_balance' => $opening,
            'movement_in' => $movementIn,
            'movement_out' => $movementOut,
            'closing_balance' => $closing,
        ];
        $record->setRelation($key, $stats);

        return $stats;
    }

    private static function periodDrilldown(string $heading, bool $invoicesOnly = false, bool $paymentsOnly = false): Action
    {
        $key = $invoicesOnly ? 'period_in' : ($paymentsOnly ? 'period_out' : 'period_all');

        return Action::make('drilldown_'.$key)
            ->modalHeading(fn (Member $record): string => "{$heading} — {$record->name}")
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->slideOver()
            ->schema(function (Member $record, $livewire) use ($invoicesOnly, $paymentsOnly): array {
                [$dateFrom, $dateTo] = static::readPeriod($livewire);

                $customer = $record->customer_no ? Customer::where('no', $record->customer_no)->first() : null;
                $entries = $customer
                    ? CustomerLedgerEntry::query()
                        ->where('customer_id', $customer->id)
                        ->when($invoicesOnly, fn ($q) => $q->where('document_type', 'invoice')->where('amount', '>', 0))
                        ->when($paymentsOnly, fn ($q) => $q->where('amount', '<', 0))
                        ->when($dateFrom, fn ($q) => $q->where('posting_date', '>=', $dateFrom))
                        ->when($dateTo, fn ($q) => $q->where('posting_date', '<=', $dateTo))
                        ->orderBy('posting_date')
                        ->get()
                    : collect();

                $record->setRelation('drilldownPeriod', $entries);

                $rangeLabel = match (true) {
                    $dateFrom && $dateTo => Carbon::parse($dateFrom)->format('d M Y').' – '.Carbon::parse($dateTo)->format('d M Y'),
                    $dateFrom => 'From '.Carbon::parse($dateFrom)->format('d M Y'),
                    $dateTo => 'Up to '.Carbon::parse($dateTo)->format('d M Y'),
                    default => 'All time',
                };

                return [
                    Section::make()->schema([
                        TextEntry::make('drilldown_meta')
                            ->hiddenLabel()
                            ->state(fn () => $entries->count().' entry(ies) · '.$rangeLabel),
                        RepeatableEntry::make('drilldownPeriod')
                            ->hiddenLabel()
                            ->columns(5)
                            ->schema([
                                TextEntry::make('document_no')->label('Document'),
                                TextEntry::make('document_type')->label('Type')->badge(),
                                TextEntry::make('posting_date')->label('Date')->date('d M Y'),
                                TextEntry::make('amount')->label('Debit (KES)')
                                    ->state(fn ($record) => (float) $record->amount > 0 ? (float) $record->amount : null)
                                    ->money('KES'),
                                TextEntry::make('amount_credit')->label('Credit (KES)')
                                    ->state(fn ($record) => (float) $record->amount < 0 ? abs((float) $record->amount) : null)
                                    ->money('KES'),
                            ])
                            ->visible(fn () => $entries->isNotEmpty()),
                    ]),
                ];
            });
    }

    private static function ledgerDrilldown(string $heading, bool $upToFromKey): Action
    {
        return Action::make('drilldown_'.($upToFromKey ? 'opening' : 'closing'))
            ->modalHeading(fn (Member $record): string => "{$heading} — {$record->name}")
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->slideOver()
            ->schema(function (Member $record, $livewire) use ($upToFromKey): array {
                [$dateFrom, $dateTo] = static::readPeriod($livewire);

                $cutoff = $upToFromKey ? $dateFrom : $dateTo;

                $customer = $record->customer_no ? Customer::where('no', $record->customer_no)->first() : null;
                $entries = $customer && $cutoff
                    ? CustomerLedgerEntry::query()
                        ->where('customer_id', $customer->id)
                        ->where('posting_date', $upToFromKey ? '<' : '<=', $cutoff)
                        ->orderBy('posting_date')
                        ->orderBy('entry_no')
                        ->get()
                    : collect();

                $record->setRelation('drilldownLedger', $entries);

                $running = 0.0;
                $running = $entries->sum(fn ($e) => (float) $e->amount);
                $balanceLabel = abs($running).' '.($running > 0 ? 'DR' : ($running < 0 ? 'CR' : 'NIL'));

                return [
                    Section::make()->schema([
                        TextEntry::make('drilldown_meta')
                            ->hiddenLabel()
                            ->state(fn () => $entries->count().' entry(ies) · Balance KES '.number_format(abs($running), 2).' '.($running > 0 ? 'DR' : ($running < 0 ? 'CR' : 'NIL'))),
                        RepeatableEntry::make('drilldownLedger')
                            ->hiddenLabel()
                            ->columns(5)
                            ->schema([
                                TextEntry::make('document_no')->label('Document'),
                                TextEntry::make('document_type')->label('Type')->badge(),
                                TextEntry::make('posting_date')->label('Date')->date('d M Y'),
                                TextEntry::make('amount')->label('Debit (KES)')
                                    ->state(fn ($record) => (float) $record->amount > 0 ? (float) $record->amount : null)
                                    ->money('KES'),
                                TextEntry::make('amount_credit')->label('Credit (KES)')
                                    ->state(fn ($record) => (float) $record->amount < 0 ? abs((float) $record->amount) : null)
                                    ->money('KES'),
                            ])
                            ->visible(fn () => $entries->isNotEmpty()),
                    ]),
                ];
            });
    }

    private static function invoicesDrilldown(string $heading, bool $openOnly = false): Action
    {
        return Action::make('drilldown_invoices_'.($openOnly ? 'open' : 'all'))
            ->modalHeading(fn (Member $record): string => "{$heading} — {$record->name}")
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->slideOver()
            ->schema(function (Member $record) use ($openOnly): array {
                $customer = $record->customer_no ? Customer::where('no', $record->customer_no)->first() : null;
                $entries = $customer
                    ? CustomerLedgerEntry::query()
                        ->where('customer_id', $customer->id)
                        ->where('document_type', 'invoice')
                        ->when($openOnly, fn (Builder $q) => $q->where('is_open', true)->where('remaining_amount', '>', 0))
                        ->orderByDesc('posting_date')
                        ->get()
                    : collect();

                $record->setRelation('drilldownInvoices', $entries);

                return [
                    Section::make()->schema([
                        TextEntry::make('drilldown_count')
                            ->hiddenLabel()
                            ->state(fn () => $entries->count().' invoice(s)'),
                        RepeatableEntry::make('drilldownInvoices')
                            ->hiddenLabel()
                            ->columns(5)
                            ->schema([
                                TextEntry::make('document_no')
                                    ->label('Invoice No'),
                                TextEntry::make('posting_date')
                                    ->label('Date')
                                    ->date('d M Y'),
                                TextEntry::make('due_date')
                                    ->label('Due')
                                    ->date('d M Y'),
                                TextEntry::make('amount')
                                    ->label('Amount')
                                    ->money('KES'),
                                TextEntry::make('remaining_amount')
                                    ->label('Outstanding')
                                    ->money('KES'),
                            ])
                            ->visible(fn () => $entries->isNotEmpty()),
                    ]),
                ];
            });
    }

    private static function paymentsDrilldown(): Action
    {
        return Action::make('drilldown_payments')
            ->modalHeading(fn (Member $record): string => "Payments — {$record->name}")
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->slideOver()
            ->schema(function (Member $record): array {
                $customer = $record->customer_no ? Customer::where('no', $record->customer_no)->first() : null;
                $entries = $customer
                    ? CustomerLedgerEntry::query()
                        ->where('customer_id', $customer->id)
                        ->where('document_type', 'payment')
                        ->orderByDesc('posting_date')
                        ->get()
                    : collect();

                $record->setRelation('drilldownPayments', $entries);

                return [
                    Section::make()->schema([
                        TextEntry::make('drilldown_count')
                            ->hiddenLabel()
                            ->state(fn () => $entries->count().' payment(s)'),
                        RepeatableEntry::make('drilldownPayments')
                            ->hiddenLabel()
                            ->columns(3)
                            ->schema([
                                TextEntry::make('document_no')
                                    ->label('Receipt No'),
                                TextEntry::make('posting_date')
                                    ->label('Date')
                                    ->date('d M Y'),
                                TextEntry::make('amount')
                                    ->label('Amount')
                                    ->money('KES'),
                            ])
                            ->visible(fn () => $entries->isNotEmpty()),
                    ]),
                ];
            });
    }

    private static function sharesDrilldown(): Action
    {
        return Action::make('drilldown_shares')
            ->modalHeading(fn (Member $record): string => "Share Allocations — {$record->name}")
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->slideOver()
            ->schema(function (Member $record): array {
                $record->loadMissing('shareSubscriptions.billingSchedule');

                return [
                    Section::make()->schema([
                        TextEntry::make('drilldown_count')
                            ->hiddenLabel()
                            ->state(fn () => $record->shareSubscriptions->count().' subscription(s)'),
                        RepeatableEntry::make('shareSubscriptions')
                            ->hiddenLabel()
                            ->columns(6)
                            ->schema([
                                TextEntry::make('no')
                                    ->label('Subscription'),
                                TextEntry::make('billingSchedule.name')
                                    ->label('Schedule'),
                                TextEntry::make('number_of_shares')
                                    ->label('Shares'),
                                TextEntry::make('total_amount')
                                    ->label('Total')
                                    ->money('KES'),
                                TextEntry::make('amount_paid')
                                    ->label('Paid')
                                    ->money('KES'),
                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge(),
                            ])
                            ->visible(fn () => $record->shareSubscriptions->isNotEmpty()),
                    ]),
                ];
            });
    }

    /** @return array{total_billed: float, total_paid: float, outstanding: float, months_outstanding: int, oldest_due_date: ?string} */
    public static function getMemberBillingStats(Member $record): array
    {
        static $cache = [];

        if (isset($cache[$record->id])) {
            return $cache[$record->id];
        }

        $customer = $record->customer_no
            ? Customer::where('no', $record->customer_no)->first()
            : null;

        if (! $customer) {
            return $cache[$record->id] = ['total_billed' => 0, 'total_paid' => 0, 'outstanding' => 0, 'months_outstanding' => 0, 'oldest_due_date' => null];
        }

        $entries = CustomerLedgerEntry::where('customer_id', $customer->id)
            ->where('document_type', 'invoice')
            ->get();

        $totalBilled = $entries->sum(fn ($e) => (float) $e->amount);
        $totalPaid = $entries->sum(fn ($e) => (float) $e->amount - (float) $e->remaining_amount);
        $outstanding = $entries->where('is_open', true)->sum(fn ($e) => (float) $e->remaining_amount);

        $openEntries = $entries->where('is_open', true)->where('remaining_amount', '>', 0);
        $oldestDue = $openEntries->min('due_date');
        $monthsOutstanding = $oldestDue ? max(0, (int) now()->startOfMonth()->diffInMonths($oldestDue, false) * -1) : 0;

        return $cache[$record->id] = [
            'total_billed' => $totalBilled,
            'total_paid' => $totalPaid,
            'outstanding' => $outstanding,
            'months_outstanding' => $monthsOutstanding,
            'oldest_due_date' => $oldestDue?->format('d M Y'),
        ];
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListChakamaMemberReports::route('/'),
        ];
    }
}
