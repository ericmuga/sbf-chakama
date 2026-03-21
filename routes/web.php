<?php

use App\Livewire\Members\MemberCard;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::get('member', MemberCard::class)->name('member.card');
});

require __DIR__.'/settings.php';
