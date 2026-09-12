<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WeatherController;

Route::get('/', function () {
    return view('welcome');
});

// API Routes for Weather Dashboard 
Route::get('/api/weather', [WeatherController::class, 'getWeather']); 
Route::get('/api/historical', [WeatherController::class, 'getHistorical']); 
Route::get('/api/weather-analytics', [WeatherController::class, 'getWeatherAnalytics']);

// API Route for AI Assistant
Route::post('/api/meteorologist', [WeatherController::class, 'askMeteorologist']);

// Add this line to routes/web.php with your other API routes:
Route::get('/api/air-quality', [WeatherController::class, 'getAirQuality']);
