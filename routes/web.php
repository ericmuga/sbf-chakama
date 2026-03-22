<?php

use App\Http\Controllers\TemplateController;
use App\Livewire\Members\MemberCard;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check()
    ? redirect('/admin')
    : redirect('/admin/login')
)->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::get('member', MemberCard::class)->name('member.card');
});

Route::middleware(['auth'])->prefix('admin/templates')->name('admin.templates.')->group(function () {
    Route::get('members', [TemplateController::class, 'members'])->name('members');
    Route::get('dependants', [TemplateController::class, 'dependants'])->name('dependants');
    Route::get('next-of-kin', [TemplateController::class, 'nextOfKin'])->name('next-of-kin');
});

require __DIR__.'/settings.php';
