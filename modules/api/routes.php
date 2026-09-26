<?php
use Illuminate\Support\Facades\Route;
// Module api: web routes (kept independent from Core)
Route::prefix('m/api')->name('mod.api.')->group(function(){ Route::get('/', fn()=>response()->json(['module'=>'api','ok'=>true])); });
