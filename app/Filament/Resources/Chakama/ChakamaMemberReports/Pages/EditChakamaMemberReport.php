<?php

namespace App\Filament\Resources\Chakama\ChakamaMemberReports\Pages;

use App\Filament\Resources\Chakama\ChakamaMemberReports\ChakamaMemberReportResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditChakamaMemberReport extends EditRecord
{
    protected static string $resource = ChakamaMemberReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
