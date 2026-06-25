<?php

use App\Http\Controllers\FinanceListExportController;
use App\Http\Controllers\IssueController;
use App\Http\Controllers\MemberStatementController;
use App\Http\Controllers\TemplateController;
use App\Livewire\Members\MemberCard;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check()
    ? redirect('/admin')
    : redirect('/admin/login')
)->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::get('member', MemberCard::class)->name('member.card');
});

Route::middleware(['auth'])->prefix('admin/reports')->name('admin.reports.')->group(function () {
    Route::get('members/export-excel', [MemberStatementController::class, 'downloadMemberListExcel'])->name('members.export-excel');
    Route::get('members/{member}/statement/excel', [MemberStatementController::class, 'downloadExcel'])->name('member-statement.excel');
    Route::get('members/{member}/statement/pdf', [MemberStatementController::class, 'downloadPdf'])->name('member-statement.pdf');
    Route::get('receipts/{receipt}/pdf', [MemberStatementController::class, 'downloadReceiptPdf'])->name('receipt.pdf');
    Route::get('receipts/export-excel', [MemberStatementController::class, 'downloadReceiptsExcel'])->name('receipts.excel');
    Route::get('invoices/{invoice:no}/pdf', [MemberStatementController::class, 'downloadInvoicePdf'])->name('invoice.pdf');
    Route::get('chakama-member-report/pdf', [MemberStatementController::class, 'downloadChakamaMemberReportPdf'])->name('chakama-member-report.pdf');
    Route::get('customers/export-excel', [FinanceListExportController::class, 'customers'])->name('customers.export-excel');
    Route::get('vendors/export-excel', [FinanceListExportController::class, 'vendors'])->name('vendors.export-excel');
});

Route::middleware(['auth'])->prefix('admin/templates')->name('admin.templates.')->group(function () {
    Route::get('members', [TemplateController::class, 'members'])->name('members');
    Route::get('dependants', [TemplateController::class, 'dependants'])->name('dependants');
    Route::get('next-of-kin', [TemplateController::class, 'nextOfKin'])->name('next-of-kin');
    Route::get('issues', [IssueController::class, 'template'])->name('issues');
});

Route::middleware(['auth'])->prefix('admin/issues')->name('admin.issues.')->group(function () {
    Route::get('export', [IssueController::class, 'export'])->name('export');
});

require __DIR__.'/settings.php';
