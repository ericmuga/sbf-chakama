<?php

namespace App\Filament\Resources\Chakama;

use App\Filament\Resources\Chakama\Pages\ListFundsBankAccounts;
use App\Models\Finance\BankAccount;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FundsBankAccountResource extends Resource
{
    protected static ?string $model = BankAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static \UnitEnum|string|null $navigationGroup = 'Chakama — Funds';

    protected static ?string $navigationLabel = 'Bank Accounts';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    private static function entriesAside(string $type): Action
    {
        $label = $type === 'receipt' ? 'Receipts' : 'Disbursements';
        $types = $type === 'receipt' ? ['receipt', 'income'] : ['payment', 'expense'];

        return Action::make("view_{$type}s")
            ->slideOver()
            ->modalHeading(fn (BankAccount $record): string => "{$label} — {$record->name}")
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->schema(fn (BankAccount $record): array => [
                View::make('filament.chakama.bank-entries-table')
                    ->viewData([
                        'entries' => $record->ledgerEntries()
                            ->whereIn('document_type', $types)
                            ->orderByDesc('posting_date')
                            ->get(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query
                ->withSum('ledgerEntries', 'amount')
                ->withSum(
                    ['ledgerEntries as receipts_sum' => fn ($q) => $q->whereIn('document_type', ['receipt', 'income'])],
                    'amount'
                )
                ->withSum(
                    ['ledgerEntries as disbursements_sum' => fn ($q) => $q->whereIn('document_type', ['payment', 'expense'])],
                    'amount'
                )
            )
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('bank_account_no')
                    ->label('Account No')
                    ->placeholder('—'),
                TextColumn::make('ledger_entries_sum_amount')
                    ->label('Balance (KES)')
                    ->money('KES')
                    ->sortable()
                    ->color(fn (BankAccount $record): string => (float) $record->ledger_entries_sum_amount >= 0 ? 'success' : 'danger'),
                TextColumn::make('receipts_sum')
                    ->label('Receipts (KES)')
                    ->money('KES')
                    ->sortable()
                    ->color('success')
                    ->action(static::entriesAside('receipt')),
                TextColumn::make('disbursements_sum')
                    ->label('Disbursements (KES)')
                    ->money('KES')
                    ->sortable()
                    ->color('danger')
                    ->action(static::entriesAside('payment')),
            ])
            ->defaultSort('name')
            ->filters([])
            ->recordActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFundsBankAccounts::route('/'),
        ];
    }
}
