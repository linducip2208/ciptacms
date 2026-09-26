<?php
use Illuminate\Support\Facades\Route;
Route::get('/api/ping', fn()=>response()->json(['module'=>'api','ok'=>true]));
