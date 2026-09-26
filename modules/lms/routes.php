<?php
use Illuminate\Support\Facades\Route;
// Module lms: web routes (kept independent from Core)
Route::prefix('m/lms')->name('mod.lms.')->group(function(){ Route::get('/', fn()=>response()->json(['module'=>'lms','ok'=>true])); });
