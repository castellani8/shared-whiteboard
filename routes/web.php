<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/api/drawing', [\App\Http\Controllers\DrawingController::class, 'broadcastDrawing']);
Route::post('/api/stroke-end', [\App\Http\Controllers\DrawingController::class, 'broadcastStrokeEnd']);
