<?php
use Illuminate\Support\Facades\Route;
Route::get('/media/ping', fn()=>response()->json(['module'=>'media','ok'=>true]));
