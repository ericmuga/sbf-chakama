<?php

namespace App\Filament\Resources\Finance\BankPostingGroups\Schemas;

use App\Models\Finance\GlAccount;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BankPostingGroupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true),
                TextInput::make('description')
                    ->required()
                    ->maxLength(255),
                Select::make('bank_account_gl_no')
                    ->label('Bank G/L Account')
                    ->helperText('The G/L account that receipts and payments using this group will post to.')
                    ->options(fn (): array => GlAccount::query()
                        ->orderBy('no')
                        ->get()
                        ->mapWithKeys(fn (GlAccount $account): array => [
                            $account->no => "{$account->no} · {$account->name}",
                        ])
                        ->all())
                    ->searchable()
                    ->required(),
            ]);
    }
}
