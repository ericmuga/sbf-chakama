<?php

namespace App\Console\Commands;

use App\Models\Finance\Customer;
use App\Models\Finance\CustomerLedgerEntry;
use App\Models\Member;
use App\Notifications\PaymentDueReminderNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('members:payment-reminders')]
#[Description('Send payment due reminders to SBF members with outstanding invoices.')]
class MemberPaymentReminders extends Command
{
    public function handle(): int
    {
        $count = 0;

        Member::with(['user'])
            ->where('is_sbf', true)
            ->whereNotNull('customer_no')
            ->get()
            ->each(function (Member $member) use (&$count): void {
                if (! $member->user) {
                    return;
                }

                $customer = Customer::where('no', $member->customer_no)->first();

                if (! $customer) {
                    return;
                }

                $dueEntries = CustomerLedgerEntry::where('customer_id', $customer->id)
                    ->where('is_open', true)
                    ->where('document_type', 'Invoice')
                    ->where(fn ($q) => $q->whereNull('due_date')
                        ->orWhere('due_date', '<=', now()->addDays(7))
                    )
                    ->get();

                if ($dueEntries->isEmpty()) {
                    return;
                }

                $outstanding = $dueEntries->sum('remaining_amount');
                $earliestDue = $dueEntries->whereNotNull('due_date')->sortBy('due_date')->first()?->due_date;

                $member->user->notify(new PaymentDueReminderNotification((float) $outstanding, $earliestDue));
                $count++;

                $this->line("Reminded {$member->user->name} — outstanding KES {$outstanding}.");
            });

        $this->info("Sent {$count} payment reminders.");

        return Command::SUCCESS;
    }
}
