<?php

namespace App\Services;

use App\Models\Member;

class MemberImportService
{
    /**
     * Import members from an open CSV file handle.
     *
     * Member number, customer number and vendor number are not read from the
     * file; they are auto-populated from the configured number series. Rows
     * whose phone or email duplicate an existing member (or an earlier row in
     * the same file) are skipped and flagged.
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

            Member::updateOrCreate(
                ['identity_no' => $record['identity_no'] ?: null],
                array_filter([
                    'name' => $record['name'] ?: null,
                    'identity_type' => $record['identity_type'] ?: 'national_id',
                    'identity_no' => $record['identity_no'] ?: null,
                    'phone' => $phone,
                    'email' => $email,
                    'date_of_birth' => $record['date_of_birth'] ?: null,
                    'member_status' => $record['member_status'] ?: null,
                    'is_chakama' => isset($record['is_chakama']) ? (bool) $record['is_chakama'] : false,
                    'is_sbf' => isset($record['is_sbf']) ? (bool) $record['is_sbf'] : false,
                    'type' => 'member',
                ], fn ($v) => $v !== null && $v !== '')
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
}
