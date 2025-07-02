<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

Volt::route('tools', 'manage-tools')->name('tools');
Volt::route('tasks', 'manage-tasks')->name('tasks');
Volt::route('customers', 'manage-customers')->name('customers');
Volt::route('tire-job-orders', 'manage-tire-job-orders')->name('tire-job-orders');
Volt::route('dashboard', 'dashboard')->name('dashboard');

require __DIR__ . '/auth.php';
