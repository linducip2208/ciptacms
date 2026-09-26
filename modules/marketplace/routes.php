<?php
use Illuminate\Support\Facades\Route;
// Module marketplace: web routes (kept independent from Core)
Route::prefix('m/marketplace')->name('mod.marketplace.')->group(function(){ Route::get('/', fn()=>response()->json(['module'=>'marketplace','ok'=>true])); });
