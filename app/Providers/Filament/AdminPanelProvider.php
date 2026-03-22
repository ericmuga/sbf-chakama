<?php

namespace App\Providers\Filament;

use App\Filament\Pages\SystemPage;
use App\Filament\Resources\ClaimApprovalTemplates\ClaimApprovalTemplateResource;
use App\Filament\Resources\Claims\ClaimResource;
use App\Filament\Resources\Finance\BankAccounts\BankAccountResource;
use App\Filament\Resources\Finance\BankLedgerEntries\BankLedgerEntryResource;
use App\Filament\Resources\Finance\CashReceipts\CashReceiptResource;
use App\Filament\Resources\Finance\CustomerLedgerEntries\CustomerLedgerEntryResource;
use App\Filament\Resources\Finance\CustomerPostingGroups\CustomerPostingGroupResource;
use App\Filament\Resources\Finance\CustomerResource;
use App\Filament\Resources\Finance\DirectExpenses\DirectExpenseResource;
use App\Filament\Resources\Finance\DirectIncomes\DirectIncomeResource;
use App\Filament\Resources\Finance\GeneralPostingSetups\GeneralPostingSetupResource;
use App\Filament\Resources\Finance\GlAccounts\GlAccountResource;
use App\Filament\Resources\Finance\GlEntries\GlEntryResource;
use App\Filament\Resources\Finance\MpesaSetup\MpesaSetupPage;
use App\Filament\Resources\Finance\NumberSeries\NumberSeriesResource;
use App\Filament\Resources\Finance\PaymentMethods\PaymentMethodResource;
use App\Filament\Resources\Finance\PurchaseHeaders\PurchaseHeaderResource;
use App\Filament\Resources\Finance\PurchaseSetups\PurchaseSetupResource;
use App\Filament\Resources\Finance\SalesHeaders\SalesHeaderResource;
use App\Filament\Resources\Finance\SalesSetups\SalesSetupResource;
use App\Filament\Resources\Finance\ScheduledInvoices\ScheduledInvoiceResource;
use App\Filament\Resources\Finance\ServicePostingGroups\ServicePostingGroupResource;
use App\Filament\Resources\Finance\Services\ServiceResource;
use App\Filament\Resources\Finance\VendorLedgerEntries\VendorLedgerEntryResource;
use App\Filament\Resources\Finance\VendorPayments\VendorPaymentResource;
use App\Filament\Resources\Finance\VendorPostingGroups\VendorPostingGroupResource;
use App\Filament\Resources\Finance\Vendors\VendorResource;
use App\Filament\Resources\Members\MemberResource;
use App\Filament\Resources\UserResource;
use App\Filament\Widgets\DepositsChart;
use App\Filament\Widgets\FinanceStatsOverview;
use App\Filament\Widgets\LatestNotificationsWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->resources([
                UserResource::class,
                MemberResource::class,
                ClaimResource::class,
                ClaimApprovalTemplateResource::class,
                BankAccountResource::class,
                DirectExpenseResource::class,
                DirectIncomeResource::class,
                CashReceiptResource::class,
                BankLedgerEntryResource::class,
                CustomerLedgerEntryResource::class,
                CustomerPostingGroupResource::class,
                CustomerResource::class,
                GeneralPostingSetupResource::class,
                GlAccountResource::class,
                GlEntryResource::class,
                NumberSeriesResource::class,
                PaymentMethodResource::class,
                PurchaseHeaderResource::class,
                PurchaseSetupResource::class,
                ScheduledInvoiceResource::class,
                SalesHeaderResource::class,
                SalesSetupResource::class,
                ServicePostingGroupResource::class,
                ServiceResource::class,
                VendorLedgerEntryResource::class,
                VendorPaymentResource::class,
                VendorPostingGroupResource::class,
                VendorResource::class,
            ])
            ->pages([
                Dashboard::class,
                MpesaSetupPage::class,
                SystemPage::class,
            ])
            ->navigationGroups([
                NavigationGroup::make('Finance — Income & Deposits'),
                NavigationGroup::make('Finance — Expenses & Claims'),
                NavigationGroup::make('Finance — Ledgers'),
                NavigationGroup::make('Finance — Setup'),
                NavigationGroup::make('Administration'),
            ])
            ->databaseNotifications()
            ->widgets([
                FinanceStatsOverview::class,
                DepositsChart::class,
                LatestNotificationsWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
