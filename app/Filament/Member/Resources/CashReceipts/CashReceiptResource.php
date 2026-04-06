<?php

namespace App\Filament\Member\Resources\CashReceipts;

use App\Filament\Member\Resources\CashReceipts\Pages\ListCashReceipts;
use App\Filament\Member\Resources\CashReceipts\Pages\ViewCashReceipt;
use App\Filament\Member\Resources\CashReceipts\Schemas\CashReceiptForm;
use App\Filament\Member\Resources\CashReceipts\Tables\CashReceiptsTable;
use App\Models\Finance\CashReceipt;
use App\Models\Finance\Customer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CashReceiptResource extends Resource
{
    protected static ?string $model = CashReceipt::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'My Payments';

    protected static \UnitEnum|string|null $navigationGroup = 'My Finances';

    public static function getEloquentQuery(): Builder
    {
        $member = auth()->user()?->member;
        $customer = $member?->customer_no
            ? Customer::where('no', $member->customer_no)->first()
            : null;

        return parent::getEloquentQuery()
            ->when($customer, fn ($q) => $q->where('customer_id', $customer->id))
            ->unless($customer, fn ($q) => $q->whereRaw('1 = 0'));
    }

    public static function form(Schema $schema): Schema
    {
        return CashReceiptForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CashReceiptsTable::configure($table);
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
            'index' => ListCashReceipts::route('/'),
            'view' => ViewCashReceipt::route('/{record}'),
        ];
    }
}
