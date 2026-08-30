<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WeatherController extends Controller
{
    public function getWeather(Request $request)
    {
        // 1. Frontend se city ka naam lena
        $city = $request->city;
        
        // 2. .env file se API key nikalna
        $apiKey = env('OPENWEATHER_API_KEY');
        
        // 3. OpenWeatherMap API ko call karna (units=metric se temperature Celsius mein aayega)
        $response = Http::get("https://api.openweathermap.org/data/2.5/weather?q={$city}&units=metric&appid={$apiKey}");

        // 4. Data check karke wapas bhejna
        if ($response->successful()) {
            return response()->json($response->json());
        }

        return response()->json(['error' => 'City not found'], 404);
    }
}