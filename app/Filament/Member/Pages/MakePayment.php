<?php

namespace App\Filament\Member\Pages;

use App\Filament\Member\Resources\CashReceipts\CashReceiptResource;
use App\Models\Finance\BankAccount;
use App\Models\Finance\CashReceipt;
use App\Models\Finance\CustomerLedgerEntry;
use App\Models\Finance\MpesaTransaction;
use App\Models\Finance\PaymentMethod;
use App\Services\Finance\MpesaService;
use App\Services\Finance\ReceiptPostingService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class MakePayment extends Page
{
    protected string $view = 'filament.member.pages.make-payment';

    protected static \BackedEnum|string|null $navigationIcon = null;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'make-payment';

    protected static ?string $title = 'Make Payment';

    // ─── Step: form | pending | confirmed ───────────────────────────────────

    public string $step = 'form';

    public bool $isLocalMode = false;

    // ─── Step 1: payment form ────────────────────────────────────────────────

    public string $phone = '';

    public string $amount = '';

    // ─── Step 2: pending (awaiting M-Pesa PIN) ───────────────────────────────

    public ?string $checkoutRequestId = null;

    // ─── Step 3: confirmed ───────────────────────────────────────────────────

    public ?string $mpesaReceiptNo = null;

    public ?string $confirmedAmount = null;

    public ?string $confirmedPhone = null;

    public ?string $confirmedAt = null;

    /** @var array<int, array{id: int, document_no: string, due_date: string, remaining_amount: float, amount_applied: string}> */
    public array $openInvoices = [];

    public function mount(): void
    {
        $this->isLocalMode = app(MpesaService::class)->isLocalMode();

        $member = auth()->user()?->member;

        if ($member) {
            $phone = $member->mpesa_phone ?? $member->phone ?? '';

            // Normalise 254XXXXXXXXX → 07XXXXXXXXX for display
            if (str_starts_with($phone, '254') && strlen($phone) === 12) {
                $phone = '0'.substr($phone, 3);
            }

            $this->phone = $phone;
        }
    }

    public function initiateSTKPush(): void
    {
        $this->validate([
            'phone' => ['required', 'string', 'regex:/^(07|01|254)\d{8,9}$/'],
            'amount' => ['required', 'numeric', 'min:1'],
        ], [
            'phone.regex' => 'Enter a valid Safaricom number (07XXXXXXXX or 01XXXXXXXX).',
        ]);

        try {
            $member = auth()->user()->member;
            $result = app(MpesaService::class)->initiateSTKPush(
                $this->phone,
                (float) $this->amount,
                'SBF-'.($member->no ?? 'PAY')
            );

            if (isset($result['CheckoutRequestID'])) {
                $this->checkoutRequestId = $result['CheckoutRequestID'];
                $this->step = 'pending';
            } else {
                Notification::make()
                    ->danger()
                    ->title('Could not initiate M-Pesa payment')
                    ->body($result['errorMessage'] ?? $result['ResponseDescription'] ?? 'Please try again.')
                    ->send();
            }
        } catch (\Throwable $e) {
            Notification::make()->danger()->title('Error: '.$e->getMessage())->send();
        }
    }

    /**
     * Called by wire:poll.3s when step = pending.
     * Checks if Safaricom has posted the callback for our checkout request.
     */
    public function pollForTransaction(): void
    {
        if ($this->step !== 'pending') {
            return;
        }

        $transaction = $this->findCompletedTransaction();

        if (! $transaction) {
            return;
        }

        if ($transaction->result_code !== 0) {
            $this->step = 'form';
            Notification::make()
                ->danger()
                ->title('M-Pesa payment failed')
                ->body($transaction->result_desc ?? 'The payment was not completed.')
                ->send();

            return;
        }

        $this->mpesaReceiptNo = $transaction->TransID;
        $this->confirmedAmount = (string) $transaction->TransAmount;
        $this->confirmedPhone = $transaction->MSISDN;
        $this->confirmedAt = $transaction->TransTime
            ? Carbon::createFromFormat('YmdHis', $transaction->TransTime)?->format('d M Y H:i:s')
            : now()->format('d M Y H:i:s');

        $this->step = 'confirmed';
        $this->loadOpenInvoices();
    }

    private function findCompletedTransaction(): ?MpesaTransaction
    {
        // Match by checkout_request_id (preferred)
        if ($this->checkoutRequestId) {
            $tx = MpesaTransaction::where('checkout_request_id', $this->checkoutRequestId)->first();
            if ($tx) {
                return $tx;
            }
        }

        // Fallback: phone + last 5 minutes
        $normalised = app(MpesaService::class)->normalisePhone($this->phone);
        $suffix = substr($normalised, -9);

        return MpesaTransaction::where('MSISDN', 'like', '%'.$suffix)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->where('is_claimed', false)
            ->latest()
            ->first();
    }

    private function loadOpenInvoices(): void
    {
        $customer = auth()->user()?->member?->financeCustomer;

        if (! $customer) {
            return;
        }

        $entries = CustomerLedgerEntry::where('customer_id', $customer->id)
            ->where('document_type', 'invoice')
            ->where('is_open', true)
            ->where('remaining_amount', '>', 0)
            ->orderBy('due_date')
            ->get();

        $remaining = (float) $this->confirmedAmount;

        $this->openInvoices = $entries->map(function ($entry) use (&$remaining) {
            $due = (float) $entry->remaining_amount;
            $applied = min($due, max(0, $remaining));
            $remaining -= $applied;

            return [
                'id' => $entry->id,
                'document_no' => $entry->document_no,
                'due_date' => $entry->due_date?->format('d M Y') ?? '—',
                'remaining_amount' => $due,
                'amount_applied' => $applied > 0 ? (string) $applied : '',
            ];
        })->toArray();
    }

    public function postPayment(): void
    {
        if ($this->step !== 'confirmed' || ! $this->mpesaReceiptNo) {
            return;
        }

        $member = auth()->user()->member;
        $customer = $member->financeCustomer;

        if (! $customer) {
            Notification::make()->danger()->title('Member has no customer account.')->send();

            return;
        }

        // Build allocations from openInvoices state
        $allocations = collect($this->openInvoices)
            ->filter(fn ($inv) => filled($inv['amount_applied']) && (float) $inv['amount_applied'] > 0)
            ->map(fn ($inv) => [
                'customer_ledger_entry_id' => $inv['id'],
                'amount_applied' => (float) $inv['amount_applied'],
            ])
            ->values()
            ->toArray();

        $totalAllocated = collect($allocations)->sum('amount_applied');

        if ($totalAllocated > (float) $this->confirmedAmount) {
            Notification::make()
                ->danger()
                ->title('Allocated total exceeds the received amount of KES '.number_format((float) $this->confirmedAmount, 2))
                ->send();

            return;
        }

        $mpesaMethod = PaymentMethod::where('code', 'MPESA')->first();
        $mpesaBank = BankAccount::where('code', 'MPESA-PAYBILL')->first() ?? BankAccount::first();

        try {
            DB::transaction(function () use ($customer, $mpesaMethod, $mpesaBank, $allocations): void {
                $receipt = CashReceipt::create([
                    'customer_id' => $customer->id,
                    'bank_account_id' => $mpesaBank?->id,
                    'payment_method_id' => $mpesaMethod?->id,
                    'posting_date' => today(),
                    'amount' => $this->confirmedAmount,
                    'description' => 'M-Pesa payment — '.$this->mpesaReceiptNo,
                    'mpesa_receipt_no' => $this->mpesaReceiptNo,
                    'mpesa_phone' => $this->confirmedPhone,
                    'status' => 'Open',
                ]);

                app(ReceiptPostingService::class)->post(
                    $receipt->load(['bankAccount.bankPostingGroup', 'customer.customerPostingGroup']),
                    $allocations
                );

                MpesaTransaction::where('TransID', $this->mpesaReceiptNo)
                    ->update(['is_claimed' => true]);
            });

            Notification::make()
                ->success()
                ->title('Payment posted — '.$this->mpesaReceiptNo)
                ->body('KES '.number_format((float) $this->confirmedAmount, 2).' received on '.$this->confirmedAt)
                ->send();

            $this->redirect(CashReceiptResource::getUrl('index'));
        } catch (\RuntimeException $e) {
            Notification::make()->danger()->title($e->getMessage())->send();
        }
    }

    /**
     * Only available in local mode — bypasses Safaricom and creates a fake transaction
     * so the portal immediately advances to the confirmed step.
     */
    public function simulateLocalPayment(): void
    {
        if (! $this->isLocalMode || ! $this->checkoutRequestId) {
            return;
        }

        app(MpesaService::class)->simulateCompletedTransaction(
            $this->checkoutRequestId,
            $this->phone,
            (float) $this->amount
        );

        // Immediately poll — will find the transaction we just created
        $this->pollForTransaction();
    }

    public function cancelPayment(): void
    {
        $this->step = 'form';
        $this->checkoutRequestId = null;
        $this->mpesaReceiptNo = null;
        $this->confirmedAmount = null;
        $this->openInvoices = [];
    }
}
