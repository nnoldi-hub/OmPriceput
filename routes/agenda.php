<?php

use App\Http\Controllers\Agenda\AgendaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:admin'])->prefix('agenda')->name('agenda.')->group(function () {
    Route::get('/', [AgendaController::class, 'today'])->name('index');
    Route::get('/inbox', [AgendaController::class, 'index'])->name('inbox');
    Route::post('/iteme', [AgendaController::class, 'store'])->name('items.store');
    Route::patch('/iteme/{agenda_item}', [AgendaController::class, 'update'])->name('items.update');
    Route::patch('/iteme/{agenda_item}/bifeaza', [AgendaController::class, 'toggle'])->name('items.toggle');
    Route::delete('/iteme/{agenda_item}', [AgendaController::class, 'destroy'])->name('items.destroy');
});
