<?php

namespace Tests\Feature;

use App\Models\Dependant;
use App\Models\Member;
use App\Models\NextOfKin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MemberImportTest extends TestCase
{
    use RefreshDatabase;

    private function writeCsv(string $filename, array $headers, array $rows): string
    {
        Storage::fake('local');
        Storage::disk('local')->makeDirectory('imports');
        $path = "imports/{$filename}";
        $handle = fopen(Storage::disk('local')->path($path), 'w');
        fputcsv($handle, $headers);
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);

        return $path;
    }

    public function test_members_can_be_imported_from_csv(): void
    {
        $csvPath = $this->writeCsv('members.csv', [
            'no', 'name', 'identity_type', 'identity_no', 'phone', 'email',
            'date_of_birth', 'member_status', 'customer_no', 'vendor_no', 'is_chakama', 'is_sbf',
        ], [
            ['MBR-001', 'John Doe', 'national_id', '12345678', '0712345678', 'john@example.com', '1990-01-15', 'active', '', '', '0', '0'],
            ['MBR-002', 'Jane Doe', 'national_id', '87654321', '0712345679', 'jane@example.com', '1992-05-20', 'active', '', '', '1', '0'],
        ]);

        $path = Storage::disk('local')->path($csvPath);
        $handle = fopen($path, 'r');
        $headers = fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {
            $record = array_combine($headers, $row);
            Member::updateOrCreate(
                ['no' => $record['no'] ?: null],
                array_filter([
                    'no' => $record['no'] ?: null,
                    'name' => $record['name'] ?: null,
                    'identity_type' => $record['identity_type'] ?: 'national_id',
                    'identity_no' => $record['identity_no'] ?: null,
                    'phone' => $record['phone'] ?: null,
                    'email' => $record['email'] ?: null,
                    'date_of_birth' => $record['date_of_birth'] ?: null,
                    'member_status' => $record['member_status'] ?: null,
                    'is_chakama' => (bool) $record['is_chakama'],
                    'is_sbf' => (bool) $record['is_sbf'],
                    'type' => 'member',
                ], fn ($v) => $v !== null && $v !== '')
            );
        }
        fclose($handle);

        $this->assertDatabaseHas('bus_members', ['no' => 'MBR-001', 'name' => 'John Doe']);
        $this->assertDatabaseHas('bus_members', ['no' => 'MBR-002', 'name' => 'Jane Doe']);
        $this->assertEquals(2, Member::members()->count());
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
