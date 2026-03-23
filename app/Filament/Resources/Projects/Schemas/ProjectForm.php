<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Enums\ProjectModule;
use App\Enums\ProjectPriority;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Project Details')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                        Select::make('module')
                            ->options(ProjectModule::class)
                            ->required(),
                        Select::make('priority')
                            ->options(ProjectPriority::class)
                            ->default('medium'),
                    ]),
                Section::make('Budget & Timeline')
                    ->columns(2)
                    ->schema([
                        TextInput::make('budget')
                            ->numeric()
                            ->prefix('KES')
                            ->required()
                            ->minValue(0),
                        DatePicker::make('start_date')
                            ->label('Start Date'),
                        DatePicker::make('due_date')
                            ->label('Due Date'),
                    ]),
            ]);
    }
}
