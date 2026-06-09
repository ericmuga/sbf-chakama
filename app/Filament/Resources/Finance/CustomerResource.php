<?php

namespace App\Filament\Resources\Finance;

use App\Filament\Resources\Finance\CustomerResource\Pages\CreateCustomer;
use App\Filament\Resources\Finance\CustomerResource\Pages\EditCustomer;
use App\Filament\Resources\Finance\CustomerResource\Pages\ListCustomers;
use App\Models\Finance\Customer;
use App\Models\Finance\CustomerLedgerEntry;
use App\Models\Finance\NumberSeries;
use App\Models\Finance\SalesSetup;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static UnitEnum|string|null $navigationGroup = 'Finance — Setup';

    protected static ?int $navigationSort = 140;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('no')
                    ->label('Customer No')
                    ->default(fn (): string => NumberSeries::preview(static::numberSeriesCode() ?? ''))
                    ->placeholder('Assigned automatically on save')
                    ->disabled()
                    ->dehydrated(false)
                    ->helperText('Generated from the customer number series when the record is saved.'),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('customer_posting_group_id')
                    ->label('Customer Posting Group')
                    ->relationship('customerPostingGroup', 'description')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('payment_terms_code')
                    ->label('Payment Terms')
                    ->relationship('paymentTerms', 'description')
                    ->searchable()
                    ->preload(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withSum('customerLedgerEntries as balance_sum', 'amount'))
            ->columns([
                TextColumn::make('no')
                    ->label('Customer No')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customerPostingGroup.description')
                    ->label('Posting Group')
                    ->badge(),
                TextColumn::make('payment_terms_code')
                    ->label('Payment Terms'),
                TextColumn::make('balance_sum')
                    ->label('Balance (KES)')
                    ->badge()
                    ->color(fn ($state): string => (float) $state > 0 ? 'danger' : ((float) $state < 0 ? 'success' : 'gray'))
                    ->formatStateUsing(fn ($state): string => number_format(abs((float) $state), 2).((float) $state > 0 ? ' DR' : ((float) $state < 0 ? ' CR' : '')))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('customer_posting_group_id')
                    ->label('Posting Group')
                    ->relationship('customerPostingGroup', 'description')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('with_balance')
                    ->label('Outstanding balance')
                    ->placeholder('All')
                    ->trueLabel('With balance')
                    ->falseLabel('Zero balance')
                    ->queries(
                        true: fn (Builder $q): Builder => $q->whereIn('id', static::customerIdsWithBalance()),
                        false: fn (Builder $q): Builder => $q->whereNotIn('id', static::customerIdsWithBalance()),
                        blank: fn (Builder $q): Builder => $q,
                    ),
            ])
            ->defaultSort('no')
            ->headerActions([
                Action::make('exportCustomersExcel')
                    ->label('Export to Excel')
                    ->icon(Heroicon::ArrowDownTray)
                    ->color('success')
                    ->url(fn ($livewire): string => route('admin.reports.customers.export-excel', array_filter([
                        'posting_group' => $livewire->getTableFilterState('customer_posting_group_id')['value'] ?? null,
                        'with_balance' => ($livewire->getTableFilterState('with_balance')['value'] ?? null) === true ? 1 : null,
                    ])))
                    ->openUrlInNewTab(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'create' => CreateCustomer::route('/create'),
            'edit' => EditCustomer::route('/{record}/edit'),
        ];
    }

    public static function numberSeriesCode(): ?string
    {
        return SalesSetup::query()->value('customer_nos');
    }

    protected static function customerIdsWithBalance(): Builder
    {
        return CustomerLedgerEntry::query()
            ->groupBy('customer_id')
            ->havingRaw('SUM(amount) <> 0')
            ->select('customer_id');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        if (! (auth()->user()?->isAdmin() ?? false)) {
            return false;
        }

        return ! $record->customerLedgerEntries()->exists();
    }
}
