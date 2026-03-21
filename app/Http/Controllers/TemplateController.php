<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\StreamedResponse;

class TemplateController extends Controller
{
    public function members(): StreamedResponse
    {
        $headers = [
            'no', 'name', 'identity_type', 'identity_no', 'phone', 'email',
            'date_of_birth', 'member_status', 'customer_no', 'vendor_no',
            'is_chakama', 'is_sbf',
        ];

        $example = [
            'MBR-001', 'John Doe', 'national_id', '12345678', '0712345678',
            'john@example.com', '1990-01-15', 'active', 'CUST-001', '', '1', '0',
        ];

        return $this->streamCsv('members_template.csv', $headers, [$example]);
    }

    public function dependants(): StreamedResponse
    {
        $headers = [
            'member_no', 'name', 'identity_type', 'identity_no', 'phone',
            'email', 'date_of_birth', 'relationship',
        ];

        $example = [
            'MBR-001', 'Jane Doe', 'birth_cert_no', 'BC123456', '0712345678',
            'jane@example.com', '2010-06-20', 'Child',
        ];

        return $this->streamCsv('dependants_template.csv', $headers, [$example]);
    }

    public function nextOfKin(): StreamedResponse
    {
        $headers = [
            'member_no', 'name', 'identity_type', 'identity_no', 'phone',
            'email', 'date_of_birth', 'relationship', 'contact_preference',
        ];

        $example = [
            'MBR-001', 'Mary Doe', 'national_id', '87654321', '0712345679',
            'mary@example.com', '1985-03-10', 'Spouse', 'phone',
        ];

        return $this->streamCsv('next_of_kin_template.csv', $headers, [$example]);
    }

    /** @param array<int, array<int, string>> $rows */
    private function streamCsv(string $filename, array $headers, array $rows = []): StreamedResponse
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
