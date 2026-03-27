<?php

namespace App\Filament\Member\Pages;

use App\Filament\Member\Widgets\MemberStatsOverview;
use App\Filament\Member\Widgets\PortalSwitchWidget;
use App\Filament\Member\Widgets\RecentClaimsWidget;
use App\Filament\Member\Widgets\RecentPaymentsWidget;
use App\Filament\Member\Widgets\ShareSummaryWidget;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class MemberDashboard extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $title = 'My Portal';

    protected static bool $shouldRegisterNavigation = true;

    public function getWidgets(): array
    {
        return array_merge(
            $this->getHeaderWidgets(),
            $this->getFooterWidgets(),
        );
    }

    public function getHeaderWidgets(): array
    {
        $isChakama = Filament::getCurrentPanel()?->getId() === 'chakama-portal';

        $widgets = [PortalSwitchWidget::class];

        if ($isChakama) {
            $widgets[] = ShareSummaryWidget::class;
        } else {
            $widgets[] = MemberStatsOverview::class;
        }

        return $widgets;
    }

    public function getFooterWidgets(): array
    {
        $isChakama = Filament::getCurrentPanel()?->getId() === 'chakama-portal';

        if ($isChakama) {
            return [RecentPaymentsWidget::class];
        }

        return [RecentClaimsWidget::class, RecentPaymentsWidget::class];
    }
}
