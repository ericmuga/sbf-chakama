<?php

namespace App\Providers\Filament;

use App\Filament\Resources\Chakama\ChakamaMemberReports\ChakamaMemberReportResource;
use App\Filament\Resources\Chakama\FundAccountResource;
use App\Filament\Resources\Chakama\FundsBankAccountResource;
use App\Filament\Resources\Chakama\FundWithdrawalResource;
use App\Filament\Resources\Chakama\ShareBillingRuns\ShareBillingRunResource;
use App\Filament\Resources\Chakama\ShareBillingScheduleResource;
use App\Filament\Resources\Chakama\ShareSubscriptionResource;
use App\Filament\Resources\Finance\BankAccounts\BankAccountResource;
use App\Filament\Resources\Finance\BankLedgerEntries\BankLedgerEntryResource;
use App\Filament\Resources\Finance\CashReceipts\CashReceiptResource;
use App\Filament\Resources\Finance\CustomerLedgerEntries\CustomerLedgerEntryResource;
use App\Filament\Resources\Finance\CustomerPostingGroups\CustomerPostingGroupResource;
use App\Filament\Resources\Finance\CustomerResource;
use App\Filament\Resources\Finance\DirectExpenses\DirectExpenseResource;
use App\Filament\Resources\Finance\DirectIncomes\DirectIncomeResource;
use App\Filament\Resources\Finance\GlAccounts\GlAccountResource;
use App\Filament\Resources\Finance\GlEntries\GlEntryResource;
use App\Filament\Resources\Finance\NumberSeries\NumberSeriesResource;
use App\Filament\Resources\Finance\PaymentMethods\PaymentMethodResource;
use App\Filament\Resources\Finance\ServicePostingGroups\ServicePostingGroupResource;
use App\Filament\Resources\Finance\Services\ServiceResource;
use App\Filament\Resources\Finance\VendorLedgerEntries\VendorLedgerEntryResource;
use App\Filament\Resources\Finance\VendorPayments\VendorPaymentResource;
use App\Filament\Resources\Finance\VendorPostingGroups\VendorPostingGroupResource;
use App\Filament\Resources\Finance\Vendors\VendorResource;
use App\Filament\Resources\Members\MemberResource;
use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Resources\UserResource;
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

class ChakamaPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('chakama')
            ->path('chakama')
            ->domain(config('app.chakama_domain'))
            ->login()
            ->brandName('Chakama Ranch')
            ->colors([
                'primary' => Color::Emerald,
            ])
            ->resources([
                // Members
                MemberResource::class,
                UserResource::class,
                // Projects
                ProjectResource::class,
                // Chakama — Shares & Funds
                ShareSubscriptionResource::class,
                ShareBillingScheduleResource::class,
                ShareBillingRunResource::class,
                ChakamaMemberReportResource::class,
                FundsBankAccountResource::class,
                FundAccountResource::class,
                FundWithdrawalResource::class,
                // Finance
                CashReceiptResource::class,
                DirectExpenseResource::class,
                DirectIncomeResource::class,
                BankAccountResource::class,
                BankLedgerEntryResource::class,
                CustomerLedgerEntryResource::class,
                CustomerPostingGroupResource::class,
                CustomerResource::class,
                GlAccountResource::class,
                GlEntryResource::class,
                NumberSeriesResource::class,
                PaymentMethodResource::class,
                ServicePostingGroupResource::class,
                ServiceResource::class,
                VendorLedgerEntryResource::class,
                VendorPaymentResource::class,
                VendorPostingGroupResource::class,
                VendorResource::class,
            ])
            ->pages([
                Dashboard::class,
            ])
            ->navigationGroups([
                NavigationGroup::make('Project Management'),
                NavigationGroup::make('Chakama — Shares'),
                NavigationGroup::make('Chakama — Funds'),
                NavigationGroup::make('Chakama — Settings'),
                NavigationGroup::make('Finance — Income & Deposits'),
                NavigationGroup::make('Finance — Expenses & Payments'),
                NavigationGroup::make('Finance — Ledgers'),
                NavigationGroup::make('Finance — Setup'),
                NavigationGroup::make('Chakama — Reports'),
                NavigationGroup::make('Administration'),
            ])
            ->databaseNotifications()
            ->widgets([
                FinanceStatsOverview::class,
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
