<?php

namespace App\Filament\Resources\Chakama\ChakamaMemberReports;

use App\Filament\Exports\Chakama\ChakamaMemberReportExporter;
use App\Filament\Resources\Chakama\ChakamaMemberReports\Pages\ListChakamaMemberReports;
use App\Models\Finance\Customer;
use App\Models\Finance\CustomerLedgerEntry;
use App\Models\Member;
use BackedEnum;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
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
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
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
                TextColumn::make('total_billed')
                    ->label('Total Billed (KES)')
                    ->state(fn (Member $record): float => static::getMemberBillingStats($record)['total_billed'])
                    ->numeric(decimalPlaces: 2)
                    ->money('KES')
                    ->alignRight(),
                TextColumn::make('total_paid')
                    ->label('Total Paid (KES)')
                    ->state(fn (Member $record): float => static::getMemberBillingStats($record)['total_paid'])
                    ->numeric(decimalPlaces: 2)
                    ->money('KES')
                    ->alignRight()
                    ->color('success'),
                TextColumn::make('outstanding')
                    ->label('Outstanding (KES)')
                    ->state(fn (Member $record): float => static::getMemberBillingStats($record)['outstanding'])
                    ->numeric(decimalPlaces: 2)
                    ->money('KES')
                    ->alignRight()
                    ->color(fn (Member $record): string => static::getMemberBillingStats($record)['outstanding'] > 0 ? 'danger' : 'success'),
                TextColumn::make('months_outstanding')
                    ->label('Months Overdue')
                    ->state(fn (Member $record): int => static::getMemberBillingStats($record)['months_outstanding'])
                    ->numeric()
                    ->alignCenter()
                    ->color(fn (Member $record): string => static::getMemberBillingStats($record)['months_outstanding'] > 0 ? 'danger' : 'success'),
                TextColumn::make('oldest_due_date')
                    ->label('Oldest Due Date')
                    ->state(fn (Member $record): string => static::getMemberBillingStats($record)['oldest_due_date'] ?? '—')
                    ->sortable(false),
                TextColumn::make('share_count')
                    ->label('Shares')
                    ->state(fn (Member $record): int => (int) $record->shareSubscriptions->sum('number_of_shares'))
                    ->numeric()
                    ->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('member_status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'suspended' => 'Suspended',
                    ]),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(ChakamaMemberReportExporter::class)
                    ->formats([ExportFormat::Xlsx, ExportFormat::Csv])
                    ->columnMappingColumns(2)
                    ->label('Export to Excel/CSV'),
            ])
            ->recordActions([])
            ->defaultSort('name', 'asc');
    }

    /** @return array{total_billed: float, total_paid: float, outstanding: float, months_outstanding: int, oldest_due_date: ?string} */
    private static function getMemberBillingStats(Member $record): array
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
