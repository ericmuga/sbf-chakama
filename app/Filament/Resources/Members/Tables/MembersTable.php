<?php

namespace App\Filament\Resources\Members\Tables;

use App\Models\Member;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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

                        $imported = 0;
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
                            $imported++;
                        }
                        fclose($handle);
                        Storage::disk('local')->delete($data['file']);
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
}
