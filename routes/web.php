<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\TController;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/generate', [DocumentController::class, 'generate']);