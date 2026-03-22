<?php

namespace App\Filament\Member\Pages;

use App\Filament\Member\Widgets\MemberStatsOverview;
use App\Filament\Member\Widgets\RecentClaimsWidget;
use App\Filament\Member\Widgets\RecentPaymentsWidget;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class MemberDashboard extends Page
{
    protected string $view = 'filament.member.pages.member-dashboard';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $title = 'My Portal';

    protected static bool $shouldRegisterNavigation = true;

    public function getWidgets(): array
    {
        return [
            MemberStatsOverview::class,
            RecentClaimsWidget::class,
            RecentPaymentsWidget::class,
        ];
    }

    public function getHeaderWidgets(): array
    {
        return [
            MemberStatsOverview::class,
        ];
    }

    public function getFooterWidgets(): array
    {
        return [
            RecentClaimsWidget::class,
            RecentPaymentsWidget::class,
        ];
    }
}
