<?php

namespace App\Filament\Resources\Releases\Schemas;

use App\Enums\ReleaseStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ReleaseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('version')
                    ->placeholder('e.g. v1.2.0')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('name')
                    ->label('Release Name')
                    ->maxLength(255),
                Select::make('status')
                    ->options(ReleaseStatus::class)
                    ->default('planned')
                    ->required(),
                DatePicker::make('released_on'),
                Textarea::make('notes')
                    ->label('Release Notes / Changelog')
                    ->rows(6)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }
}
