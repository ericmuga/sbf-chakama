<?php

namespace App\Filament\Member\Resources\Profile\Pages;

use App\Filament\Member\Resources\Profile\MyDocumentsResource;
use App\Models\Member;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMyDocuments extends ListRecords
{
    protected static string $resource = MyDocumentsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->mutateFormDataUsing(function (array $data): array {
                    $member = auth()->user()?->member;

                    $data['documentable_type'] = Member::class;
                    $data['documentable_id'] = $member?->id;

                    return $data;
                }),
        ];
    }
}
