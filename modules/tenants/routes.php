<?php
use Illuminate\Support\Facades\Route;
// Module tenants: web routes (kept independent from Core)
Route::prefix('m/tenants')->name('mod.tenants.')->group(function(){ Route::get('/', fn()=>response()->json(['module'=>'tenants','ok'=>true])); });
