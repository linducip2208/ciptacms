<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\SiteController;
use App\Http\Controllers\InstallerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Marketplace pairing wizard
|--------------------------------------------------------------------------
| Registered before anything else so /__pair always resolves, even while
| the application is still locked. See app/Http/Middleware/RequirePair.php.
*/
require __DIR__.'/pair-routes.php';

Route::get('/install', [InstallerController::class, 'index']);
Route::get('/install/database', [InstallerController::class, 'database'])->name('install.database');
Route::post('/install', [InstallerController::class, 'run'])->name('install.run');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
Route::get('/2fa/challenge', [AuthController::class, 'showChallenge'])->name('2fa.challenge');
Route::post('/2fa/challenge', [AuthController::class, 'verifyChallenge'])->name('2fa.verify');
Route::get('/oauth/{provider}', [AuthController::class, 'socialRedirect'])->name('oauth.redirect');
Route::get('/oauth/{provider}/callback', [AuthController::class, 'socialCallback'])->name('oauth.callback');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.attempt');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/forgot-password', [AuthController::class, 'showForgot'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'forgot'])->name('password.email');

Route::get('/sitemap.xml', [HomeController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [HomeController::class, 'robots'])->name('robots');
Route::get('/docs', fn () => view('docs.index'))->name('docs');

require __DIR__.'/site.php';

Route::get('/p/{slug}', [SiteController::class, 'page'])->name('page.show');

require __DIR__.'/admin.php';
