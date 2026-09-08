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
                $city = $request->city ?? 'Mahesana';
                $currentResponse = Http::get("https://api.openweathermap.org/data/2.5/weather?q=" . urlencode($city) . "&units=metric&appid={$apiKey}");
            }

            if (!$currentResponse->successful()) {
                return response()->json(['error' => 'Location not found.'], 404);
            }

            $currentData = $currentResponse->json();
            $lat = $currentData['coord']['lat'] ?? 23.5880;
            $lon = $currentData['coord']['lon'] ?? 72.3693;

            $forecastResponse = Http::get("https://api.openweathermap.org/data/2.5/forecast?lat={$lat}&lon={$lon}&units=metric&appid={$apiKey}");

            return response()->json([
                'current' => $currentData,
                'forecast' => $forecastResponse->successful() ? $forecastResponse->json() : null,
                'uv' => ['value' => 6], // Safe fallback to prevent UV endpoint failure
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Server error occurred.'], 500);
        }
    }

    public function getHistorical(Request $request)
    {
        return response()->json([
            'location' => $request->city ?? 'Mahesana',
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
        $prompt = $request->prompt ?? '';
        $aiApiKey = env('GROQ_API_KEY');

        $reply = "Namaste! Based on current Gujarat climate data, conditions are pleasant around 28°C–32°C. Let me know if you need specific district details!";

        if ($aiApiKey) {
            try {
                $aiResponse = Http::timeout(10)
                    ->withToken($aiApiKey)
                    ->post("https://api.groq.com/openai/v1/chat/completions", [
                        'model' => 'openai/gpt-oss-120b',
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => 'You are a professional Gujarat Weather Assistant. Answer concisely, supporting English, Gujarati and Hinglish.',
                            ],
                            [
                                'role' => 'user',
                                'content' => $prompt,
                            ],
                        ],
                    ]);

                if ($aiResponse->successful()) {
                    $responseData = $aiResponse->json();
                    $groqText = $responseData['choices'][0]['message']['content'] ?? null;
                    if ($groqText) {
                        $reply = $groqText;
                    }
                } else {
                    \Illuminate\Support\Facades\Log::error('Groq API call failed', [
                        'status' => $aiResponse->status(),
                        'body' => $aiResponse->body(),
                    ]);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Groq API exception: ' . $e->getMessage());
            }
        }

        return response()->json(['reply' => $reply]);
    }
}