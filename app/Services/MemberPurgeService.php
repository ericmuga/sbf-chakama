<?php

namespace App\Services;

use App\Models\Claim;
use App\Models\Dependant;
use App\Models\Document;
use App\Models\Finance\CashReceipt;
use App\Models\Finance\Customer;
use App\Models\Finance\CustomerLedgerEntry;
use App\Models\Finance\Vendor;
use App\Models\Finance\VendorLedgerEntry as FinanceVendorLedgerEntry;
use App\Models\Member;
use App\Models\MemberLedgerEntry;
use App\Models\NextOfKin;
use App\Models\Notification;
use App\Models\PostedSalesHeader;
use App\Models\SalesHeader;
use App\Models\ShareNominee;
use App\Models\ShareSubscription;
use App\Models\User;
use App\Models\VendorLedgerEntry;
use Illuminate\Support\Facades\DB;

class MemberPurgeService
{
    /**
     * Delete all members and everything tied to them, keeping admin logins.
     *
     * Deletes are ordered so that foreign-key constraints are respected:
     * records that reference members (or member-owned finance accounts) with
     * a RESTRICT rule are removed first; CASCADE children are removed
     * automatically when their parent is deleted.
     *
     * @return int number of members deleted
     */
    public function purge(): int
    {
        return DB::transaction(function (): int {
            $members = Member::query()->where('type', 'member')->get(['id', 'no', 'customer_no', 'vendor_no', 'user_id']);

            if ($members->isEmpty()) {
                return 0;
            }

            $memberIds = $members->pluck('id')->all();
            $memberNos = $members->pluck('no')->filter()->values()->all();
            $customerNos = $members->pluck('customer_no')->filter()->values()->all();
            $vendorNos = $members->pluck('vendor_no')->filter()->values()->all();
            $userIds = $members->pluck('user_id')->filter()->values()->all();

            $customerIds = Customer::query()->whereIn('no', $customerNos)->pluck('id')->all();
            $vendorIds = Vendor::query()->whereIn('no', $vendorNos)->pluck('id')->all();

            $customerLedgerIds = CustomerLedgerEntry::query()->whereIn('customer_id', $customerIds)->pluck('id')->all();
            $vendorLedgerIds = FinanceVendorLedgerEntry::query()->whereIn('vendor_id', $vendorIds)->pluck('id')->all();

            $this->deleteApplications('customer_applications', $customerLedgerIds);
            $this->deleteApplications('vendor_applications', $vendorLedgerIds);

            CashReceipt::query()->whereIn('customer_id', $customerIds)->delete();
            CustomerLedgerEntry::query()->whereIn('customer_id', $customerIds)->delete();
            FinanceVendorLedgerEntry::query()->whereIn('vendor_id', $vendorIds)->delete();
            VendorLedgerEntry::query()->whereIn('vendor_no', $vendorNos)->delete();
            MemberLedgerEntry::query()->whereIn('member_no', $memberNos)->delete();

            Notification::query()->whereIn('member_no', $memberNos)->delete();
            SalesHeader::query()->whereIn('member_no', $memberNos)->delete();
            PostedSalesHeader::query()->whereIn('member_no', $memberNos)->delete();

            Claim::query()->whereIn('member_id', $memberIds)->forceDelete();
            ShareNominee::query()->whereIn('member_id', $memberIds)->delete();
            ShareSubscription::query()->whereIn('member_id', $memberIds)->forceDelete();
            Dependant::query()->whereIn('member_id', $memberIds)->delete();
            NextOfKin::query()->whereIn('member_id', $memberIds)->delete();

            Document::query()
                ->where('documentable_type', Member::class)
                ->whereIn('documentable_id', $memberIds)
                ->delete();

            Customer::query()->whereIn('id', $customerIds)->delete();
            Vendor::query()->whereIn('id', $vendorIds)->delete();

            $deleted = Member::query()->whereIn('id', $memberIds)->delete();

            User::query()
                ->whereIn('id', $userIds)
                ->where('is_admin', false)
                ->delete();

            return $deleted;
        });
    }

    /**
     * Remove ledger application rows that reference any of the given ledger entry ids.
     *
     * @param  array<int, int>  $ledgerEntryIds
     */
    private function deleteApplications(string $table, array $ledgerEntryIds): void
    {
        if (empty($ledgerEntryIds)) {
            return;
        }

        DB::table($table)
            ->whereIn('payment_entry_id', $ledgerEntryIds)
            ->orWhereIn('invoice_entry_id', $ledgerEntryIds)
            ->delete();
    }
}
