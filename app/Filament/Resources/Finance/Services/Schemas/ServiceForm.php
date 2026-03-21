<?php

namespace App\Filament\Resources\Finance\Services\Schemas;

use App\Models\Finance\ServicePostingGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required()
                    ->maxLength(20)
                    ->unique(ignoreRecord: true),
                TextInput::make('description')
                    ->required()
                    ->maxLength(255),
                TextInput::make('unit_price')
                    ->numeric()
                    ->required()
                    ->minValue(0),
                Select::make('service_posting_group_id')
                    ->label('Service Posting Group')
                    ->options(ServicePostingGroup::query()->pluck('description', 'id'))
                    ->searchable()
                    ->required(),
            ]);
    }
}
