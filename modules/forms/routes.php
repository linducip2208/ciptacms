<?php
use Illuminate\Support\Facades\Route;
// Module forms: web routes (kept independent from Core)
Route::prefix('m/forms')->name('mod.forms.')->group(function(){ Route::get('/', fn()=>response()->json(['module'=>'forms','ok'=>true])); });
