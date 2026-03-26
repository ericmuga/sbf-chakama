<?php

namespace Tests\Feature;

use App\Enums\FundTransactionType;
use App\Models\Finance\NumberSeries;
use App\Models\FundAccount;
use App\Models\FundTransaction;
use App\Models\User;
use App\Services\FundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FundServiceTest extends TestCase
{
    use RefreshDatabase;

    private FundService $service;

    private FundAccount $fund;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(FundService::class);

        $user = User::factory()->create();
        $this->actingAs($user);

        NumberSeries::factory()->create([
            'code' => 'FUND',
            'prefix' => 'FUND-',
            'last_no' => 0,
            'length' => 4,
            'is_active' => true,
        ]);

        $this->fund = FundAccount::create([
            'no' => 'FUND-0001',
            'name' => 'Test Fund',
            'balance' => 0,
            'is_active' => true,
        ]);
    }

    public function test_record_transaction_updates_fund_balance(): void
    {
        $this->service->recordTransaction(
            $this->fund,
            FundTransactionType::Contribution,
            10000.00,
            'Test contribution'
        );

        $this->assertEquals(10000.00, (float) $this->fund->fresh()->balance);
    }

    public function test_inflow_increases_balance(): void
    {
        $this->service->recordTransaction($this->fund, FundTransactionType::Contribution, 5000.00, 'Inflow');
        $this->service->recordTransaction($this->fund->fresh(), FundTransactionType::Contribution, 3000.00, 'Inflow 2');

        $this->assertEquals(8000.00, (float) $this->fund->fresh()->balance);
    }

    public function test_outflow_decreases_balance(): void
    {
        $this->service->recordTransaction($this->fund, FundTransactionType::Contribution, 10000.00, 'Seed');
        $this->service->recordTransaction($this->fund->fresh(), FundTransactionType::Withdrawal, -4000.00, 'Withdrawal');

        $this->assertEquals(6000.00, (float) $this->fund->fresh()->balance);
    }

    public function test_running_balance_is_sequential(): void
    {
        $this->service->recordTransaction($this->fund, FundTransactionType::Contribution, 1000.00, 'First');
        $tx2 = $this->service->recordTransaction($this->fund->fresh(), FundTransactionType::Contribution, 500.00, 'Second');

        $this->assertEquals(1500.00, (float) $tx2->running_balance);
    }

    public function test_recalculate_balance_matches_sum_of_transactions(): void
    {
        FundTransaction::create([
            'fund_account_id' => $this->fund->id,
            'transaction_type' => FundTransactionType::Contribution,
            'description' => 'Manual entry 1',
            'amount' => 2000.00,
            'running_balance' => 2000.00,
            'posting_date' => today(),
            'created_by' => auth()->id(),
        ]);

        FundTransaction::create([
            'fund_account_id' => $this->fund->id,
            'transaction_type' => FundTransactionType::Withdrawal,
            'description' => 'Manual entry 2',
            'amount' => -500.00,
            'running_balance' => 1500.00,
            'posting_date' => today(),
            'created_by' => auth()->id(),
        ]);

        // Fund balance is stale — recalculate should fix it
        $this->fund->balance = 0;
        $this->fund->save();

        $this->service->recalculateBalance($this->fund);

        $this->assertEquals(1500.00, (float) $this->fund->fresh()->balance);
    }
}
