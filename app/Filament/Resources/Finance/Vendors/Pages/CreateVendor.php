<?php

namespace App\Filament\Resources\Finance\Vendors\Pages;

use App\Filament\Resources\Finance\Vendors\VendorResource;
use App\Models\Finance\NumberSeries;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateVendor extends CreateRecord
{
    protected static string $resource = VendorResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $code = VendorResource::numberSeriesCode();

        if (blank($code)) {
            Notification::make()
                ->title('No vendor number series configured')
                ->body('Set the vendor number series in Purchase Setup before creating vendors.')
                ->danger()
                ->send();

            throw new Halt;
        }

        return DB::transaction(function () use ($data, $code): Model {
            $no = NumberSeries::generate($code);

            if (blank($no)) {
                Notification::make()
                    ->title('Vendor number series is inactive')
                    ->body('Activate the vendor number series in Number Series setup.')
                    ->danger()
                    ->send();

                throw new Halt;
            }

            $data['no'] = $no;

            return static::getModel()::create($data);
        });
    }
}
