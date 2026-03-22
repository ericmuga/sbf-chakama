<?php

namespace App\Filament\Resources\Finance\ScheduledInvoices\ScheduledInvoices;

use App\Filament\Resources\Finance\ScheduledInvoices\ScheduledInvoices\Pages\CreateScheduledInvoice;
use App\Filament\Resources\Finance\ScheduledInvoices\ScheduledInvoices\Pages\EditScheduledInvoice;
use App\Filament\Resources\Finance\ScheduledInvoices\ScheduledInvoices\Pages\ListScheduledInvoices;
use App\Filament\Resources\Finance\ScheduledInvoices\ScheduledInvoices\Schemas\ScheduledInvoiceForm;
use App\Filament\Resources\Finance\ScheduledInvoices\ScheduledInvoices\Tables\ScheduledInvoicesTable;
use App\Models\Finance\ScheduledInvoices\ScheduledInvoice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ScheduledInvoiceResource extends Resource
{
    protected static ?string $model = ScheduledInvoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ScheduledInvoiceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ScheduledInvoicesTable::configure($table);
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
            'index' => ListScheduledInvoices::route('/'),
            'create' => CreateScheduledInvoice::route('/create'),
            'edit' => EditScheduledInvoice::route('/{record}/edit'),
        ];
    }
}
