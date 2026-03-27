<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\ChakamaMemberPanelProvider;
use App\Providers\Filament\ChakamaPanelProvider;
use App\Providers\Filament\MemberPanelProvider;
use App\Providers\FortifyServiceProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    ChakamaPanelProvider::class,
    MemberPanelProvider::class,
    ChakamaMemberPanelProvider::class,
    FortifyServiceProvider::class,
];
