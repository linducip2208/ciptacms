<?php
use Illuminate\Support\Facades\Route;
// Module pos: web routes (kept independent from Core)
Route::prefix('m/pos')->name('mod.pos.')->group(function(){ Route::get('/', fn()=>response()->json(['module'=>'pos','ok'=>true])); });
