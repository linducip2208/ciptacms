<?php
use Illuminate\Support\Facades\Route;
// Module jodohku: web routes (kept independent from Core)
Route::prefix('m/jodohku')->name('mod.jodohku.')->group(function(){ Route::get('/', fn()=>response()->json(['module'=>'jodohku','ok'=>true])); });
