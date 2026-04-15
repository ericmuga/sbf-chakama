<?php

namespace App\Filament\Resources\Members\Tables;

use App\Models\Finance\Customer;
use App\Models\Finance\CustomerLedgerEntry;
use App\Models\Member;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class MembersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no')
                    ->label('Member No')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('identity_no')
                    ->label('Identity No')
                    ->searchable(),
                TextColumn::make('identity_type')
                    ->label('ID Type')
                    ->badge(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('member_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'lapsed' => 'warning',
                        'suspended' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('running_balance')
                    ->label('Balance (KES)')
                    ->state(fn (Member $record): string => self::runningBalanceLabel($record))
                    ->color(fn (Member $record): string => self::balanceColor($record))
                    ->alignRight(),
                IconColumn::make('is_chakama')
                    ->label('Chakama')
                    ->boolean(),
                IconColumn::make('is_sbf')
                    ->label('SBF')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Action::make('exportMembersExcel')
                    ->label('Export to Excel')
                    ->icon(Heroicon::ArrowDownTray)
                    ->color('success')
                    ->url(fn () => route('admin.reports.members.export-excel'))
                    ->openUrlInNewTab(),
                Action::make('downloadTemplate')
                    ->label('Download Template')
                    ->icon(Heroicon::ArrowDownTray)
                    ->color('gray')
                    ->url(fn () => route('admin.templates.members'))
                    ->openUrlInNewTab(),
                Action::make('import')
                    ->label('Import from CSV')
                    ->icon(Heroicon::ArrowUpTray)
                    ->color('primary')
                    ->schema([
                        FileUpload::make('file')
                            ->label('CSV File')
                            ->disk('local')
                            ->directory('imports')
                            ->acceptedFileTypes(['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'])
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $path = Storage::disk('local')->path($data['file']);
                        $handle = fopen($path, 'r');
                        $headers = fgetcsv($handle);

                        while (($row = fgetcsv($handle)) !== false) {
                            $record = array_combine($headers, $row);
                            Member::updateOrCreate(
                                ['no' => $record['no'] ?: null],
                                array_filter([
                                    'no' => $record['no'] ?: null,
                                    'name' => $record['name'] ?: null,
                                    'identity_type' => $record['identity_type'] ?: 'national_id',
                                    'identity_no' => $record['identity_no'] ?: null,
                                    'phone' => $record['phone'] ?: null,
                                    'email' => $record['email'] ?: null,
                                    'date_of_birth' => $record['date_of_birth'] ?: null,
                                    'member_status' => $record['member_status'] ?: null,
                                    'customer_no' => $record['customer_no'] ?: null,
                                    'vendor_no' => $record['vendor_no'] ?: null,
                                    'is_chakama' => isset($record['is_chakama']) ? (bool) $record['is_chakama'] : false,
                                    'is_sbf' => isset($record['is_sbf']) ? (bool) $record['is_sbf'] : false,
                                    'type' => 'member',
                                ], fn ($v) => $v !== null && $v !== '')
                            );
                        }
                        fclose($handle);
                        Storage::disk('local')->delete($data['file']);
                    }),
            ])
            ->recordActions([
                Action::make('statement')
                    ->label('Statement')
                    ->icon(Heroicon::OutlinedDocumentChartBar)
                    ->color('info')
                    ->slideOver()
                    ->modalWidth('xl')
                    ->modalHeading(fn (Member $record): string => 'Statement — '.$record->name)
                    ->modalDescription('Select a date range to filter the statement. Leave blank for all entries.')
                    ->schema([
                        DatePicker::make('date_from')
                            ->label('From')
                            ->native(false),
                        DatePicker::make('date_to')
                            ->label('To')
                            ->native(false),
                    ])
                    ->modalSubmitActionLabel('Download Excel')
                    ->extraModalFooterActions(fn (Action $action): array => [
                        $action->makeModalSubmitAction('downloadPdf', arguments: ['format' => 'pdf'])
                            ->label('Download PDF')
                            ->color('danger')
                            ->icon(Heroicon::DocumentArrowDown),
                    ])
                    ->action(function (array $data, array $arguments, Member $record): mixed {
                        $format = $arguments['format'] ?? 'excel';
                        $params = array_filter([
                            'date_from' => $data['date_from'] ?? null,
                            'date_to' => $data['date_to'] ?? null,
                        ]);
                        $query = $params ? '?'.http_build_query($params) : '';

                        $routeName = $format === 'pdf'
                            ? 'admin.reports.member-statement.pdf'
                            : 'admin.reports.member-statement.excel';

                        return redirect()->away(route($routeName, $record).$query);
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function runningBalanceLabel(Member $record): string
    {
        $balance = self::resolveBalance($record);

        if ($balance === null) {
            return '—';
        }

        $direction = $balance > 0 ? 'DR' : ($balance < 0 ? 'CR' : 'NIL');

        return number_format(abs($balance), 2).' '.$direction;
    }

    private static function balanceColor(Member $record): string
    {
        $balance = self::resolveBalance($record);

        if ($balance === null || $balance === 0.0) {
            return 'gray';
        }

        return $balance > 0 ? 'warning' : 'success';
    }

    private static function resolveBalance(Member $record): ?float
    {
        if (! $record->customer_no) {
            return null;
        }

        $customer = Customer::where('no', $record->customer_no)->first();

        if (! $customer) {
            return null;
        }

        return (float) CustomerLedgerEntry::where('customer_id', $customer->id)->sum('amount');
    }
}
