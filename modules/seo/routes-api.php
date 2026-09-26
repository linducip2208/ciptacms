<?php
use Illuminate\Support\Facades\Route;
Route::get('/seo/ping', fn()=>response()->json(['module'=>'seo','ok'=>true]));
