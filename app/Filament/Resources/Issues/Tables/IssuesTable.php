<?php

namespace App\Filament\Resources\Issues\Tables;

use App\Enums\IssueCategory;
use App\Enums\IssuePortal;
use App\Enums\IssueStatus;
use App\Models\Issue;
use App\Services\IssueImportService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class IssuesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Issue')
                    ->wrap()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('portal_type')
                    ->label('Portal')
                    ->badge(),
                TextColumn::make('category')
                    ->label('Type')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('issue_owner')
                    ->label('Owner')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('resource')
                    ->label('Resource')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('date_assigned')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('date_actioned')
                    ->label('Date Actioned')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('qa_test_result')
                    ->label('QA')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Pass' => 'success',
                        'Fail' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(),
                TextColumn::make('closure_date')
                    ->label('Date Closed')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('release.version')
                    ->label('Release')
                    ->badge()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(IssueStatus::class)
                    ->multiple(),
                SelectFilter::make('portal_type')
                    ->label('Portal')
                    ->options(IssuePortal::class)
                    ->multiple(),
                SelectFilter::make('category')
                    ->label('Type')
                    ->options(IssueCategory::class),
                SelectFilter::make('qa_test_result')
                    ->label('QA Result')
                    ->options(['Pass' => 'Pass', 'Fail' => 'Fail']),
                SelectFilter::make('issue_owner')
                    ->label('Owner')
                    ->options(fn (): array => self::distinct('issue_owner')),
                SelectFilter::make('resource')
                    ->options(fn (): array => self::distinct('resource')),
                SelectFilter::make('release')
                    ->relationship('release', 'version'),
                Filter::make('date_assigned')
                    ->schema([
                        DatePicker::make('assigned_from')->label('Assigned from'),
                        DatePicker::make('assigned_until')->label('Assigned until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['assigned_from'] ?? null, fn (Builder $q, $date) => $q->whereDate('date_assigned', '>=', $date))
                        ->when($data['assigned_until'] ?? null, fn (Builder $q, $date) => $q->whereDate('date_assigned', '<=', $date))),
            ])
            ->headerActions([
                Action::make('export')
                    ->label('Download CSV')
                    ->icon(Heroicon::ArrowDownTray)
                    ->color('success')
                    ->url(fn (): string => route('admin.issues.export'))
                    ->openUrlInNewTab(),
                Action::make('downloadTemplate')
                    ->label('Download Template')
                    ->icon(Heroicon::ArrowDownTray)
                    ->color('gray')
                    ->url(fn (): string => route('admin.templates.issues'))
                    ->openUrlInNewTab(),
                Action::make('import')
                    ->label('Upload CSV')
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
                    ->action(function (array $data, IssueImportService $importer): void {
                        $path = Storage::disk('local')->path($data['file']);
                        $handle = fopen($path, 'r');
                        $result = $importer->importFromHandle($handle);
                        fclose($handle);
                        Storage::disk('local')->delete($data['file']);

                        $notification = Notification::make()
                            ->title("{$result['imported']} issue(s) imported");

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
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Distinct non-empty values for a column, as an options array.
     *
     * @return array<string, string>
     */
    private static function distinct(string $column): array
    {
        return Issue::query()
            ->whereNotNull($column)
            ->distinct()
            ->orderBy($column)
            ->pluck($column, $column)
            ->all();
    }
}
