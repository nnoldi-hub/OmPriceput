<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/public.php';

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::patch('/notificari/citeste-toate', [\App\Http\Controllers\NotificationController::class, 'markAllRead'])
        ->name('notifications.read-all');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/cautare', SearchController::class)->name('search');
});

require __DIR__.'/sales.php';
require __DIR__.'/technical.php';
require __DIR__.'/agenda.php';
require __DIR__.'/admin.php';
require __DIR__.'/client.php';

require __DIR__.'/auth.php';
