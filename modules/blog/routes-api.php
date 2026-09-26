<?php
use Illuminate\Support\Facades\Route;
Route::get('/blog/ping', fn()=>response()->json(['module'=>'blog','ok'=>true]));
