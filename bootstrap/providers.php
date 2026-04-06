<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\ChakamaPanelProvider;
use App\Providers\Filament\MemberPanelProvider;
use App\Providers\FortifyServiceProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    ChakamaPanelProvider::class,
    MemberPanelProvider::class,
    FortifyServiceProvider::class,
];
