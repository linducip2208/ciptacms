<?php
use Illuminate\Support\Facades\Route;
Route::get('/lms/ping', fn()=>response()->json(['module'=>'lms','ok'=>true]));
