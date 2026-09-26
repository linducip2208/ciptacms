<?php
use Illuminate\Support\Facades\Route;
// Module hotel: web routes (kept independent from Core)
Route::prefix('m/hotel')->name('mod.hotel.')->group(function(){ Route::get('/', fn()=>response()->json(['module'=>'hotel','ok'=>true])); });
