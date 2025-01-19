<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('whiteboard');
});

Route::get('/{any}', function () {
    return view('whiteboard'); // Certifique-se de que esta view carrega o Vue App
})->where('any', '.*');

Route::post('/api/drawing', [\App\Http\Controllers\DrawingController::class, 'broadcastDrawing']);
Route::post('/api/stroke-end', [\App\Http\Controllers\DrawingController::class, 'broadcastStrokeEnd']);
