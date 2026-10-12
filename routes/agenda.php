<?php

use App\Http\Controllers\Agenda\AgendaController;
use App\Http\Controllers\Agenda\ProjectController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:admin'])->prefix('agenda')->name('agenda.')->group(function () {
    Route::get('/', [AgendaController::class, 'today'])->name('index');
    Route::get('/inbox', [AgendaController::class, 'index'])->name('inbox');

    Route::get('/proiecte', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('/proiecte/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::post('/proiecte', [ProjectController::class, 'store'])->name('projects.store');
    Route::post('/proiecte/{project}/masuratori', [ProjectController::class, 'storeMeasurement'])->name('projects.measurements.store');
    Route::patch('/proiecte/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('/proiecte/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');

    Route::post('/iteme', [AgendaController::class, 'store'])->name('items.store');
    Route::patch('/iteme/{agenda_item}', [AgendaController::class, 'update'])->name('items.update');
    Route::patch('/iteme/{agenda_item}/bifeaza', [AgendaController::class, 'toggle'])->name('items.toggle');
    Route::delete('/iteme/{agenda_item}', [AgendaController::class, 'destroy'])->name('items.destroy');
});
