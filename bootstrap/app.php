<?php

use App\Console\Commands\CheckOverdueProjects;
use App\Console\Commands\MemberPaymentReminders;
use App\Console\Commands\RecalculateProjectSpent;
use App\Console\Commands\RemindApprovers;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command(RemindApprovers::class)->dailyAt('09:00');
        $schedule->command(MemberPaymentReminders::class)->weeklyOn(1, '08:00');
        $schedule->command(CheckOverdueProjects::class)->dailyAt('08:00');
        $schedule->command(RecalculateProjectSpent::class)->weeklyOn(0, '00:00');
    })
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
