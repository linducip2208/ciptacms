<?php
use Illuminate\Support\Facades\Route;
Route::get('/jodohku/ping', fn()=>response()->json(['module'=>'jodohku','ok'=>true]));
