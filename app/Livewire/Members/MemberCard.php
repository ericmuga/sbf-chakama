<?php

namespace App\Livewire\Members;

use App\Models\Finance\CashReceipt;
use App\Models\Finance\Customer;
use App\Models\Finance\CustomerLedgerEntry;
use App\Models\Member;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Component;

class MemberCard extends Component
{
    public ?Member $member = null;

    /** @var array<int, array{posting_date: string, document_type: string, document_no: string, debit: float, credit: float, running_balance: float, is_open: bool, pdf_url: ?string}> */
    public array $ledgerEntries = [];

    public float $closingBalance = 0;

    public function mount(): void
    {
        $this->member = auth()->user()?->member()
            ->with(['user', 'dependants', 'nextOfKin'])
            ->first();

        $this->loadLedger();
    }

    private function loadLedger(): void
    {
        if (! $this->member?->customer_no) {
            return;
        }

        $customer = Customer::where('no', $this->member->customer_no)->first();

        if (! $customer) {
            return;
        }

        $entries = CustomerLedgerEntry::where('customer_id', $customer->id)
            ->orderBy('posting_date')
            ->orderBy('entry_no')
            ->get();

        $receiptIdsByNo = CashReceipt::where('customer_id', $customer->id)
            ->whereIn('no', $entries->where('document_type', 'payment')->pluck('document_no')->all())
            ->pluck('id', 'no');

        $running = 0.0;

        $this->ledgerEntries = $entries->map(function (CustomerLedgerEntry $entry) use (&$running, $receiptIdsByNo) {
            $amount = (float) $entry->amount;
            $running += $amount;

            return [
                'posting_date' => $entry->posting_date?->format('d M Y') ?? '—',
                'due_date' => $entry->due_date?->format('d M Y') ?? '—',
                'document_type' => ucfirst($entry->document_type ?? ''),
                'document_no' => $entry->document_no ?? '—',
                'debit' => $amount > 0 ? $amount : 0.0,
                'credit' => $amount < 0 ? abs($amount) : 0.0,
                'running_balance' => $running,
                'is_open' => (bool) $entry->is_open,
                'pdf_url' => $this->resolvePdfUrl($entry->document_type, $entry->document_no, $receiptIdsByNo),
            ];
        })->toArray();

        $this->closingBalance = $running;
    }

    private function resolvePdfUrl(?string $documentType, ?string $documentNo, Collection $receiptIdsByNo): ?string
    {
        if (! $documentNo) {
            return null;
        }

        return match ($documentType) {
            'invoice' => route('admin.reports.invoice.pdf', ['invoice' => $documentNo]),
            'payment' => isset($receiptIdsByNo[$documentNo])
                ? route('admin.reports.receipt.pdf', ['receipt' => $receiptIdsByNo[$documentNo]])
                : null,
            default => null,
        };
    }

    public function render(): View
    {
        return view('livewire.members.member-card');
    }
}
