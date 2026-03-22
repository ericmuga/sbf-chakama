<?php

namespace App\Filament\Resources\ClaimApprovalTemplates\Schemas;

use App\Enums\ClaimType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ClaimApprovalTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                Select::make('claim_type')
                    ->options(ClaimType::class),
                Toggle::make('is_default')
                    ->required(),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
