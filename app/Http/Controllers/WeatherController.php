<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WeatherController extends Controller
{
    public function getWeather(Request $request)
    {
        $apiKey = env('OPENWEATHER_API_KEY');

        // Feature 7: Handle Latitude & Longitude from Geolocation
        if ($request->has('lat') && $request->has('lon')) {
            $lat = $request->lat;
            $lon = $request->lon;
            $currentResponse = Http::get("https://api.openweathermap.org/data/2.5/weather?lat={$lat}&lon={$lon}&units=metric&appid={$apiKey}");
        } else {
            // Standard City Search
            $request->validate(['city' => 'required|string']);
            $city = $request->city;
            $currentResponse = Http::get("https://api.openweathermap.org/data/2.5/weather?q={$city}&units=metric&appid={$apiKey}");
        }

        if (!$currentResponse->successful()) {
            return response()->json(['error' => 'Location not found'], 404);
        }

        $currentData = $currentResponse->json();
        
        // Extract accurate coordinates for forecast APIs
        $lat = $currentData['coord']['lat'];
        $lon = $currentData['coord']['lon'];

        // Fetch Extended Features Data
        $forecastResponse = Http::get("https://api.openweathermap.org/data/2.5/forecast?lat={$lat}&lon={$lon}&units=metric&appid={$apiKey}");
        $uvResponse = Http::get("https://api.openweathermap.org/data/2.5/uvi?lat={$lat}&lon={$lon}&appid={$apiKey}");

        return response()->json([
            'current' => $currentData,
            'forecast' => $forecastResponse->successful() ? $forecastResponse->json() : null,
            'uv' => $uvResponse->successful() ? $uvResponse->json() : null,
        ]);
    }
}