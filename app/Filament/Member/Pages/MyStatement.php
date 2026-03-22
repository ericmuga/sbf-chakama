<?php

namespace App\Filament\Member\Pages;

use App\Models\Finance\Customer;
use App\Models\Finance\CustomerLedgerEntry;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class MyStatement extends Page
{
    protected string $view = 'filament.member.pages.my-statement';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static ?string $navigationLabel = 'My Statement';

    protected static ?string $title = 'My Statement';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public function getEntries(): Collection
    {
        $member = auth()->user()?->member;

        if (! $member?->customer_no) {
            return collect();
        }

        $customer = Customer::where('no', $member->customer_no)->first();

        if (! $customer) {
            return collect();
        }

        return CustomerLedgerEntry::where('customer_id', $customer->id)
            ->when($this->dateFrom, fn ($q) => $q->whereDate('posting_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('posting_date', '<=', $this->dateTo))
            ->orderByDesc('posting_date')
            ->get();
    }
}
