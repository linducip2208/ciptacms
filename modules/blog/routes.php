<?php
use Illuminate\Support\Facades\Route;
// Module blog: web routes (kept independent from Core)
Route::prefix('m/blog')->name('mod.blog.')->group(function(){ Route::get('/', fn()=>response()->json(['module'=>'blog','ok'=>true])); });
