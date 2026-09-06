<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WeatherController extends Controller
{
    public function getWeather(Request $request)
    {
        $request->validate(['city' => 'required|string']);
        $city = $request->city;
        $apiKey = env('OPENWEATHER_API_KEY');

        // 1. Fetch Current Weather (Provides lat & lon needed for other endpoints)
        $currentResponse = Http::get("https://api.openweathermap.org/data/2.5/weather?q={$city}&units=metric&appid={$apiKey}");

        if (!$currentResponse->successful()) {
            return response()->json(['error' => 'City not found'], 404);
        }

        $currentData = $currentResponse->json();
        $lat = $currentData['coord']['lat'];
        $lon = $currentData['coord']['lon'];

        // 2. Fetch 5-Day / 3-Hour Forecast
        $forecastResponse = Http::get("https://api.openweathermap.org/data/2.5/forecast?lat={$lat}&lon={$lon}&units=metric&appid={$apiKey}");

        // 3. Fetch UV Index
        $uvResponse = Http::get("https://api.openweathermap.org/data/2.5/uvi?lat={$lat}&lon={$lon}&appid={$apiKey}");

        // Return all data beautifully packaged for the frontend
        return response()->json([
            'current' => $currentData,
            'forecast' => $forecastResponse->successful() ? $forecastResponse->json() : null,
            'uv' => $uvResponse->successful() ? $uvResponse->json() : null,
        ]);
    }
}