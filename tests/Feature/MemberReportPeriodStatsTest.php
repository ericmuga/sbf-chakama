<?php

namespace Tests\Feature;

use App\Filament\Resources\Chakama\ChakamaMemberReports\ChakamaMemberReportResource;
use App\Models\Finance\Customer;
use App\Models\Finance\CustomerLedgerEntry;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberReportPeriodStatsTest extends TestCase
{
    use RefreshDatabase;

    private Member $member;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::factory()->create();
        $this->member = Member::factory()->create([
            'is_chakama' => true,
            'customer_no' => $this->customer->no,
        ]);

        $this->makeEntry('2026-01-15', 'invoice', 'INV-1', 5_000);
        $this->makeEntry('2026-02-15', 'payment', 'RCP-1', -3_000);
        $this->makeEntry('2026-03-10', 'invoice', 'INV-2', 4_000);
        $this->makeEntry('2026-03-20', 'payment', 'RCP-2', -1_500);
    }

    private function makeEntry(string $date, string $type, string $no, float $amount): void
    {
        CustomerLedgerEntry::create([
            'customer_id' => $this->customer->id,
            'document_type' => $type,
            'document_no' => $no,
            'posting_date' => $date,
            'due_date' => $date,
            'amount' => $amount,
            'remaining_amount' => $type === 'invoice' ? $amount : 0,
            'is_open' => $type === 'invoice',
            'entry_no' => CustomerLedgerEntry::max('entry_no') + 1,
        ]);
    }

    public function test_period_stats_no_filter_returns_full_movement_and_no_opening(): void
    {
        $stats = ChakamaMemberReportResource::getPeriodStats($this->member, null, null);

        $this->assertSame(0.0, $stats['opening_balance']);
        $this->assertSame(9000.0, $stats['movement_in']);
        $this->assertSame(4500.0, $stats['movement_out']);
        $this->assertSame(4500.0, $stats['closing_balance']);
    }

    public function test_period_stats_with_february_window(): void
    {
        // Opening as at 2026-02-01: invoice 5000 only → 5000
        // Movement in Feb: nothing (no invoices) → 0
        // Movement out Feb: 3000 → 3000
        // Closing: 5000 + 0 - 3000 = 2000
        $stats = ChakamaMemberReportResource::getPeriodStats($this->member, '2026-02-01', '2026-02-28');

        $this->assertSame(5000.0, $stats['opening_balance']);
        $this->assertSame(0.0, $stats['movement_in']);
        $this->assertSame(3000.0, $stats['movement_out']);
        $this->assertSame(2000.0, $stats['closing_balance']);
    }

    public function test_period_stats_with_march_window(): void
    {
        // Opening as at 2026-03-01: 5000 - 3000 = 2000
        // Movement in March: 4000
        // Movement out March: 1500
        // Closing: 2000 + 4000 - 1500 = 4500
        $stats = ChakamaMemberReportResource::getPeriodStats($this->member, '2026-03-01', '2026-03-31');

        $this->assertSame(2000.0, $stats['opening_balance']);
        $this->assertSame(4000.0, $stats['movement_in']);
        $this->assertSame(1500.0, $stats['movement_out']);
        $this->assertSame(4500.0, $stats['closing_balance']);
    }

    public function test_period_stats_for_member_without_customer(): void
    {
        $orphan = Member::factory()->create(['is_chakama' => true, 'customer_no' => null]);

        $stats = ChakamaMemberReportResource::getPeriodStats($orphan, '2026-01-01', '2026-12-31');

        $this->assertSame(0.0, $stats['opening_balance']);
        $this->assertSame(0.0, $stats['movement_in']);
        $this->assertSame(0.0, $stats['movement_out']);
        $this->assertSame(0.0, $stats['closing_balance']);
    }
}
