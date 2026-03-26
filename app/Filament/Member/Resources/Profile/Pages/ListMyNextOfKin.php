<?php

namespace App\Filament\Member\Resources\Profile\Pages;

use App\Filament\Member\Resources\Profile\MyNextOfKinResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMyNextOfKin extends ListRecords
{
    protected static string $resource = MyNextOfKinResource::class;

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
