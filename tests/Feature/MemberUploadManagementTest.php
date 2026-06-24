<?php

namespace Tests\Feature;

use App\Models\Claim;
use App\Models\Dependant;
use App\Models\Finance\Customer;
use App\Models\Finance\CustomerLedgerEntry;
use App\Models\Finance\CustomerPostingGroup;
use App\Models\Finance\NumberSeries;
use App\Models\Finance\PurchaseSetup;
use App\Models\Finance\SalesSetup;
use App\Models\Finance\VendorPostingGroup;
use App\Models\Member;
use App\Models\MemberLedgerEntry;
use App\Models\Notification;
use App\Models\User;
use App\Services\MemberImportService;
use App\Services\MemberPurgeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MemberUploadManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        NumberSeries::create([
            'code' => 'MBR', 'description' => 'Member Numbers',
            'prefix' => 'MBR-', 'last_no' => 0, 'length' => 6,
            'is_manual_allowed' => false, 'prevent_repeats' => true, 'is_active' => true,
        ]);
        NumberSeries::create([
            'code' => 'CUST', 'description' => 'Customer Numbers',
            'prefix' => 'CUST-', 'last_no' => 0, 'length' => 6,
            'is_manual_allowed' => false, 'prevent_repeats' => true, 'is_active' => true,
        ]);
        NumberSeries::create([
            'code' => 'VEND', 'description' => 'Vendor Numbers',
            'prefix' => 'VEND-', 'last_no' => 0, 'length' => 6,
            'is_manual_allowed' => false, 'prevent_repeats' => true, 'is_active' => true,
        ]);

        SalesSetup::create([
            'invoice_nos' => 'CUST', 'posted_invoice_nos' => 'CUST',
            'customer_nos' => 'CUST', 'member_nos' => 'MBR',
        ]);
        PurchaseSetup::create([
            'invoice_nos' => 'VEND', 'posted_invoice_nos' => 'VEND', 'vendor_nos' => 'VEND',
        ]);

        CustomerPostingGroup::create([
            'code' => 'MEMBER', 'description' => 'Members', 'receivables_account_no' => '1100',
        ]);
        VendorPostingGroup::create([
            'code' => 'MEMBER', 'description' => 'Members', 'payables_account_no' => '2100',
        ]);
    }

    private function importRows(array $headers, array $rows): array
    {
        Storage::fake('local');
        $buffer = fopen('php://temp', 'r+');
        fputcsv($buffer, $headers);
        foreach ($rows as $row) {
            fputcsv($buffer, $row);
        }
        rewind($buffer);
        Storage::disk('local')->put('imports/members.csv', stream_get_contents($buffer));
        fclose($buffer);

        $handle = fopen(Storage::disk('local')->path('imports/members.csv'), 'r');
        $result = app(MemberImportService::class)->importFromHandle($handle);
        fclose($handle);

        return $result;
    }

    public function test_member_and_customer_no_are_auto_generated_on_import(): void
    {
        $this->importRows(
            ['name', 'identity_type', 'identity_no', 'phone', 'email', 'date_of_birth', 'member_status', 'is_chakama', 'is_sbf', 'balance', 'balance_date'],
            [['John Doe', 'national_id', '12345678', '0712345678', 'john@example.com', '', 'active', '0', '1', '', '']],
        );

        $member = Member::where('identity_no', '12345678')->first();

        $this->assertNotNull($member);
        $this->assertSame('MBR-000001', $member->no);
        $this->assertSame('CUST-000001', $member->customer_no);
    }

    public function test_day_first_date_of_birth_is_parsed(): void
    {
        $this->importRows(
            ['name', 'identity_type', 'identity_no', 'date_of_birth'],
            [['John Doe', 'national_id', '12345678', '15/01/1990']],
        );

        $member = Member::where('identity_no', '12345678')->first();

        $this->assertNotNull($member);
        $this->assertSame('1990-01-15', $member->date_of_birth->format('Y-m-d'));
    }

    public function test_opening_balance_creates_a_customer_ledger_entry(): void
    {
        $this->importRows(
            ['name', 'identity_type', 'identity_no', 'balance', 'balance_date'],
            [['John Doe', 'national_id', '12345678', '2500.00', '01/06/2026']],
        );

        $member = Member::where('identity_no', '12345678')->first();
        $customer = Customer::where('no', $member->customer_no)->first();

        $entry = CustomerLedgerEntry::where('customer_id', $customer->id)
            ->where('document_type', 'opening_balance')
            ->first();

        $this->assertNotNull($entry);
        $this->assertSame('2500.0000', (string) $entry->amount);
        $this->assertSame('2026-06-01', $entry->posting_date->format('Y-m-d'));
    }

    public function test_reimporting_does_not_duplicate_the_opening_balance(): void
    {
        $headers = ['name', 'identity_type', 'identity_no', 'balance', 'balance_date'];
        $row = [['John Doe', 'national_id', '12345678', '2500.00', '01/06/2026']];

        $this->importRows($headers, $row);
        $this->importRows($headers, $row);

        $member = Member::where('identity_no', '12345678')->first();
        $customer = Customer::where('no', $member->customer_no)->first();

        $this->assertSame(1, CustomerLedgerEntry::where('customer_id', $customer->id)
            ->where('document_type', 'opening_balance')
            ->count());
    }

    public function test_purge_removes_members_and_related_data_but_keeps_admins(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->importRows(
            ['name', 'identity_type', 'identity_no', 'email', 'balance', 'balance_date'],
            [
                ['John Doe', 'national_id', '12345678', 'john@example.com', '2500.00', '01/06/2026'],
                ['Jane Doe', 'national_id', '87654321', 'jane@example.com', '', ''],
            ],
        );

        $john = Member::where('identity_no', '12345678')->first();
        $memberUserId = $john->user_id;

        MemberLedgerEntry::create([
            'entry_no' => 1, 'member_no' => $john->no, 'posting_date' => '2026-06-01',
            'document_type' => 'Invoice', 'document_no' => 'INV-1', 'amount' => 100,
            'remaining_amount' => 100, 'open' => true,
        ]);
        Notification::create([
            'member_no' => $john->no, 'title' => 'Hi', 'body' => 'Test', 'status' => 'Draft',
        ]);
        Claim::factory()->create(['member_id' => $john->id]);
        Dependant::factory()->create(['member_id' => $john->id]);

        $this->assertSame(2, Member::members()->count());
        $this->assertGreaterThan(0, Customer::count());

        $purged = app(MemberPurgeService::class)->purge();

        $this->assertSame(2, $purged);
        $this->assertSame(0, Member::query()->count());
        $this->assertSame(0, Customer::count());
        $this->assertSame(0, CustomerLedgerEntry::count());
        $this->assertSame(0, MemberLedgerEntry::count());
        $this->assertSame(0, Notification::count());
        $this->assertSame(0, Claim::count());

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
        $this->assertDatabaseMissing('users', ['id' => $memberUserId]);
    }
}
