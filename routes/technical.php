<?php

use App\Http\Controllers\Technical\DashboardController;
use App\Http\Controllers\Technical\EquipmentController;
use App\Http\Controllers\Technical\InstallationController;
use App\Http\Controllers\Technical\PurchaseOrderController;
use App\Http\Controllers\Technical\ServiceController;
use App\Http\Controllers\Technical\SupplierController;
use App\Http\Controllers\Technical\TicketController;
use Illuminate\Support\Facades\Route;

/*
| Read routes (list / show / export / pdf) are guarded by the matching
| "*.view" permission, while every write route requires "*.manage".
| A role that can only see data never reaches a create / update / delete
| action, even if the route lives in the same resource.
*/
Route::middleware(['auth', 'verified'])->prefix('tehnic')->name('technical.')->group(function () {
    Route::middleware('role_or_permission:admin|tehnic|suport|clients.view|equipment.view|installations.view|tickets.view')->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');
    });

    Route::middleware('permission:tickets.view')->group(function () {
        Route::resource('tichete', TicketController::class)->only(['index'])->parameters(['tichete' => 'ticket'])->names('tickets');
    });

    Route::middleware('permission:tickets.manage')->group(function () {
        Route::resource('tichete', TicketController::class)->only(['create', 'store', 'edit', 'update', 'destroy'])->parameters(['tichete' => 'ticket'])->names('tickets');
        Route::patch('/tichete/{ticket}/status', [TicketController::class, 'updateStatus'])->name('tickets.status');
        Route::post('/tichete/{ticket}/comentarii', [TicketController::class, 'addComment'])->name('tickets.comments');
    });

    Route::middleware('permission:tickets.view')->group(function () {
        Route::get('/tichete/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    });

    Route::middleware('permission:equipment.view')->group(function () {
        Route::resource('echipamente', EquipmentController::class)->only(['index'])->parameters(['echipamente' => 'equipment'])->names('equipment');
        Route::resource('servicii', ServiceController::class)->only(['index'])->parameters(['servicii' => 'service'])->names('services');
        Route::get('/furnizori', [SupplierController::class, 'index'])->name('suppliers.index');
        Route::resource('comenzi-furnizori', PurchaseOrderController::class)->only(['index'])->parameters(['comenzi-furnizori' => 'purchaseOrder'])->names('purchase-orders');
    });

    Route::middleware('permission:equipment.manage')->group(function () {
        Route::resource('echipamente', EquipmentController::class)->only(['create', 'store', 'edit', 'update', 'destroy'])->parameters(['echipamente' => 'equipment'])->names('equipment');
        Route::patch('/echipamente/{equipment}/stoc', [EquipmentController::class, 'adjustStock'])->name('equipment.stock');

        Route::resource('servicii', ServiceController::class)->only(['create', 'store', 'edit', 'update', 'destroy'])->parameters(['servicii' => 'service'])->names('services');

        Route::post('/furnizori', [SupplierController::class, 'store'])->name('suppliers.store');
        Route::post('/furnizori/import', [SupplierController::class, 'import'])->name('suppliers.import');

        Route::resource('comenzi-furnizori', PurchaseOrderController::class)->only(['create', 'store', 'edit', 'update'])->parameters(['comenzi-furnizori' => 'purchaseOrder'])->names('purchase-orders');
        Route::patch('/comenzi-furnizori/{purchaseOrder}/receptioneaza', [PurchaseOrderController::class, 'receive'])->name('purchase-orders.receive');
        Route::post('/comenzi-furnizori/reaprovizionare', [PurchaseOrderController::class, 'replenish'])->name('purchase-orders.replenish');
        Route::patch('/comenzi-furnizori/{purchaseOrder}/anuleaza', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');
    });

    Route::middleware('permission:equipment.view')->group(function () {
        Route::get('/comenzi-furnizori/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('purchase-orders.show');
    });

    Route::middleware('permission:installations.view')->group(function () {
        Route::resource('instalari', InstallationController::class)->only(['index'])->parameters(['instalari' => 'installation'])->names('installations');
        Route::get('/instalari/calendar', [InstallationController::class, 'calendar'])->name('installations.calendar');
        Route::get('/instalari/{installation}/pdf', [InstallationController::class, 'pdf'])->name('installations.pdf');
    });

    Route::middleware('permission:installations.manage')->group(function () {
        Route::resource('instalari', InstallationController::class)->only(['create', 'store', 'edit', 'update', 'destroy'])->parameters(['instalari' => 'installation'])->names('installations');
        Route::patch('/instalari/{installation}/status', [InstallationController::class, 'updateStatus'])->name('installations.status');
        Route::patch('/instalari/{installation}/checklist', [InstallationController::class, 'updateChecklist'])->name('installations.checklist');
    });

    Route::middleware('permission:installations.view')->group(function () {
        Route::get('/instalari/{installation}', [InstallationController::class, 'show'])->name('installations.show');
    });
});