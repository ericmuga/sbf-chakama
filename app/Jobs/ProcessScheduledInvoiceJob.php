<?php

namespace App\Jobs;

use App\Models\Finance\Customer;
use App\Models\Finance\SalesHeader;
use App\Models\Finance\SalesLine;
use App\Models\Finance\ScheduledInvoice;
use App\Models\Member;
use App\Services\Finance\SalesPostingService;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProcessScheduledInvoiceJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $scheduledInvoiceId) {}

    public function handle(SalesPostingService $postingService): void
    {
        $invoice = ScheduledInvoice::findOrFail($this->scheduledInvoiceId);

        if (! $invoice->isProcessable()) {
            return;
        }

        $invoice->update(['status' => 'processing']);

        $members = Member::query()
            ->members()
            ->where('member_status', 'active')
            ->where('exclude_from_billing', false)
            ->whereNotNull('customer_no')
            ->get();

        $totalInvoiced = 0;
        $memberCount = 0;
        $errors = [];

        foreach ($members as $member) {
            $customer = Customer::where('no', $member->customer_no)->first();

            if (! $customer) {
                $errors[] = "Member {$member->no}: no linked customer record.";

                continue;
            }

            try {
                DB::transaction(function () use ($invoice, $customer, $postingService, &$totalInvoiced, &$memberCount): void {
                    $header = SalesHeader::create([
                        'customer_id' => $customer->id,
                        'document_type' => 'invoice',
                        'posting_date' => $invoice->scheduled_date,
                        'due_date' => $invoice->due_date ?? $invoice->scheduled_date,
                        'customer_posting_group_id' => $invoice->customer_posting_group_id,
                        'status' => 'open',
                    ]);

                    SalesLine::create([
                        'sales_header_id' => $header->id,
                        'service_id' => $invoice->service_id,
                        'description' => $invoice->title,
                        'quantity' => 1,
                        'unit_price' => $invoice->amount,
                        'line_amount' => $invoice->amount,
                        'customer_posting_group_id' => $invoice->customer_posting_group_id,
                    ]);

                    $header->load(['customer.customerPostingGroup', 'salesLines.service']);
                    $postingService->post($header);

                    $totalInvoiced += (float) $invoice->amount;
                    $memberCount++;
                });

                if ($invoice->notify_members && $member->user) {
                    $this->notifyMember($member, $invoice);
                }
            } catch (\Throwable $e) {
                $errors[] = "Member {$member->no}: {$e->getMessage()}";
                Log::error("ScheduledInvoice #{$invoice->id} failed for member {$member->no}", ['error' => $e->getMessage()]);
            }
        }

        $invoice->update([
            'status' => empty($errors) ? 'completed' : ($memberCount > 0 ? 'completed' : 'failed'),
            'processed_at' => now(),
            'total_invoiced' => $totalInvoiced,
            'member_count' => $memberCount,
            'error_log' => empty($errors) ? null : implode("\n", $errors),
        ]);

        // Notify the admin who triggered it
        if ($invoice->createdBy) {
            FilamentNotification::make()
                ->title("Scheduled Invoice '{$invoice->title}' processed")
                ->body("{$memberCount} invoices created — KES ".number_format($totalInvoiced, 2).($errors ? ' (with errors)' : ''))
                ->success()
                ->sendToDatabase($invoice->createdBy);
        }
    }

    private function notifyMember(Member $member, ScheduledInvoice $invoice): void
    {
        if (! $member->user) {
            return;
        }

        FilamentNotification::make()
            ->title('New Invoice: '.$invoice->title)
            ->body('Amount due: KES '.number_format((float) $invoice->amount, 2).' — Due: '.$invoice->scheduled_date->format('d M Y'))
            ->info()
            ->sendToDatabase($member->user);

        if ($invoice->send_email && $member->email) {
            try {
                Mail::raw(
                    "Dear {$member->name},\n\nA new invoice has been raised:\n\n"
                    ."Title: {$invoice->title}\nAmount: KES ".number_format((float) $invoice->amount, 2)
                    ."\nDue Date: ".$invoice->scheduled_date->format('d M Y')
                    ."\n\nPlease log in to the member portal to view and pay.\n\nRegards,\nSBF Chakama",
                    fn ($message) => $message
                        ->to($member->email)
                        ->subject('Invoice: '.$invoice->title)
                );
            } catch (\Throwable $e) {
                Log::warning("Could not send email to member {$member->no}: {$e->getMessage()}");
            }
        }
    }
}
