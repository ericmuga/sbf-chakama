<?php

namespace App\Http\Controllers;

use App\Exports\CashReceiptsExport;
use App\Exports\MemberListExport;
use App\Exports\MemberStatementExport;
use App\Filament\Resources\Chakama\ChakamaMemberReports\ChakamaMemberReportResource;
use App\Models\Finance\CashReceipt;
use App\Models\Finance\Customer;
use App\Models\Finance\SalesHeader;
use App\Models\Member;
use App\Services\Reports\MemberStatementService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Auth\Access\AuthorizationException;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class MemberStatementController extends Controller
{
    public function downloadExcel(Member $member, MemberStatementService $service): Response
    {
        $dateFrom = request('date_from');
        $dateTo = request('date_to');

        $filename = 'statement-'.$member->no.($dateFrom ? '-'.$dateFrom : '').'.xlsx';

        return Excel::download(new MemberStatementExport($member, $dateFrom, $dateTo), $filename);
    }

    public function downloadPdf(Member $member, MemberStatementService $service): Response
    {
        $dateFrom = request('date_from');
        $dateTo = request('date_to');

        $data = $service->build($member, $dateFrom, $dateTo);

        $pdf = Pdf::loadView('reports.member-statement-pdf', compact('data'))
            ->setPaper('a4', 'portrait');

        $filename = 'statement-'.$member->no.($dateFrom ? '-'.$dateFrom : '').'.pdf';

        return $pdf->download($filename);
    }

    public function downloadMemberListExcel(): Response
    {
        return Excel::download(new MemberListExport, 'chakama-members-'.now()->format('Y-m-d').'.xlsx');
    }

    public function downloadReceiptPdf(CashReceipt $receipt): Response
    {
        $receipt->load(['customer', 'paymentMethod', 'bankAccount', 'shareSubscription']);

        $this->authorizeCustomerDocument($receipt->customer_id);

        $organization = config('app.name', 'SOBA Benevolent Fund');

        $pdf = Pdf::loadView('reports.cash-receipt-pdf', compact('receipt', 'organization'))
            ->setPaper([0, 0, 595.28, 420], 'landscape');

        return $pdf->download('receipt-'.$receipt->no.'.pdf');
    }

    public function downloadInvoicePdf(SalesHeader $invoice): Response
    {
        $invoice->load(['customer', 'salesLines.service', 'shareSubscription']);

        $this->authorizeCustomerDocument($invoice->customer_id);

        $organization = config('app.name', 'SOBA Benevolent Fund');
        $invoiceTotal = (float) $invoice->salesLines->sum('line_amount');

        $pdf = Pdf::loadView('reports.sales-invoice-pdf', compact('invoice', 'organization', 'invoiceTotal'))
            ->setPaper('a4', 'portrait');

        return $pdf->download('invoice-'.$invoice->no.'.pdf');
    }

    private function authorizeCustomerDocument(?int $customerId): void
    {
        $user = auth()->user();

        if ($user?->isAdmin()) {
            return;
        }

        $memberCustomerNo = $user?->member?->customer_no;

        if (! $memberCustomerNo || ! $customerId) {
            throw new AuthorizationException;
        }

        $owns = Customer::where('id', $customerId)
            ->where('no', $memberCustomerNo)
            ->exists();

        if (! $owns) {
            throw new AuthorizationException;
        }
    }

    public function downloadReceiptsExcel(): Response
    {
        $dateFrom = request('date_from');
        $dateTo = request('date_to');
        $paymentMethodId = request('payment_method_id') ? (int) request('payment_method_id') : null;
        $status = request('status');

        $filename = 'receipts-'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(
            new CashReceiptsExport($dateFrom, $dateTo, $paymentMethodId, $status),
            $filename
        );
    }

    public function downloadChakamaMemberReportPdf(): Response
    {
        if (! auth()->user()?->isAdmin()) {
            throw new AuthorizationException;
        }

        $dateFrom = request('date_from');
        $dateTo = request('date_to');

        $members = Member::query()
            ->where('is_chakama', true)
            ->with('shareSubscriptions')
            ->orderBy('name')
            ->get();

        $rows = $members->map(function (Member $member) use ($dateFrom, $dateTo): array {
            $period = ChakamaMemberReportResource::getPeriodStats($member, $dateFrom, $dateTo);
            $stats = ChakamaMemberReportResource::getMemberBillingStats($member);

            return [
                'no' => $member->no,
                'name' => $member->name,
                'phone' => $member->phone,
                'member_status' => $member->member_status,
                'opening_balance' => $period['opening_balance'],
                'movement_in' => $period['movement_in'],
                'movement_out' => $period['movement_out'],
                'closing_balance' => $period['closing_balance'],
                'months_outstanding' => $stats['months_outstanding'],
                'oldest_due_date' => $stats['oldest_due_date'],
                'share_count' => (int) $member->shareSubscriptions->sum('number_of_shares'),
            ];
        })->all();

        $totals = [
            'opening_balance' => array_sum(array_column($rows, 'opening_balance')),
            'movement_in' => array_sum(array_column($rows, 'movement_in')),
            'movement_out' => array_sum(array_column($rows, 'movement_out')),
            'closing_balance' => array_sum(array_column($rows, 'closing_balance')),
            'share_count' => array_sum(array_column($rows, 'share_count')),
        ];

        $pdf = Pdf::loadView('reports.chakama-member-report-pdf', [
            'rows' => $rows,
            'totals' => $totals,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'organization' => config('app.name', 'SOBA Benevolent Fund'),
        ])->setPaper('a4', 'landscape');

        $stamp = now()->format('Y-m-d');

        return $pdf->download("chakama-member-report-{$stamp}.pdf");
    }
}
