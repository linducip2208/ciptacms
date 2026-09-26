<?php
use Illuminate\Support\Facades\Route;
Route::get('/forms/ping', fn()=>response()->json(['module'=>'forms','ok'=>true]));
