<?php

namespace App\Filament\Resources\Finance\BankPostingGroups\Tables;

use App\Models\Finance\BankPostingGroup;
use App\Models\Finance\GlAccount;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BankPostingGroupsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->searchable()->sortable(),
                TextColumn::make('description')->searchable(),
                TextColumn::make('bank_account_gl_no')
                    ->label('Bank G/L Account')
                    ->placeholder('Not mapped')
                    ->state(function (BankPostingGroup $record): ?string {
                        if (! $record->bank_account_gl_no) {
                            return null;
                        }

                        $name = GlAccount::where('no', $record->bank_account_gl_no)->value('name');

                        return $name
                            ? "{$record->bank_account_gl_no} · {$name}"
                            : $record->bank_account_gl_no;
                    }),
                TextColumn::make('bank_accounts_count')
                    ->label('Bank Accounts')
                    ->counts('bankAccounts')
                    ->badge(),
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
