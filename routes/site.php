<?php

use App\Http\Controllers\Frontend\SiteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Company Website
|--------------------------------------------------------------------------
| Every route renders from database content. The order matters: fixed
| section slugs are matched before the generic /{slug} custom page route
| so a page cannot shadow a core section.
*/

Route::get('/', [SiteController::class, 'home'])->name('site.home');

Route::get('/about', [SiteController::class, 'about'])->name('site.about');

Route::get('/services', [SiteController::class, 'services'])->name('site.services');
Route::get('/services/{slug}', [SiteController::class, 'service'])->name('site.service');

Route::get('/products', [SiteController::class, 'products'])->name('site.products');
Route::get('/products/{slug}', [SiteController::class, 'product'])->name('site.product');

Route::get('/portfolio', [SiteController::class, 'portfolio'])->name('site.portfolio');
Route::get('/portfolio/{slug}', [SiteController::class, 'portfolioItem'])->name('site.portfolio.item');

Route::get('/team', [SiteController::class, 'team'])->name('site.team');
Route::get('/testimonials', [SiteController::class, 'testimonials'])->name('site.testimonials');
Route::get('/clients', [SiteController::class, 'clients'])->name('site.clients');
Route::get('/faq', [SiteController::class, 'faq'])->name('site.faq');

Route::get('/gallery', [SiteController::class, 'gallery'])->name('site.gallery');
Route::get('/gallery/{slug}', [SiteController::class, 'galleryAlbum'])->name('site.gallery.album');

Route::get('/careers', [SiteController::class, 'careers'])->name('site.careers');
Route::get('/careers/{slug}', [SiteController::class, 'career'])->name('site.career');
Route::post('/careers/{career}/apply', [SiteController::class, 'apply'])->name('site.career.apply');

Route::get('/contact', [SiteController::class, 'contact'])->name('site.contact');
Route::post('/contact', [SiteController::class, 'submitContact'])->name('site.contact.submit');

Route::get('/blog', [SiteController::class, 'blog'])->name('site.blog');
Route::get('/blog/{slug}', [SiteController::class, 'post'])->name('site.post');

// Comments. The admin has always had moderation screens (Comments, Word
// Filter, Reports); these are the public write paths they moderate.
Route::post('/blog/{post}/comments', [SiteController::class, 'storeComment'])
    ->middleware('throttle:comment')
    ->name('site.comments.store');
Route::post('/comments/{comment}/report', [SiteController::class, 'reportComment'])
    ->middleware('throttle:comment')
    ->name('site.comments.report');

// Custom CMS pages live under /p/{slug} (see routes/web.php)
