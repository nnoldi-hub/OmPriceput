<?php

use App\Http\Controllers\Sales\ActivityController;
use App\Http\Controllers\Sales\ClientController;
use App\Http\Controllers\Sales\DashboardController;
use App\Http\Controllers\Sales\OfferController;
use Illuminate\Support\Facades\Route;

/*
| Same rule as the technical module: "*.view" for reading, "*.manage" for
| every write, so sales data can be inspected without being editable.
*/
Route::middleware(['auth', 'verified'])->prefix('vanzari')->name('sales.')->group(function () {
    Route::middleware('role_or_permission:admin|vanzari|clients.view|offers.view|activities.view')->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');
    });

    Route::middleware('permission:clients.view')->group(function () {
        Route::resource('clienti', ClientController::class)->only(['index'])->parameters(['clienti' => 'client'])->names('clients');
        Route::get('/clienti/export', [ClientController::class, 'export'])->name('clients.export');
        Route::get('/clienti/{client}/situatie-financiara/pdf', [ClientController::class, 'statementPdf'])->name('clients.statement.pdf');
        Route::get('/clienti/{client}/situatie-financiara/excel', [ClientController::class, 'statementExcel'])->name('clients.statement.excel');
    });

    Route::middleware('permission:clients.manage')->group(function () {
        Route::resource('clienti', ClientController::class)->only(['create', 'store', 'edit', 'update', 'destroy'])->parameters(['clienti' => 'client'])->names('clients');
        Route::patch('/clienti/{client}/pipeline', [ClientController::class, 'updatePipeline'])->name('clients.pipeline');
    });

    Route::middleware('permission:clients.view')->group(function () {
        Route::get('/clienti/{client}', [ClientController::class, 'show'])->name('clients.show');
    });

    Route::middleware('permission:activities.view')->group(function () {
        Route::get('/activitati', [ActivityController::class, 'index'])->name('activities.index');
        Route::get('/activitati/creeaza', [ActivityController::class, 'create'])->name('activities.create');
    });

    Route::middleware('permission:activities.manage')->group(function () {
        Route::post('/activitati', [ActivityController::class, 'store'])->name('activities.store');
        Route::patch('/activitati/{activity}/status', [ActivityController::class, 'updateStatus'])->name('activities.status');
        Route::delete('/activitati/{activity}', [ActivityController::class, 'destroy'])->name('activities.destroy');
    });

    Route::middleware('permission:offers.view')->group(function () {
        Route::resource('oferte', OfferController::class)->only(['index'])->parameters(['oferte' => 'offer'])->names('offers');
        Route::get('/oferte/export', [OfferController::class, 'export'])->name('offers.export');
        Route::get('/oferte/{offer}/pdf', [OfferController::class, 'pdf'])->name('offers.pdf');
    });

    Route::middleware('permission:offers.manage')->group(function () {
        Route::resource('oferte', OfferController::class)->only(['create', 'store', 'edit', 'update', 'destroy'])->parameters(['oferte' => 'offer'])->names('offers');
        Route::patch('/oferte/{offer}/status', [OfferController::class, 'updateStatus'])->name('offers.status');
    });

    Route::middleware('permission:offers.view')->group(function () {
        Route::get('/oferte/{offer}', [OfferController::class, 'show'])->name('offers.show');
    });
});