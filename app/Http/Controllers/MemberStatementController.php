<?php

namespace App\Http\Controllers;

use App\Exports\CashReceiptsExport;
use App\Exports\MemberListExport;
use App\Exports\MemberStatementExport;
use App\Models\Finance\CashReceipt;
use App\Models\Member;
use App\Services\Reports\MemberStatementService;
use Barryvdh\DomPDF\Facade\Pdf;
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

        $organization = config('app.name', 'SOBA Benevolent Fund');

        $pdf = Pdf::loadView('reports.cash-receipt-pdf', compact('receipt', 'organization'))
            ->setPaper([0, 0, 595.28, 420], 'landscape');

        return $pdf->download('receipt-'.$receipt->no.'.pdf');
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
}
