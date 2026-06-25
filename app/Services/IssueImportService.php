<?php

namespace App\Services;

use App\Models\Issue;
use Carbon\Carbon;

class IssueImportService
{
    /**
     * Import issues from an open CSV file handle. Rows are matched on
     * (title, details) so re-importing updates rather than duplicates.
     *
     * @param  resource  $handle
     * @return array{imported: int, skipped: array<int, string>}
     */
    public function importFromHandle($handle): array
    {
        $headers = fgetcsv($handle);

        if ($headers === false) {
            return ['imported' => 0, 'skipped' => []];
        }

        $headers = array_map(fn ($h) => strtolower(trim((string) $h)), $headers);

        $imported = 0;
        $skipped = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            $record = @array_combine($headers, $row);

            if ($record === false) {
                $skipped[] = "Row {$rowNumber}: column count does not match the header.";

                continue;
            }

            $title = trim((string) ($record['title'] ?? $record['issue'] ?? ''));

            if ($title === '') {
                $skipped[] = "Row {$rowNumber}: missing issue title.";

                continue;
            }

            $details = trim((string) ($record['details'] ?? $record['issue details'] ?? ''));

            Issue::updateOrCreate(
                ['title' => $title, 'details' => $details],
                array_filter([
                    'portal_type' => $this->normalise($record['portal_type'] ?? $record['portal'] ?? null, ['sbf', 'chakama', 'both']),
                    'category' => $this->normalise($record['category'] ?? $record['type'] ?? null, ['development', 'functional']),
                    'issue_owner' => $this->value($record['issue_owner'] ?? $record['owner'] ?? null),
                    'resource' => $this->value($record['resource'] ?? null),
                    'status' => $this->normaliseStatus($record['status'] ?? null),
                    'qa_test_result' => $this->value($record['qa_test_result'] ?? $record['qa'] ?? null),
                    'comments' => $this->value($record['comments'] ?? null),
                    'date_assigned' => $this->parseDate($record['date_assigned'] ?? null),
                    'date_actioned' => $this->parseDate($record['date_actioned'] ?? null),
                    'closure_date' => $this->parseDate($record['closure_date'] ?? $record['date_closed'] ?? null),
                    'reviewed_date' => $this->parseDate($record['reviewed_date'] ?? null),
                ], fn ($v) => $v !== null && $v !== ''),
            );

            $imported++;
        }

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    private function value(mixed $value): ?string
    {
        $value = $value !== null ? trim((string) $value) : '';

        return $value === '' ? null : $value;
    }

    /**
     * @param  array<int, string>  $allowed
     */
    private function normalise(mixed $value, array $allowed): ?string
    {
        $value = strtolower(trim((string) ($value ?? '')));

        return in_array($value, $allowed, true) ? $value : null;
    }

    private function normaliseStatus(mixed $value): ?string
    {
        $value = strtolower(trim((string) ($value ?? '')));

        return match ($value) {
            'open' => 'open',
            'closed' => 'closed',
            'pending testing', 'pending qa review', 'pending_testing' => 'pending_testing',
            'pending rework', 'pending_rework' => 'pending_rework',
            'reworked-pending testing', 'reworked_pending_testing' => 'reworked_pending_testing',
            default => null,
        };
    }

    private function parseDate(?string $value): ?Carbon
    {
        $value = $value !== null ? trim($value) : '';

        if ($value === '') {
            return null;
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y', 'd.m.Y'] as $format) {
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
