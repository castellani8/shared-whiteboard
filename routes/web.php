<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DrawingController;

Route::get('/', function () {
    return view('whiteboard');
});

Route::get('/whiteboard/{any}', function () {
    return view('whiteboard');
})->where('any', '.*');

Route::post('api/stroke', [DrawingController::class, 'saveStroke']);
Route::get('api/whiteboard/{pass}', [DrawingController::class, 'getWhiteboardState']);
Route::post('api/clear-whiteboard', [DrawingController::class, 'clearWhiteboard']);
