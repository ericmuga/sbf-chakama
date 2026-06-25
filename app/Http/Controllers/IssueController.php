<?php

namespace App\Http\Controllers;

use App\Models\Issue;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IssueController extends Controller
{
    private const COLUMNS = [
        'title', 'portal_type', 'category', 'details', 'issue_owner', 'resource',
        'date_assigned', 'date_actioned', 'status', 'closure_date', 'reviewed_date',
        'qa_test_result', 'comments',
    ];

    public function export(): StreamedResponse
    {
        $rows = Issue::query()
            ->orderBy('created_at')
            ->get()
            ->map(fn (Issue $issue): array => [
                $issue->title,
                $issue->portal_type?->value,
                $issue->category?->value,
                $issue->details,
                $issue->issue_owner,
                $issue->resource,
                $issue->date_assigned?->format('Y-m-d'),
                $issue->date_actioned?->format('Y-m-d'),
                $issue->status?->value,
                $issue->closure_date?->format('Y-m-d'),
                $issue->reviewed_date?->format('Y-m-d'),
                $issue->qa_test_result,
                $issue->comments,
            ])
            ->all();

        return $this->streamCsv('issues_export.csv', self::COLUMNS, $rows);
    }

    public function template(): StreamedResponse
    {
        $example = [
            'Posted Sales Invoice', 'sbf', 'development',
            'Introduce the sales amount on the Posted Sales Invoice Page',
            'VW', 'EM', '14/06/2026', '', 'open', '', '', '', '',
        ];

        return $this->streamCsv('issues_template.csv', self::COLUMNS, [$example]);
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function streamCsv(string $filename, array $headers, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
