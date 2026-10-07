<?php

use App\Http\Controllers\Public\BlogController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\LegalController;
use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\Public\ShopController;
use App\Http\Controllers\Public\SitemapController;
use Illuminate\Support\Facades\Route;

Route::name('public.')->middleware('track.views')->group(function () {
    Route::get('/', HomeController::class)->name('home');
    Route::get('/despre', [PageController::class, 'about'])->name('about');
    Route::get('/servicii', [PageController::class, 'services'])->name('services');
    Route::get('/cerere-deviz', [ContactController::class, 'quote'])->name('quote');
    Route::get('/cerere-deviz/zile', [ContactController::class, 'dates'])
    ->middleware('throttle:60,1')
    ->name('quote.dates');
Route::get('/cerere-deviz/sloturi', [ContactController::class, 'slots'])
    ->middleware('throttle:60,1')
    ->name('quote.slots');
    Route::get('/contact', [ContactController::class, 'index'])->name('contact');
    Route::get('/termeni', [LegalController::class, 'terms'])->name('terms');
    Route::get('/confidentialitate', [LegalController::class, 'privacy'])->name('privacy');
    Route::post('/cerere-deviz', [ContactController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('lead.store');
    Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
    Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

    Route::get('/magazin', [ShopController::class, 'index'])->name('shop.index');
    Route::get('/magazin/cos', [ShopController::class, 'cart'])->name('shop.cart');
    Route::get('/magazin/comanda/{orderNumber}', [ShopController::class, 'confirmation'])->name('shop.confirmation');
    Route::post('/magazin/comanda', [ShopController::class, 'checkout'])
        ->middleware('throttle:10,1')
        ->name('shop.checkout');
    Route::get('/magazin/{slug}', [ShopController::class, 'show'])->name('shop.show');
});

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', function () {
    return response("User-agent: *\nAllow: /\nSitemap: ".route('sitemap'), 200)
        ->header('Content-Type', 'text/plain');
})->name('robots');
