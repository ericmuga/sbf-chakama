<?php

namespace App\Filament\Resources\Finance\BankAccounts\Tables;

use App\Models\Finance\BankAccount;
use App\Models\Finance\GlAccount;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BankAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->searchable()->sortable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('bank_account_no')->label('Account No'),
                TextColumn::make('bankPostingGroup.description')->label('Posting Group')->badge(),
                TextColumn::make('bank_gl_account')
                    ->label('Bank G/L Account')
                    ->placeholder('Not mapped')
                    ->state(function (BankAccount $record): ?string {
                        $glNo = $record->bankPostingGroup?->bank_account_gl_no;

                        if (! $glNo) {
                            return null;
                        }

                        $name = GlAccount::where('no', $glNo)->value('name');

                        return $name ? "{$glNo} · {$name}" : $glNo;
                    }),
                TextColumn::make('currency_code')->label('Currency'),
            ])
            ->defaultSort('code')
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
