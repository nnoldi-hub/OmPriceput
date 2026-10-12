<?php

use App\Http\Controllers\Agenda\AgendaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:admin'])->prefix('agenda')->name('agenda.')->group(function () {
    Route::get('/', [AgendaController::class, 'index'])->name('index');
    Route::post('/iteme', [AgendaController::class, 'store'])->name('items.store');
    Route::patch('/iteme/{agenda_item}', [AgendaController::class, 'update'])->name('items.update');
    Route::delete('/iteme/{agenda_item}', [AgendaController::class, 'destroy'])->name('items.destroy');
});
