<?php

namespace App\Filament\Resources\Finance\CustomerResource\Pages;

use App\Filament\Resources\Finance\CustomerResource;
use App\Models\Finance\NumberSeries;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $code = CustomerResource::numberSeriesCode();

        if (blank($code)) {
            Notification::make()
                ->title('No customer number series configured')
                ->body('Set the customer number series in Sales Setup before creating customers.')
                ->danger()
                ->send();

            throw new Halt;
        }

        return DB::transaction(function () use ($data, $code): Model {
            $no = NumberSeries::generate($code);

            if (blank($no)) {
                Notification::make()
                    ->title('Customer number series is inactive')
                    ->body('Activate the customer number series in Number Series setup.')
                    ->danger()
                    ->send();

                throw new Halt;
            }

            $data['no'] = $no;

            return static::getModel()::create($data);
        });
    }
}
