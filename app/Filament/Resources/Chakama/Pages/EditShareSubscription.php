<?php

namespace App\Filament\Resources\Chakama\Pages;

use App\Filament\Resources\Chakama\ShareSubscriptionResource;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EditShareSubscription extends EditRecord
{
    protected static string $resource = ShareSubscriptionResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Share Allocation')
                    ->description('Update the number of shares allocated to this member for this billing schedule.')
                    ->schema([
                        TextInput::make('number_of_shares')
                            ->label('Number of Shares Allocated')
                            ->integer()
                            ->minValue(1)
                            ->required()
                            ->helperText('This is the multiplier used when billing runs are generated for the linked schedule.'),
                    ]),
            ]);
    }
}
