<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\{AuthApiController,ResourceApiController,WebhookApiController};
use App\Http\Controllers\Api\V1\{ContentTypeApiController,FormSubmissionController,MediaApiController,PageApiController,PostApiController,SearchApiController,TaxonomyApiController};
Route::prefix('v1')->name('api.v1.')->group(function(){
    Route::post('/auth/login', [AuthApiController::class,'login']);
    Route::post('/auth/register', [AuthApiController::class,'register']);
    Route::post('/webhooks/in/{key}', [WebhookApiController::class,'incoming']);

    // Public: a visitor on the website posting a builder form.
    Route::post('/forms/{slug}', [FormSubmissionController::class,'store'])
        ->middleware('throttle:form-submit');

    Route::middleware('auth:sanctum')->group(function(){
        Route::get('/auth/me', [AuthApiController::class,'me']);
        Route::post('/auth/logout', [AuthApiController::class,'logout']);

        // Named resource routes. These are registered before the generic
        // /{resource} catch-all below so they are not shadowed by it.
        Route::get('/pages', [PageApiController::class,'index']);
        Route::get('/pages/{slug}', [PageApiController::class,'show']);
        Route::get('/posts', [PostApiController::class,'index']);
        Route::get('/posts/{slug}', [PostApiController::class,'show']);
        Route::get('/categories', [TaxonomyApiController::class,'categories']);
        Route::get('/tags', [TaxonomyApiController::class,'tags']);
        Route::get('/search', [SearchApiController::class,'index']);
        Route::get('/media', [MediaApiController::class,'index']);
        Route::post('/media', [MediaApiController::class,'store']);
        Route::get('/media/{id}', [MediaApiController::class,'show']);
        Route::delete('/media/{id}', [MediaApiController::class,'destroy']);
        Route::get('/content-types', [ContentTypeApiController::class,'index']);
        Route::get('/content-types/{slug}', [ContentTypeApiController::class,'show']);
        Route::get('/content-types/{slug}/records', [ContentTypeApiController::class,'records']);
        Route::post('/content-types/{slug}/records', [ContentTypeApiController::class,'storeRecord']);
        Route::get('/content-types/{slug}/records/{id}', [ContentTypeApiController::class,'showRecord']);
        Route::put('/content-types/{slug}/records/{id}', [ContentTypeApiController::class,'updateRecord']);
        Route::delete('/content-types/{slug}/records/{id}', [ContentTypeApiController::class,'destroyRecord']);

        Route::get('/{resource}', [ResourceApiController::class,'index'])->where('resource','[a-z-]+');
        Route::post('/{resource}', [ResourceApiController::class,'store'])->where('resource','[a-z-]+');
        Route::get('/{resource}/{id}', [ResourceApiController::class,'show'])->where('resource','[a-z-]+');
        Route::put('/{resource}/{id}', [ResourceApiController::class,'update'])->where('resource','[a-z-]+');
        Route::delete('/{resource}/{id}', [ResourceApiController::class,'destroy'])->where('resource','[a-z-]+');
    });
});
Route::prefix('v2')->group(function(){
    Route::post('/auth/login', [\App\Http\Controllers\Api\V2\AuthApiController::class,'login']);
    Route::middleware('auth:sanctum')->group(function(){
        Route::get('/auth/me', [\App\Http\Controllers\Api\V2\AuthApiController::class,'me']);
        Route::post('/auth/logout', [\App\Http\Controllers\Api\V2\AuthApiController::class,'logout']);
        Route::get('/{resource}', [\App\Http\Controllers\Api\V2\ResourceApiController::class,'index'])->where('resource','[a-z-]+');
        Route::post('/{resource}', [\App\Http\Controllers\Api\V2\ResourceApiController::class,'store'])->where('resource','[a-z-]+');
        Route::get('/{resource}/{id}', [\App\Http\Controllers\Api\V2\ResourceApiController::class,'show'])->where('resource','[a-z-]+');
        Route::put('/{resource}/{id}', [\App\Http\Controllers\Api\V2\ResourceApiController::class,'update'])->where('resource','[a-z-]+');
        Route::delete('/{resource}/{id}', [\App\Http\Controllers\Api\V2\ResourceApiController::class,'destroy'])->where('resource','[a-z-]+');
    });
});
Route::get('/docs/openapi.json', fn()=>response()->file(public_path('docs/openapi.json')))->middleware('web');
