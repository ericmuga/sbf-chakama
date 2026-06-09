<?php

namespace App\Http\Controllers;

use App\Exports\CustomerListExport;
use App\Exports\VendorListExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FinanceListExportController extends Controller
{
    public function customers(Request $request): BinaryFileResponse
    {
        $postingGroupId = $request->filled('posting_group') ? (int) $request->query('posting_group') : null;
        $onlyWithBalance = $request->boolean('with_balance');

        return Excel::download(
            new CustomerListExport($postingGroupId, $onlyWithBalance),
            'customers-'.now()->format('Y-m-d').'.xlsx',
        );
    }

    public function vendors(Request $request): BinaryFileResponse
    {
        $postingGroupId = $request->filled('posting_group') ? (int) $request->query('posting_group') : null;
        $onlyWithBalance = $request->boolean('with_balance');

        return Excel::download(
            new VendorListExport($postingGroupId, $onlyWithBalance),
            'vendors-'.now()->format('Y-m-d').'.xlsx',
        );
    }
}
