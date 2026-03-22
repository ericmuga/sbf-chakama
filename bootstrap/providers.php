<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\MemberPanelProvider;
use App\Providers\FortifyServiceProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    MemberPanelProvider::class,
    FortifyServiceProvider::class,
];
