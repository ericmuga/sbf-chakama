<?php

namespace App\Filament\Member\Resources\MyProjects\Pages;

use App\Filament\Member\Resources\MyProjects\MyProjectsResource;
use Filament\Resources\Pages\ListRecords;

class ListMyProjects extends ListRecords
{
    protected static string $resource = MyProjectsResource::class;
}
