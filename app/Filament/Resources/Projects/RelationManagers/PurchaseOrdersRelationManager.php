<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use App\Models\Finance\PurchaseHeader;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PurchaseOrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'purchaseOrders';

    protected static ?string $title = 'Purchase Orders';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('no')
            ->columns([
                TextColumn::make('no')
                    ->label('PO No')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('vendor.name')
                    ->label('Vendor')
                    ->searchable(),
                TextColumn::make('posting_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('total_amount')
                    ->label('Total (KES)')
                    ->state(fn (PurchaseHeader $record): float => (float) $record->purchaseLines->sum('line_amount'))
                    ->money('KES'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Action::make('create_purchase_order')
                    ->label('New Purchase Invoice')
                    ->icon('heroicon-o-shopping-cart')
                    ->url(fn (): string => route('filament.sbf.resources.finance.purchase-headers.create', [
                        'project' => $this->getOwnerRecord()->id,
                    ])),
            ])
            ->recordActions([
                Action::make('open_document')
                    ->label('Open')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (PurchaseHeader $record): string => route('filament.sbf.resources.finance.purchase-headers.edit', [
                        'record' => $record,
                    ])),
            ]);
    }
}
