<?php

use App\Http\Controllers\Measurement\MeasurementController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:admin'])->prefix('masuratori')->name('measurements.')->group(function () {
    Route::get('/', [MeasurementController::class, 'index'])->name('index');
    Route::post('/', [MeasurementController::class, 'store'])->name('store');
    Route::patch('/{measurement}', [MeasurementController::class, 'update'])->name('update');
    Route::delete('/{measurement}', [MeasurementController::class, 'destroy'])->name('destroy');

    Route::post('/{measurement}/obiecte', [MeasurementController::class, 'storeObject'])->name('objects.store');
    Route::patch('/obiecte/{object}', [MeasurementController::class, 'updateObject'])->name('objects.update');
    Route::delete('/obiecte/{object}', [MeasurementController::class, 'destroyObject'])->name('objects.destroy');
});
