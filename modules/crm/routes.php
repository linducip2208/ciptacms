<?php
use Illuminate\Support\Facades\Route;
// Module crm: web routes (kept independent from Core)
Route::prefix('m/crm')->name('mod.crm.')->group(function(){ Route::get('/', fn()=>response()->json(['module'=>'crm','ok'=>true])); });
