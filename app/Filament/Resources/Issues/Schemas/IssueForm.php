<?php

namespace App\Filament\Resources\Issues\Schemas;

use App\Enums\IssueCategory;
use App\Enums\IssuePortal;
use App\Enums\IssueStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class IssueForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Issue')
                    ->schema([
                        TextInput::make('title')
                            ->label('Issue')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Select::make('portal_type')
                            ->label('Portal')
                            ->options(IssuePortal::class)
                            ->default('sbf')
                            ->required(),
                        Select::make('category')
                            ->label('Type')
                            ->options(IssueCategory::class)
                            ->default('development')
                            ->required(),
                        Textarea::make('details')
                            ->label('Issue Details')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Assignment & Tracking')
                    ->schema([
                        TextInput::make('issue_owner')
                            ->label('Issue Owner')
                            ->maxLength(255),
                        TextInput::make('resource')
                            ->label('Resource')
                            ->maxLength(255),
                        DatePicker::make('date_assigned'),
                        DatePicker::make('date_actioned')
                            ->label('Date Actioned'),
                        Select::make('status')
                            ->options(IssueStatus::class)
                            ->default('open')
                            ->required(),
                        DatePicker::make('closure_date')
                            ->label('Date Closed'),
                        DatePicker::make('reviewed_date'),
                        TextInput::make('qa_test_result')
                            ->label('QA Test Result')
                            ->maxLength(255),
                        Select::make('release_id')
                            ->label('Release')
                            ->relationship('release', 'version')
                            ->searchable()
                            ->preload(),
                        Textarea::make('comments')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
