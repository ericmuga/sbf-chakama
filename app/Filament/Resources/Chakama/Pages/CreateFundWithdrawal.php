<?php

namespace App\Filament\Resources\Chakama\Pages;

use App\Filament\Resources\Chakama\FundWithdrawalResource;
use App\Models\FundAccount;
use App\Services\FundWithdrawalService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateFundWithdrawal extends CreateRecord
{
    protected static string $resource = FundWithdrawalResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $fund = FundAccount::findOrFail($data['fund_account_id']);
        $service = app(FundWithdrawalService::class);

        return $service->createWithdrawal($fund, $data, auth()->user());
    }
}
