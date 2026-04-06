<?php

namespace App\Filament\Member\Pages;

use App\Filament\Member\Widgets\MemberStatsOverview;
use App\Filament\Member\Widgets\RecentClaimsWidget;
use App\Filament\Member\Widgets\RecentPaymentsWidget;
use App\Filament\Member\Widgets\ShareSummaryWidget;
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
        $member = auth()->user()?->member;
        $widgets = [];

        if ($member?->is_sbf) {
            $widgets[] = MemberStatsOverview::class;
        }

        if ($member?->is_chakama) {
            $widgets[] = ShareSummaryWidget::class;
        }

        return $widgets;
    }

    public function getFooterWidgets(): array
    {
        $member = auth()->user()?->member;
        $widgets = [];

        if ($member?->is_sbf) {
            $widgets[] = RecentClaimsWidget::class;
        }

        $widgets[] = RecentPaymentsWidget::class;

        return $widgets;
    }
}
