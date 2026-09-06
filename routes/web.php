<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WeatherController;

Route::get('/', function () {
    return view('welcome');
});

// API Routes for Weather Dashboard
Route::get('/api/weather', [WeatherController::class, 'getWeather']);
Route::get('/api/historical', [WeatherController::class, 'getHistorical']);

// API Route for AI Assistant
Route::post('/api/meteorologist', [WeatherController::class, 'askMeteorologist']);