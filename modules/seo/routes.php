<?php
use Illuminate\Support\Facades\Route;
// Module seo: web routes (kept independent from Core)
Route::prefix('m/seo')->name('mod.seo.')->group(function(){ Route::get('/', fn()=>response()->json(['module'=>'seo','ok'=>true])); });
