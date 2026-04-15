<?php

namespace App\Services\Reports;

use App\Models\Finance\Customer;
use App\Models\Finance\CustomerLedgerEntry;
use App\Models\Member;
use Illuminate\Support\Collection;

class MemberStatementService
{
    /**
     * @return array{
     *     member: Member,
     *     customer: ?Customer,
     *     opening: float,
     *     entries: Collection,
     *     total_debits: float,
     *     total_credits: float,
     *     closing: float,
     *     date_from: ?string,
     *     date_to: ?string,
     * }
     */
    public function build(Member $member, ?string $dateFrom, ?string $dateTo): array
    {
        $customer = $member->customer_no
            ? Customer::where('no', $member->customer_no)->first()
            : null;

        if (! $customer) {
            return $this->empty($member, $dateFrom, $dateTo);
        }

        $opening = $dateFrom
            ? (float) CustomerLedgerEntry::where('customer_id', $customer->id)
                ->whereDate('posting_date', '<', $dateFrom)
                ->sum('amount')
            : 0.0;

        $rows = CustomerLedgerEntry::where('customer_id', $customer->id)
            ->when($dateFrom, fn ($q) => $q->whereDate('posting_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('posting_date', '<=', $dateTo))
            ->orderBy('posting_date')
            ->orderBy('entry_no')
            ->get();

        $running = $opening;

        $entries = $rows->map(function (CustomerLedgerEntry $entry) use (&$running) {
            $running += (float) $entry->amount;
            $entry->running_balance = $running;

            return $entry;
        });

        $totalDebits = (float) $rows->where('amount', '>', 0)->sum('amount');
        $totalCredits = (float) $rows->where('amount', '<', 0)->sum('amount');

        return [
            'member' => $member,
            'customer' => $customer,
            'opening' => $opening,
            'entries' => $entries,
            'total_debits' => $totalDebits,
            'total_credits' => abs($totalCredits),
            'closing' => $opening + $totalDebits + $totalCredits,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }

    private function empty(Member $member, ?string $dateFrom, ?string $dateTo): array
    {
        return [
            'member' => $member,
            'customer' => null,
            'opening' => 0.0,
            'entries' => collect(),
            'total_debits' => 0.0,
            'total_credits' => 0.0,
            'closing' => 0.0,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }
}
