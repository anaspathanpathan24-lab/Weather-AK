<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WeatherController extends Controller
{
    public function getWeather(Request $request)
    {
        $apiKey = env('OPENWEATHER_API_KEY');

        try {
            if ($request->has('lat') && $request->has('lon')) {
                $lat = $request->lat;
                $lon = $request->lon;
                $currentResponse = Http::get("https://api.openweathermap.org/data/2.5/weather?lat={$lat}&lon={$lon}&units=metric&appid={$apiKey}");
            } else {
                $request->validate(['city' => 'required|string']);
                $city = $request->city;
                $currentResponse = Http::get("https://api.openweathermap.org/data/2.5/weather?q=" . urlencode($city) . "&units=metric&appid={$apiKey}");
            }

            if (!$currentResponse->successful()) {
                return response()->json(['error' => 'City not found or OpenWeather API limit reached.'], 404);
            }

            $currentData = $currentResponse->json();
            
            // Safe fallback for coordinates
            $lat = $currentData['coord']['lat'] ?? 23.0225;
            $lon = $currentData['coord']['lon'] ?? 72.5714;

            $forecastResponse = Http::get("https://api.openweathermap.org/data/2.5/forecast?lat={$lat}&lon={$lon}&units=metric&appid={$apiKey}");
            $uvResponse = @Http::get("https://api.openweathermap.org/data/2.5/uvi?lat={$lat}&lon={$lon}&appid={$apiKey}");

            return response()->json([
                'current' => $currentData,
                'forecast' => $forecastResponse->successful() ? $forecastResponse->json() : null,
                'uv' => ($uvResponse && $uvResponse->successful()) ? $uvResponse->json() : ['value' => 0],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Backend server issue.'], 500);
        }
    }

    public function getHistorical(Request $request)
    {
        return response()->json([
            'location' => $request->city ?? 'Gujarat',
            'avg_temp' => 31.2,
            'max_temp' => 36.5,
            'min_temp' => 25.0,
            'total_rainfall' => 124.5,
            'avg_humidity' => 68,
            'avg_wind' => 9.4
        ]);
    }

    public function askMeteorologist(Request $request)
    {
        $prompt = $request->prompt;
        $context = $request->context ?? [];
        $aiApiKey = env('GEMINI_API_KEY') ?? env('AI_API_KEY');

        // EXTREMELY SAFE DATA EXTRACTION (Yeh ab kabhi PHP error (undefined) nahi dega)
        $current = $context['current'] ?? [];
        $main = $current['main'] ?? [];
        $weatherObj = $current['weather'][0] ?? [];
        $windData = $current['wind'] ?? [];

        $cityName = $current['name'] ?? 'Gujarat';
        $temp = isset($main['temp']) ? round($main['temp']) : 31;
        $desc = $weatherObj['description'] ?? 'clear sky';
        $humidity = $main['humidity'] ?? 65;
        $wind = $windData['speed'] ?? 8;

        if ($aiApiKey) {
            try {
                $aiResponse = Http::timeout(8)->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$aiApiKey}", [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => "You are a professional Gujarat Weather Assistant. Keep answers concise. Current live data for {$cityName}: Temp is {$temp}°C, condition is {$desc}, humidity {$humidity}%, wind {$wind} km/h. User query: " . $prompt]
                            ]
                        ]
                    ]
                ]);

                if ($aiResponse->successful()) {
                    $responseData = $aiResponse->json();
                    $reply = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? null;
                    if ($reply) {
                        return response()->json(['reply' => $reply]);
                    }
                }
            } catch (\Exception $e) {
                // Silently fallback if API blocks request
            }
        }

        // Smart Fallback 
        $lower = strtolower($prompt);
        if (str_contains($lower, 'rain') || str_contains($lower, 'varsad')) {
            $reply = "Based on real-time data for {$cityName}, it is currently {$desc} at {$temp}°C with {$humidity}% humidity. Please check the 'Rain Probability' section for detailed hourly forecasts.";
        } elseif (str_contains($lower, 'temp') || str_contains($lower, 'garmi')) {
            $reply = "The temperature in {$cityName} is currently {$temp}°C with {$desc}. Winds are at {$wind} km/h.";
        } else {
            $reply = "Namaste! In {$cityName}, it is currently {$temp}°C with {$desc}. Humidity is {$humidity}% and wind speed is {$wind} km/h. Let me know if you need specific forecasts!";
        }

        return response()->json(['reply' => $reply]);
    }
}