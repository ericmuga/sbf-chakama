<?php

namespace App\Livewire\Members;

use App\Models\Member;
use App\Services\Reports\MemberStatementService;
use Illuminate\View\View;
use Livewire\Component;

class MemberStatement extends Component
{
    public int $memberId;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public bool $loaded = false;

    public float $opening = 0;

    public float $totalDebits = 0;

    public float $totalCredits = 0;

    public float $closing = 0;

    /** @var array<int, array{posting_date: string, document_type: string, document_no: string, description: ?string, debit: float, credit: float, running_balance: float}> */
    public array $entries = [];

    public function mount(int $memberId): void
    {
        $this->memberId = $memberId;
    }

    public function refresh(MemberStatementService $service): void
    {
        $member = Member::findOrFail($this->memberId);
        $data = $service->build($member, $this->dateFrom ?: null, $this->dateTo ?: null);

        $this->opening = $data['opening'];
        $this->totalDebits = $data['total_debits'];
        $this->totalCredits = $data['total_credits'];
        $this->closing = $data['closing'];

        $this->entries = $data['entries']->map(fn ($entry) => [
            'posting_date' => $entry->posting_date?->format('d M Y') ?? '—',
            'document_type' => ucfirst($entry->document_type ?? ''),
            'document_no' => $entry->document_no ?? '—',
            'description' => $entry->description,
            'debit' => (float) $entry->amount > 0 ? (float) $entry->amount : 0,
            'credit' => (float) $entry->amount < 0 ? abs((float) $entry->amount) : 0,
            'running_balance' => (float) $entry->running_balance,
        ])->toArray();

        $this->loaded = true;
    }

    public function getDownloadUrlAttribute(string $format): string
    {
        $params = array_filter([
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        ]);

        $route = $format === 'pdf'
            ? 'admin.reports.member-statement.pdf'
            : 'admin.reports.member-statement.excel';

        return route($route, $this->memberId).($params ? '?'.http_build_query($params) : '');
    }

    public function render(): View
    {
        return view('livewire.members.member-statement');
    }
}
