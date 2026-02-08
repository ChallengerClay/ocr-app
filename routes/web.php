<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class,'index']);
Route::get('home', [HomeController::class,'index']);
Route::post('/process',[HomeController::class,'process'])->name('ocr.process');
