<?php
use Illuminate\Support\Facades\Route;
Route::get('/tenants/ping', fn()=>response()->json(['module'=>'tenants','ok'=>true]));
