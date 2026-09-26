<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\{AuthApiController,ResourceApiController,WebhookApiController};
Route::prefix('v1')->group(function(){
    Route::post('/auth/login', [AuthApiController::class,'login']);
    Route::post('/auth/register', [AuthApiController::class,'register']);
    Route::post('/webhooks/in/{key}', [WebhookApiController::class,'incoming']);
    Route::middleware('auth:sanctum')->group(function(){
        Route::get('/auth/me', [AuthApiController::class,'me']);
        Route::post('/auth/logout', [AuthApiController::class,'logout']);
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
