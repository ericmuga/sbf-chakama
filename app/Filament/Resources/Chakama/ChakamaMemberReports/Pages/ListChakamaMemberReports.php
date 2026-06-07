<?php

namespace App\Filament\Resources\Chakama\ChakamaMemberReports\Pages;

use App\Filament\Resources\Chakama\ChakamaMemberReports\ChakamaMemberReportResource;
use Filament\Resources\Pages\ListRecords;

class ListChakamaMemberReports extends ListRecords
{
    protected static string $resource = ChakamaMemberReportResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
