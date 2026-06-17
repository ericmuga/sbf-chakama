<?php

namespace App\Filament\Resources\Finance\BankAccounts\Schemas;

use App\Models\Finance\BankPostingGroup;
use App\Models\Finance\GlAccount;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class BankAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required()
                    ->maxLength(20)
                    ->unique(ignoreRecord: true),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('bank_account_no')
                    ->label('Bank Account No')
                    ->maxLength(50),
                Select::make('bank_posting_group_id')
                    ->label('Bank Posting Group')
                    ->relationship('bankPostingGroup', 'description')
                    ->searchable()
                    ->preload()
                    ->live(),
                Placeholder::make('bank_gl_account')
                    ->label('Mapped Bank G/L Account')
                    ->content(function (Get $get): string {
                        $group = $get('bank_posting_group_id')
                            ? BankPostingGroup::find($get('bank_posting_group_id'))
                            : null;

                        if (! $group?->bank_account_gl_no) {
                            return '— not mapped —';
                        }

                        $name = GlAccount::where('no', $group->bank_account_gl_no)->value('name');

                        return $name ? "{$group->bank_account_gl_no} · {$name}" : $group->bank_account_gl_no;
                    }),
                TextInput::make('currency_code')
                    ->label('Currency Code')
                    ->maxLength(10)
                    ->default('KES'),
            ]);
    }
}
