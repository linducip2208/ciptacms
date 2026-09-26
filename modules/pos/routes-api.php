<?php
use Illuminate\Support\Facades\Route;
Route::get('/pos/ping', fn()=>response()->json(['module'=>'pos','ok'=>true]));
