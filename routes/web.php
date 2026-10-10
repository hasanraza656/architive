<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SeoController;
use App\Http\Middleware\EnsureTrailingSlash;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Architive – public site routes
|--------------------------------------------------------------------------
| URL structure follows the client's copy deck. Every page has exactly one
| permanent, lowercase, trailing-slash URL; the slash-less variant 301s to it.
*/

Route::middleware(EnsureTrailingSlash::class)->group(function () {
    Route::get('/', [PageController::class, 'home'])->name('home');
    Route::get('about-us/', [PageController::class, 'about'])->name('about');
    Route::get('team/', [PageController::class, 'team'])->name('team');

    Route::get('services/', [PageController::class, 'services'])->name('services.index');
    Route::get('architectural-visualization-rendering/', [PageController::class, 'visualization'])->name('services.visualization');
    Route::get('bim-revit-scan-to-bim/', [PageController::class, 'bim'])->name('services.bim');
    Route::get('cad-drafting-services/', [PageController::class, 'cad'])->name('services.cad');
    // Production support is no longer a separate page: keep the old URL alive with a permanent redirect to Services.
    Route::get('architectural-outsourcing/', [PageController::class, 'outsourcing'])->name('services.outsourcing');

    Route::get('collaborations/', [PageController::class, 'collaborations'])->name('collaborations.index');
    Route::get('collaborations/{slug}/', [PageController::class, 'collaboration'])
        ->where('slug', '[a-z0-9\-]+')->name('collaborations.show');

    Route::get('blog/', [BlogController::class, 'index'])->name('blog.index');
    Route::get('blog/category/{slug}/', [BlogController::class, 'category'])->where('slug', '[a-z0-9\-]+')->name('blog.category');
    Route::get('blog/tag/{slug}/', [BlogController::class, 'tag'])->where('slug', '[a-z0-9\-]+')->name('blog.tag');
    Route::get('blog/{slug}/', [BlogController::class, 'show'])->where('slug', '[a-z0-9\-]+')->name('blog.show');

    Route::get('how-it-works/', [PageController::class, 'process'])->name('process');
    Route::get('faqs/', [PageController::class, 'faqs'])->name('faqs');
    Route::get('contact/', [PageController::class, 'contact'])->name('contact');

    Route::get('privacy-policy/', [PageController::class, 'privacy'])->name('privacy');
    Route::get('terms-of-service/', [PageController::class, 'terms'])->name('terms');
    Route::get('sitemap/', [PageController::class, 'sitemap'])->name('sitemap.html');
});

Route::post('contact/send', [ContactController::class, 'store'])->middleware('throttle:6,1')->name('contact.send');

Route::get('blog/feed.xml', [BlogController::class, 'feed'])->name('blog.feed');
Route::get('sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap.xml');
Route::get('robots.txt', [SeoController::class, 'robots'])->name('robots');
