<?php
use Illuminate\Support\Facades\Route;
Route::get('/hotel/ping', fn()=>response()->json(['module'=>'hotel','ok'=>true]));
