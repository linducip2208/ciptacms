<?php
use Illuminate\Support\Facades\Route;
// Module pages: web routes (kept independent from Core)
Route::prefix('m/pages')->name('mod.pages.')->group(function(){ Route::get('/', fn()=>response()->json(['module'=>'pages','ok'=>true])); });
