<?php
use Illuminate\Support\Facades\Route;
Route::get('/pages/ping', fn()=>response()->json(['module'=>'pages','ok'=>true]));
