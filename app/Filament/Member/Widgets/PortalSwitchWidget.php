<?php

namespace App\Filament\Member\Widgets;

use Filament\Facades\Filament;
use Filament\Widgets\Widget;

class PortalSwitchWidget extends Widget
{
    protected string $view = 'filament.member.widgets.portal-switch-widget';

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $member = auth()->user()?->member;

        return $member?->is_chakama && $member?->is_sbf;
    }

    public function getOtherPortalLabel(): string
    {
        return $this->isOnChakama()
            ? 'SOBA Benevolent Fund Portal'
            : 'Chakama Ranch Portal';
    }

    public function getOtherPortalUrl(): string
    {
        return $this->isOnChakama()
            ? route('filament.member.pages.member-dashboard')
            : route('filament.chakama-portal.pages.member-dashboard');
    }

    public function getCurrentPortalLabel(): string
    {
        return $this->isOnChakama()
            ? 'Chakama Ranch Portal'
            : 'SOBA Benevolent Fund Portal';
    }

    private function isOnChakama(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'chakama-portal';
    }
}
