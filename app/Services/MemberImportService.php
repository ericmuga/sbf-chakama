<?php

namespace App\Services;

use App\Models\Finance\Customer;
use App\Models\Finance\CustomerLedgerEntry;
use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class MemberImportService
{
    /**
     * Import members from an open CSV file handle.
     *
     * Member number, customer number and vendor number are not read from the
     * file; they are auto-populated from the configured number series. Rows
     * whose phone or email duplicate an existing member (or an earlier row in
     * the same file) are skipped and flagged. When a balance and balance date
     * are supplied, an opening-balance ledger entry is created for the member.
     *
     * @param  resource  $handle
     * @return array{imported: int, skipped: array<int, string>}
     */
    public function importFromHandle($handle): array
    {
        $headers = fgetcsv($handle);

        $imported = 0;
        $skipped = [];
        $seenPhones = [];
        $seenEmails = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            $record = array_combine($headers, $row);
            $phone = ! empty($record['phone']) ? trim($record['phone']) : null;
            $email = ! empty($record['email']) ? trim($record['email']) : null;

            if ($phone && (in_array($phone, $seenPhones, true) || Member::where('phone', $phone)->exists())) {
                $skipped[] = "Row {$rowNumber}: duplicate phone {$phone}";

                continue;
            }

            if ($email && (in_array($email, $seenEmails, true) || Member::where('email', $email)->exists())) {
                $skipped[] = "Row {$rowNumber}: duplicate email {$email}";

                continue;
            }

            $dateOfBirth = $this->parseDate($record['date_of_birth'] ?? null);

            $member = Member::updateOrCreate(
                ['identity_no' => ($record['identity_no'] ?? null) ?: null],
                array_filter([
                    'name' => ($record['name'] ?? null) ?: null,
                    'identity_type' => ($record['identity_type'] ?? null) ?: 'national_id',
                    'identity_no' => ($record['identity_no'] ?? null) ?: null,
                    'phone' => $phone,
                    'email' => $email,
                    'date_of_birth' => $dateOfBirth,
                    'member_status' => ($record['member_status'] ?? null) ?: null,
                    'is_chakama' => isset($record['is_chakama']) ? (bool) $record['is_chakama'] : false,
                    'is_sbf' => isset($record['is_sbf']) ? (bool) $record['is_sbf'] : false,
                    'type' => 'member',
                ], fn ($v) => $v !== null && $v !== '')
            );

            $this->applyOpeningBalance(
                $member,
                $record['balance'] ?? null,
                $this->parseDate($record['balance_date'] ?? null),
            );

            $imported++;

            if ($phone) {
                $seenPhones[] = $phone;
            }

            if ($email) {
                $seenEmails[] = $email;
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    /**
     * Create (or refresh) an opening-balance customer ledger entry for the member.
     */
    private function applyOpeningBalance(Member $member, ?string $balance, ?Carbon $balanceDate): void
    {
        if ($balance === null || trim($balance) === '' || ! is_numeric($balance)) {
            return;
        }

        $amount = (float) $balance;

        if ($amount === 0.0) {
            return;
        }

        $customer = $member->customer_no
            ? Customer::where('no', $member->customer_no)->first()
            : null;

        if (! $customer) {
            return;
        }

        $postingDate = $balanceDate ?? Carbon::today();

        CustomerLedgerEntry::updateOrCreate(
            [
                'customer_id' => $customer->id,
                'document_type' => 'opening_balance',
            ],
            [
                'entry_no' => (CustomerLedgerEntry::max('entry_no') ?? 0) + 1,
                'document_no' => 'OPENING',
                'posting_date' => $postingDate,
                'due_date' => $postingDate,
                'amount' => $amount,
                'remaining_amount' => $amount,
                'is_open' => true,
                'created_by' => Auth::id(),
            ]
        );
    }

    /**
     * Parse a date from common formats used in uploaded spreadsheets.
     */
    private function parseDate(?string $value): ?Carbon
    {
        $value = $value !== null ? trim($value) : '';

        if ($value === '') {
            return null;
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y', 'd/m/y', 'd.m.Y', 'Y/m/d'] as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $value);
            } catch (\Throwable) {
                continue;
            }

            if ($parsed !== false && $parsed->format($format) === $value) {
                return $parsed->startOfDay();
            }
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
