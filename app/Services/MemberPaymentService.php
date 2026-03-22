<?php

namespace App\Services;

use App\Events\MemberPaymentReceived;
use App\Models\Finance\BankAccount;
use App\Models\Finance\CashReceipt;
use App\Models\Finance\Customer;
use App\Models\Finance\CustomerLedgerEntry;
use App\Models\Finance\CustomerPostingGroup;
use App\Models\Finance\NumberSeries;
use App\Models\Finance\SalesSetup;
use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MemberPaymentService
{
    public function initiatePayment(Member $member, float $amount, string $description, ?int $bankAccountId = null): CashReceipt
    {
        return DB::transaction(function () use ($member, $amount, $bankAccountId) {
            $customer = $this->ensureCustomerForMember($member);

            // Use default bank account if not specified
            if (! $bankAccountId) {
                $bankAccountId = BankAccount::first()?->id;
            }

            $receipt = CashReceipt::create([
                'customer_id' => $customer->id,
                'bank_account_id' => $bankAccountId,
                'posting_date' => today(),
                'amount' => $amount,
                'status' => 'Open',
            ]);

            MemberPaymentReceived::dispatch($receipt, $member);

            return $receipt;
        });
    }

    public function recordMpesaCallback(string $transactionId, float $amount, string $phone): CashReceipt
    {
        $member = Member::where('mpesa_phone', $phone)->firstOrFail();

        return $this->initiatePayment($member, $amount, "M-PESA {$transactionId}");
    }

    /**
     * @return Collection<int, CustomerLedgerEntry>
     */
    public function getMemberStatement(Member $member, ?Carbon $from = null, ?Carbon $to = null): Collection
    {
        if (! $member->customer_no) {
            return collect();
        }

        $customer = Customer::where('no', $member->customer_no)->first();
        if (! $customer) {
            return collect();
        }

        $query = CustomerLedgerEntry::where('customer_id', $customer->id)
            ->orderBy('posting_date', 'desc');

        if ($from) {
            $query->whereDate('posting_date', '>=', $from);
        }

        if ($to) {
            $query->whereDate('posting_date', '<=', $to);
        }

        return $query->get();
    }

    private function ensureCustomerForMember(Member $member): Customer
    {
        if ($member->customer_no) {
            $customer = Customer::where('no', $member->customer_no)->first();
            if ($customer) {
                return $customer;
            }
        }

        $salesSetup = SalesSetup::first();
        $cpg = CustomerPostingGroup::where('code', 'MEMBER')->first();
        $displayName = $member->name ?? $member->user?->name ?? $member->no;

        $customerNo = $salesSetup?->customer_nos
            ? NumberSeries::generate($salesSetup->customer_nos)
            : 'CUS-'.$member->id;

        $customer = Customer::create([
            'no' => $customerNo,
            'name' => $displayName,
            'customer_posting_group_id' => $cpg?->id,
        ]);

        $member->updateQuietly(['customer_no' => $customerNo]);

        return $customer;
    }
}
