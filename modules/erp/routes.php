<?php
use Illuminate\Support\Facades\Route;
// Module erp: web routes (kept independent from Core)
Route::prefix('m/erp')->name('mod.erp.')->group(function(){ Route::get('/', fn()=>response()->json(['module'=>'erp','ok'=>true])); });
