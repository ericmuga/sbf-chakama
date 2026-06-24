<?php

namespace App\Filament\Resources\Members\Tables;

use App\Models\Finance\Customer;
use App\Models\Finance\CustomerLedgerEntry;
use App\Models\Member;
use App\Services\MemberImportService;
use App\Services\MemberPurgeService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
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
                TextColumn::make('name')
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
                        Toggle::make('purge')
                            ->label('Purge existing members first')
                            ->helperText('Permanently delete ALL existing members and their finance records (customers, vendors, ledgers, invoices, claims, shares). Admin logins are kept. This cannot be undone.')
                            ->default(false),
                    ])
                    ->action(function (array $data, MemberImportService $importer, MemberPurgeService $purger) {
                        $purged = 0;

                        if (! empty($data['purge'])) {
                            $purged = $purger->purge();
                        }

                        $path = Storage::disk('local')->path($data['file']);
                        $handle = fopen($path, 'r');
                        $result = $importer->importFromHandle($handle);
                        fclose($handle);
                        Storage::disk('local')->delete($data['file']);

                        $title = "{$result['imported']} member(s) imported";

                        if ($purged > 0) {
                            $title = "{$purged} purged, {$title}";
                        }

                        $notification = Notification::make()->title($title);

                        if (count($result['skipped']) > 0) {
                            $notification
                                ->warning()
                                ->body(count($result['skipped']).' row(s) skipped:'.PHP_EOL.implode(PHP_EOL, $result['skipped']));
                        } else {
                            $notification->success();
                        }

                        $notification->send();
                    }),
            ])
            ->recordActions([
                Action::make('statement')
                    ->label('Statement')
                    ->icon(Heroicon::OutlinedDocumentChartBar)
                    ->color('info')
                    ->slideOver()
                    ->modalWidth('2xl')
                    ->modalHeading(fn (Member $record): string => 'Statement — '.$record->name)
                    ->modalContent(fn (Member $record) => view('livewire.members.member-statement-modal', ['memberId' => $record->id]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
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
