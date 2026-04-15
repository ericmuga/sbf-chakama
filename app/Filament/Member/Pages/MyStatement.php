<?php

namespace App\Filament\Member\Pages;

use App\Models\Finance\Customer;
use App\Models\Finance\CustomerLedgerEntry;
use Carbon\Carbon;
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

    /**
     * Returns entries enriched with a per-row running_balance.
     *
     * @return Collection<int, object{
     *     posting_date: Carbon,
     *     document_type: string,
     *     document_no: string,
     *     amount: float,
     *     running_balance: float,
     * }>
     */
    public function getEntries(): Collection
    {
        $customer = $this->resolveCustomer();

        if (! $customer) {
            return collect();
        }

        // Opening balance = sum of all entries before the date range
        $openingBalance = $this->dateFrom
            ? (float) CustomerLedgerEntry::where('customer_id', $customer->id)
                ->whereDate('posting_date', '<', $this->dateFrom)
                ->sum('amount')
            : 0.0;

        $entries = CustomerLedgerEntry::where('customer_id', $customer->id)
            ->when($this->dateFrom, fn ($q) => $q->whereDate('posting_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('posting_date', '<=', $this->dateTo))
            ->orderBy('posting_date')
            ->orderBy('entry_no')
            ->get();

        $running = $openingBalance;

        return $entries->map(function (CustomerLedgerEntry $entry) use (&$running) {
            $running += (float) $entry->amount;
            $entry->running_balance = $running;

            return $entry;
        });
    }

    /**
     * Summary totals for the footer row.
     *
     * @return array{opening: float, total_debits: float, total_credits: float, closing: float}
     */
    public function getTotals(): array
    {
        $customer = $this->resolveCustomer();

        if (! $customer) {
            return ['opening' => 0, 'total_debits' => 0, 'total_credits' => 0, 'closing' => 0];
        }

        $opening = $this->dateFrom
            ? (float) CustomerLedgerEntry::where('customer_id', $customer->id)
                ->whereDate('posting_date', '<', $this->dateFrom)
                ->sum('amount')
            : 0.0;

        $entries = CustomerLedgerEntry::where('customer_id', $customer->id)
            ->when($this->dateFrom, fn ($q) => $q->whereDate('posting_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('posting_date', '<=', $this->dateTo))
            ->get();

        $totalDebits = (float) $entries->where('amount', '>', 0)->sum('amount');
        $totalCredits = (float) $entries->where('amount', '<', 0)->sum('amount');

        return [
            'opening' => $opening,
            'total_debits' => $totalDebits,
            'total_credits' => abs($totalCredits),
            'closing' => $opening + $totalDebits + $totalCredits,
        ];
    }

    private function resolveCustomer(): ?Customer
    {
        $member = auth()->user()?->member;

        return $member?->customer_no
            ? Customer::where('no', $member->customer_no)->first()
            : null;
    }
}
