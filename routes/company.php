<?php

use App\Http\Controllers\Admin\CompanyProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Company Profile Module (admin)
|--------------------------------------------------------------------------
| Every route maps to a real controller method backed by a database table.
| The {resource} parameter is resolved through a fixed whitelist inside the
| controller, never from user input directly.
*/

Route::prefix('company')->name('company.')->group(function () {    Route::get('/', [CompanyProfileController::class, 'home'])->name('home');

    Route::get('/about', [CompanyProfileController::class, 'about'])->name('about');
    Route::post('/about', [CompanyProfileController::class, 'saveAbout'])->name('about.save');

    Route::get('/contact', [CompanyProfileController::class, 'contactSettings'])->name('contact');
    Route::post('/contact', [CompanyProfileController::class, 'saveContactSettings'])->name('contact.save');

    Route::get('/messages', [CompanyProfileController::class, 'messages'])->name('messages');
    Route::post('/messages/{message}/status', [CompanyProfileController::class, 'messageStatus'])->name('messages.status');
    Route::delete('/messages/{message}', [CompanyProfileController::class, 'destroyMessage'])->name('messages.destroy');

    Route::get('/applications', [CompanyProfileController::class, 'applications'])->name('applications');
    Route::post('/applications/{application}/status', [CompanyProfileController::class, 'applicationStatus'])->name('applications.status');

    // ---- Generic CRUD over a whitelisted resource slug ----------------
    Route::get('/data/{resource}', [CompanyProfileController::class, 'index'])->name('index');
    Route::get('/data/{resource}/trash', [CompanyProfileController::class, 'trash'])->name('trash');
    Route::get('/data/{resource}/create', [CompanyProfileController::class, 'create'])->name('create');
    Route::post('/data/{resource}', [CompanyProfileController::class, 'store'])->name('store');
    Route::get('/data/{resource}/{id}/edit', [CompanyProfileController::class, 'edit'])->name('edit');
    Route::put('/data/{resource}/{id}', [CompanyProfileController::class, 'update'])->name('update');
    Route::delete('/data/{resource}/{id}', [CompanyProfileController::class, 'destroy'])->name('destroy');
    Route::post('/data/{resource}/{id}/restore', [CompanyProfileController::class, 'restore'])->name('restore');
    Route::post('/data/{resource}/{id}/duplicate', [CompanyProfileController::class, 'duplicate'])->name('duplicate');
    Route::post('/data/{resource}/{id}/toggle', [CompanyProfileController::class, 'toggle'])->name('toggle');
    Route::post('/data/{resource}/reorder', [CompanyProfileController::class, 'reorder'])->name('reorder');

    // ---- Gallery albums (own image pipeline) --------------------------
    Route::get('/albums', [CompanyProfileController::class, 'albums'])->name('albums');
    Route::post('/albums', [CompanyProfileController::class, 'saveAlbum'])->name('albums.store');
    Route::put('/albums/{album}', [CompanyProfileController::class, 'saveAlbum'])->name('albums.update');
    Route::delete('/albums/{album}', [CompanyProfileController::class, 'destroyAlbum'])->name('albums.destroy');
    Route::get('/albums/{album}/images', [CompanyProfileController::class, 'albumImages'])->name('albums.images');
    Route::post('/albums/{album}/images', [CompanyProfileController::class, 'saveImage'])->name('albums.images.store');
    Route::delete('/albums/images/{image}', [CompanyProfileController::class, 'destroyImage'])->name('albums.images.destroy');
    Route::post('/albums/{album}/images/reorder', [CompanyProfileController::class, 'reorderImages'])->name('albums.images.reorder');
});
