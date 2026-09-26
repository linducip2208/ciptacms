<?php
use Illuminate\Support\Facades\Route;
Route::get('/marketplace/ping', fn()=>response()->json(['module'=>'marketplace','ok'=>true]));
