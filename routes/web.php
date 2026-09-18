<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WeatherController;

Route::get('/', function () {
    return view('welcome');
});

// Central Gujarat location search / resolver
Route::get('/api/locations/search', [WeatherController::class, 'searchLocations']);

// Weather APIs
Route::get('/api/weather', [WeatherController::class, 'getWeather']);
Route::get('/api/historical', [WeatherController::class, 'getHistorical']);
Route::get('/api/weather-analytics', [WeatherController::class, 'getWeatherAnalytics']);
Route::get('/api/air-quality', [WeatherController::class, 'getAirQuality']);

// AI Assistant
Route::post('/api/meteorologist', [WeatherController::class, 'askMeteorologist']);
