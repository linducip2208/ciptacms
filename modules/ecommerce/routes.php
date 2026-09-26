<?php
use Illuminate\Support\Facades\Route;
// Module ecommerce: web routes (kept independent from Core)
Route::prefix('m/ecommerce')->name('mod.ecommerce.')->group(function(){ Route::get('/', fn()=>response()->json(['module'=>'ecommerce','ok'=>true])); });
