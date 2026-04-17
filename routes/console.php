<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Generate recurring Chakama share invoices based on billing schedules
Schedule::command('chakama:generate-invoices')->dailyAt('06:00');

// Auto-process Chakama share billing runs whose billing date has arrived
Schedule::command('chakama:process-billing-runs')->dailyAt('06:30');

// Check and notify overdue Chakama share payments, auto-suspend 90+ days overdue
Schedule::command('chakama:check-overdue')->dailyAt('07:00');

// Send payment due reminders to SBF members with invoices due within 7 days
Schedule::command('members:payment-reminders')->weeklyOn(1, '08:00');

// Remind approvers about pending claim approvals older than 48 hours
Schedule::command('claims:remind-approvers')->dailyAt('09:00');

// Check and notify overdue projects
Schedule::command('projects:check-overdue')->dailyAt('07:30');
