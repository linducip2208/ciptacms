<?php
use Illuminate\Support\Facades\Route;
// Module media: web routes (kept independent from Core)
Route::prefix('m/media')->name('mod.media.')->group(function(){ Route::get('/', fn()=>response()->json(['module'=>'media','ok'=>true])); });
