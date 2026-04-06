<?php

namespace App\Filament\Member\Resources\MyInvoices;

use App\Filament\Member\Resources\MyInvoices\Pages\ListMyInvoices;
use App\Filament\Member\Resources\MyInvoices\Tables\MyInvoicesTable;
use App\Models\Finance\Customer;
use App\Models\Finance\CustomerLedgerEntry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MyInvoicesResource extends Resource
{
    protected static ?string $model = CustomerLedgerEntry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'My Bills';

    protected static \UnitEnum|string|null $navigationGroup = 'My Finances';

    protected static ?string $modelLabel = 'Invoice';

    protected static ?string $pluralModelLabel = 'Pending Bills';

    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        $member = auth()->user()?->member;
        $customer = $member?->customer_no
            ? Customer::where('no', $member->customer_no)->first()
            : null;

        return parent::getEloquentQuery()
            ->where('document_type', 'invoice')
            ->where('is_open', true)
            ->when($customer, fn ($q) => $q->where('customer_id', $customer->id))
            ->unless($customer, fn ($q) => $q->whereRaw('1 = 0'));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return MyInvoicesTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMyInvoices::route('/'),
        ];
    }
}
