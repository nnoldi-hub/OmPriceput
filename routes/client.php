<?php

use App\Http\Controllers\Client\PortalController;
use App\Http\Middleware\EnsureClientPortal;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', EnsureClientPortal::class])->prefix('client')->name('client.')->group(function () {
    Route::get('/', [PortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/tichete', [PortalController::class, 'tickets'])->name('tickets.index');
    Route::post('/tichete', [PortalController::class, 'storeTicket'])->name('tickets.store');
    Route::post('/tichete/{ticket}/comentarii', [PortalController::class, 'addTicketComment'])->name('tickets.comments');
    Route::get('/oferte', [PortalController::class, 'offers'])->name('offers.index');
    Route::patch('/oferte/{offer}/status', [PortalController::class, 'updateOfferStatus'])->name('offers.status');
    Route::get('/lucrari', [PortalController::class, 'works'])->name('works.index');
    Route::get('/lucrari/{installation}/raport', [PortalController::class, 'workReport'])->name('works.report');
    Route::get('/facturi', [PortalController::class, 'invoices'])->name('invoices.index');
    Route::get('/facturi/situatie/pdf', [PortalController::class, 'statementPdf'])->name('invoices.statement');
    Route::get('/facturi/{invoice}/pdf', [PortalController::class, 'invoicePdf'])->name('invoices.pdf');
    Route::get('/abonament', [PortalController::class, 'subscriptions'])->name('subscriptions.index');
    Route::get('/echipamente', [PortalController::class, 'equipment'])->name('equipment.index');
    Route::get('/comenzi-magazin', [PortalController::class, 'shopOrders'])->name('shop-orders.index');
    Route::get('/comenzi-magazin/{order}', [PortalController::class, 'shopOrderShow'])->name('shop-orders.show');
    Route::get('/notificari', [PortalController::class, 'notifications'])->name('notifications.index');
    Route::patch('/notificari/{notification}/citita', [PortalController::class, 'readNotification'])->name('notifications.read');
    Route::patch('/notificari/citeste-toate', [PortalController::class, 'readAllNotifications'])->name('notifications.read-all');
});
