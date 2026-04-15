<?php

namespace App\Filament\Resources\Finance\VendorPayments;

use App\Filament\Resources\Finance\VendorPayments\Pages\CreateVendorPayment;
use App\Filament\Resources\Finance\VendorPayments\Pages\EditVendorPayment;
use App\Filament\Resources\Finance\VendorPayments\Pages\ListVendorPayments;
use App\Filament\Resources\Finance\VendorPayments\Pages\ViewVendorPayment;
use App\Filament\Resources\Finance\VendorPayments\Schemas\VendorPaymentForm;
use App\Filament\Resources\Finance\VendorPayments\Tables\VendorPaymentsTable;
use App\Models\Finance\VendorPayment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class VendorPaymentResource extends Resource
{
    protected static ?string $model = VendorPayment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static UnitEnum|string|null $navigationGroup = 'Finance — Expenses & Claims';

    protected static ?string $modelLabel = 'Payment';

    protected static ?string $pluralModelLabel = 'Payments';

    protected static ?string $navigationLabel = 'Disbursements';

    protected static ?int $navigationSort = 20;

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
        return (auth()->user()?->isAdmin() ?? false) && strtolower($record->status) !== 'posted';
    }

    public static function canDelete(Model $record): bool
    {
        return (auth()->user()?->isAdmin() ?? false) && strtolower($record->status) !== 'posted';
    }

    public static function form(Schema $schema): Schema
    {
        return VendorPaymentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VendorPaymentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVendorPayments::route('/'),
            'create' => CreateVendorPayment::route('/create'),
            'view' => ViewVendorPayment::route('/{record}'),
            'edit' => EditVendorPayment::route('/{record}/edit'),
        ];
    }
}
