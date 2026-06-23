<?php

namespace Tests\Feature;

use App\Models\Dependant;
use App\Models\Member;
use App\Models\NextOfKin;
use App\Services\MemberImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MemberImportTest extends TestCase
{
    use RefreshDatabase;

    private function writeCsv(string $filename, array $headers, array $rows): string
    {
        Storage::fake('local');
        $path = "imports/{$filename}";

        $buffer = fopen('php://temp', 'r+');
        fputcsv($buffer, $headers);
        foreach ($rows as $row) {
            fputcsv($buffer, $row);
        }
        rewind($buffer);
        Storage::disk('local')->put($path, stream_get_contents($buffer));
        fclose($buffer);

        return $path;
    }

    private function importMembers(array $rows): array
    {
        $csvPath = $this->writeCsv('members.csv', [
            'name', 'identity_type', 'identity_no', 'phone', 'email',
            'date_of_birth', 'member_status', 'is_chakama', 'is_sbf',
        ], $rows);

        $handle = fopen(Storage::disk('local')->path($csvPath), 'r');
        $result = app(MemberImportService::class)->importFromHandle($handle);
        fclose($handle);

        return $result;
    }

    public function test_members_can_be_imported_from_csv(): void
    {
        $result = $this->importMembers([
            ['John Doe', 'national_id', '12345678', '0712345678', 'john@example.com', '1990-01-15', 'active', '0', '0'],
            ['Jane Doe', 'national_id', '87654321', '0712345679', 'jane@example.com', '1992-05-20', 'active', '1', '0'],
        ]);

        $this->assertSame(2, $result['imported']);
        $this->assertSame([], $result['skipped']);
        $this->assertDatabaseHas('bus_members', ['identity_no' => '12345678', 'name' => 'John Doe']);
        $this->assertDatabaseHas('bus_members', ['identity_no' => '87654321', 'name' => 'Jane Doe']);
        $this->assertEquals(2, Member::members()->count());
    }

    public function test_member_no_customer_no_and_vendor_no_are_not_read_from_the_file(): void
    {
        $this->importMembers([
            ['John Doe', 'national_id', '12345678', '0712345678', 'john@example.com', '1990-01-15', 'active', '0', '0'],
        ]);

        $member = Member::where('identity_no', '12345678')->first();

        $this->assertNotNull($member);
        $this->assertSame('member', $member->type);
    }

    public function test_rows_with_duplicate_phone_are_skipped_and_flagged(): void
    {
        Member::factory()->create(['phone' => '0712345678']);

        $result = $this->importMembers([
            ['John Doe', 'national_id', '12345678', '0712345678', 'john@example.com', '', '', '0', '0'],
            ['Jane Doe', 'national_id', '87654321', '0700000000', 'jane@example.com', '', '', '0', '0'],
        ]);

        $this->assertSame(1, $result['imported']);
        $this->assertCount(1, $result['skipped']);
        $this->assertStringContainsString('duplicate phone 0712345678', $result['skipped'][0]);
        $this->assertDatabaseMissing('bus_members', ['identity_no' => '12345678']);
        $this->assertDatabaseHas('bus_members', ['identity_no' => '87654321']);
    }

    public function test_rows_with_duplicate_email_are_skipped_and_flagged(): void
    {
        Member::factory()->create(['email' => 'john@example.com']);

        $result = $this->importMembers([
            ['John Doe', 'national_id', '12345678', '0712345678', 'john@example.com', '', '', '0', '0'],
        ]);

        $this->assertSame(0, $result['imported']);
        $this->assertCount(1, $result['skipped']);
        $this->assertStringContainsString('duplicate email john@example.com', $result['skipped'][0]);
        $this->assertDatabaseMissing('bus_members', ['identity_no' => '12345678']);
    }

    public function test_duplicate_phone_within_the_same_file_is_skipped(): void
    {
        $result = $this->importMembers([
            ['John Doe', 'national_id', '12345678', '0712345678', 'john@example.com', '', '', '0', '0'],
            ['Jane Doe', 'national_id', '87654321', '0712345678', 'jane@example.com', '', '', '0', '0'],
        ]);

        $this->assertSame(1, $result['imported']);
        $this->assertCount(1, $result['skipped']);
        $this->assertDatabaseHas('bus_members', ['identity_no' => '12345678']);
        $this->assertDatabaseMissing('bus_members', ['identity_no' => '87654321']);
    }

    public function test_import_skips_rows_with_unknown_member_no_for_dependants(): void
    {
        $csvPath = $this->writeCsv('dependants.csv', [
            'member_no', 'name', 'identity_type', 'identity_no', 'phone', 'email', 'date_of_birth', 'relationship',
        ], [
            ['UNKNOWN-999', 'Child One', 'birth_cert_no', 'BC001', '', '', '', 'Child'],
        ]);

        $path = Storage::disk('local')->path($csvPath);
        $handle = fopen($path, 'r');
        $headers = fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {
            $record = array_combine($headers, $row);
            $member = Member::where('no', $record['member_no'])->first();
            if (! $member) {
                continue;
            }
            Dependant::create(['member_id' => $member->id, 'name' => $record['name']]);
        }
        fclose($handle);

        $this->assertEquals(0, Dependant::count());
    }

    public function test_dependants_can_be_imported_from_csv(): void
    {
        $member = Member::factory()->create(['no' => 'MBR-001']);

        $csvPath = $this->writeCsv('dependants.csv', [
            'member_no', 'name', 'identity_type', 'identity_no', 'phone', 'email', 'date_of_birth', 'relationship',
        ], [
            ['MBR-001', 'Child One', 'birth_cert_no', 'BC001', '', '', '2015-03-10', 'Child'],
        ]);

        $path = Storage::disk('local')->path($csvPath);
        $handle = fopen($path, 'r');
        $headers = fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {
            $record = array_combine($headers, $row);
            $foundMember = Member::where('no', $record['member_no'])->first();
            if (! $foundMember) {
                continue;
            }
            Dependant::create(array_filter([
                'member_id' => $foundMember->id,
                'name' => $record['name'] ?: null,
                'relationship' => $record['relationship'] ?: null,
            ], fn ($v) => $v !== null && $v !== ''));
        }
        fclose($handle);

        $this->assertCount(1, $member->dependants);
        $this->assertEquals('Child One', $member->dependants->first()->name);
    }

    public function test_next_of_kin_can_be_imported_from_csv(): void
    {
        $member = Member::factory()->create(['no' => 'MBR-001']);

        $csvPath = $this->writeCsv('next_of_kin.csv', [
            'member_no', 'name', 'identity_type', 'identity_no', 'phone', 'email', 'date_of_birth', 'relationship', 'contact_preference',
        ], [
            ['MBR-001', 'Mary Doe', 'national_id', '87654321', '0712345679', 'mary@example.com', '1985-03-10', 'Spouse', 'phone'],
        ]);

        $path = Storage::disk('local')->path($csvPath);
        $handle = fopen($path, 'r');
        $headers = fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {
            $record = array_combine($headers, $row);
            $foundMember = Member::where('no', $record['member_no'])->first();
            if (! $foundMember) {
                continue;
            }
            NextOfKin::create(array_filter([
                'member_id' => $foundMember->id,
                'name' => $record['name'] ?: null,
                'relationship' => $record['relationship'] ?: null,
                'contact_preference' => $record['contact_preference'] ?: null,
            ], fn ($v) => $v !== null && $v !== ''));
        }
        fclose($handle);

        $this->assertCount(1, $member->nextOfKin);
        $this->assertEquals('Mary Doe', $member->nextOfKin->first()->name);
        $this->assertEquals('phone', $member->nextOfKin->first()->contact_preference);
    }
}
