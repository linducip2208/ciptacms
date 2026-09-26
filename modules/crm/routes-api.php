<?php
use Illuminate\Support\Facades\Route;
Route::get('/crm/ping', fn()=>response()->json(['module'=>'crm','ok'=>true]));
