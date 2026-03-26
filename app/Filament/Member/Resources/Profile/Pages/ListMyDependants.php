<?php

namespace App\Filament\Member\Resources\Profile\Pages;

use App\Filament\Member\Resources\Profile\MyDependantsResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMyDependants extends ListRecords
{
    protected static string $resource = MyDependantsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->mutateFormDataUsing(function (array $data): array {
                    $data['member_id'] = auth()->user()->member->id;

                    return $data;
                }),
        ];
    }
}
