<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\InstallerController;

Route::get('/install', [InstallerController::class,'index']);
Route::post('/install', [InstallerController::class,'run'])->name('install.run');

Route::get('/login', [AuthController::class,'showLogin'])->name('login');
Route::post('/login', [AuthController::class,'login'])->name('login.attempt');
Route::get('/2fa/challenge', [AuthController::class,'showChallenge'])->name('2fa.challenge');
Route::post('/2fa/challenge', [AuthController::class,'verifyChallenge'])->name('2fa.verify');
Route::get('/oauth/{provider}', [AuthController::class,'socialRedirect'])->name('oauth.redirect');
Route::get('/oauth/{provider}/callback', [AuthController::class,'socialCallback'])->name('oauth.callback');
Route::get('/register', [AuthController::class,'showRegister'])->name('register');
Route::post('/register', [AuthController::class,'register'])->name('register.attempt');
Route::post('/logout', [AuthController::class,'logout'])->name('logout');
Route::get('/forgot-password', [AuthController::class,'showForgot'])->name('password.request');
Route::post('/forgot-password', [AuthController::class,'forgot'])->name('password.email');

Route::get('/', [HomeController::class,'index'])->name('home');
Route::get('/blog', [HomeController::class,'blog'])->name('blog');
Route::get('/blog/{slug}', [HomeController::class,'post'])->name('post.show');
Route::get('/p/{slug}', [HomeController::class,'page'])->name('page.show');
Route::get('/sitemap.xml', [HomeController::class,'sitemap']);
Route::get('/robots.txt', [HomeController::class,'robots']);
Route::get('/docs', fn()=>view('docs.index'))->name('docs');

require __DIR__.'/admin.php';
