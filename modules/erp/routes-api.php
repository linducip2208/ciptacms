<?php
use Illuminate\Support\Facades\Route;
Route::get('/erp/ping', fn()=>response()->json(['module'=>'erp','ok'=>true]));
