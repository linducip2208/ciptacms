<?php
use Illuminate\Support\Facades\Route;
Route::get('/ecommerce/ping', fn()=>response()->json(['module'=>'ecommerce','ok'=>true]));
