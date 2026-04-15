<?php

namespace App\Filament\Resources\Chakama\Pages;

use App\Filament\Resources\Chakama\ShareSubscriptionResource;
use App\Models\Member;
use App\Models\ShareSubscription;
use App\Services\ShareBillingService;
use App\Services\ShareService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateShareSubscription extends CreateRecord
{
    protected static string $resource = ShareSubscriptionResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $shareService = app(ShareService::class);
        $billingService = app(ShareBillingService::class);

        $memberIds = $data['all_members']
            ? Member::where('is_chakama', true)->where('member_status', 'active')->pluck('id')->all()
            : ($data['member_ids'] ?? []);

        $subscribeOn = Carbon::parse($data['subscribed_at']);
        $isImmediate = $subscribeOn->lte(today());

        $created = 0;
        $lastSubscription = null;

        foreach ($memberIds as $memberId) {
            $member = Member::find($memberId);

            if (! $member) {
                continue;
            }

            $subscription = $shareService->subscribe($member, $data);
            $lastSubscription = $subscription;
            $created++;

            if ($isImmediate) {
                try {
                    $billingService->generateInvoice($subscription);
                } catch (\Throwable $e) {
                    Notification::make()
                        ->warning()
                        ->title("Invoice failed for {$member->name}: {$e->getMessage()}")
                        ->send();
                }
            }
        }

        if ($created === 0) {
            $this->halt();
        }

        $this->successNotificationTitle = $isImmediate
            ? "Created and invoiced {$created} subscription(s) immediately."
            : "Scheduled {$created} subscription(s) for {$subscribeOn->format('d M Y')}. Invoices will be generated on that date.";

        return $lastSubscription ?? ShareSubscription::latest()->first();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
