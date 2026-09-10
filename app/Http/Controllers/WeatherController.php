<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
                'uv' => ['value' => 6],
            ]);
        } catch (\Exception $e) {
            Log::error('Weather API exception: ' . $e->getMessage());

            return response()->json(['error' => 'Server error occurred.'], 500);
        }
    }

    /**
     * Return real historical weather statistics for a Gujarat district.
     *
     * The location is resolved through Open-Meteo Geocoding and the
     * historical values are retrieved from Open-Meteo's archive API.
     */
    public function getHistorical(Request $request)
    {
        $validated = $request->validate([
            'city' => ['required', 'string', 'max:100'],
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        try {
            $city = trim($validated['city']);
            $from = Carbon::createFromFormat('Y-m-d', $validated['from'])->startOfDay();
            $to = Carbon::createFromFormat('Y-m-d', $validated['to'])->startOfDay();

            // Historical Weather is meant for completed/past dates.
            $latestCompletedDate = Carbon::yesterday()->startOfDay();
            $earliestSupportedDate = Carbon::create(1940, 1, 1)->startOfDay();

            if ($from->lt($earliestSupportedDate)) {
                return response()->json([
                    'error' => 'Historical data is available from 01-01-1940 onward.',
                ], 422);
            }

            if ($to->gt($latestCompletedDate)) {
                return response()->json([
                    'error' => 'Please select a To Date up to yesterday for historical analysis.',
                ], 422);
            }

            // Resolve district/city to Gujarat coordinates.
            $locationCacheKey = 'historical_location:' . strtolower($city);

            $location = Cache::remember($locationCacheKey, now()->addDay(), function () use ($city) {
                $response = Http::timeout(10)->get(
                    'https://geocoding-api.open-meteo.com/v1/search',
                    [
                        'name' => $city . ', Gujarat',
                        'count' => 10,
                        'language' => 'en',
                        'format' => 'json',
                        'countryCode' => 'IN',
                    ]
                );

                if (!$response->successful()) {
                    return null;
                }

                $results = $response->json('results', []);

                foreach ($results as $result) {
                    $countryCode = strtoupper((string) ($result['country_code'] ?? ''));
                    $admin1 = strtolower(trim((string) ($result['admin1'] ?? '')));

                    if ($countryCode === 'IN' && $admin1 === 'gujarat') {
                        return [
                            'name' => $result['name'] ?? $city,
                            'latitude' => $result['latitude'] ?? null,
                            'longitude' => $result['longitude'] ?? null,
                            'timezone' => $result['timezone'] ?? 'Asia/Kolkata',
                        ];
                    }
                }

                return null;
            });

            if (!$location || !isset($location['latitude'], $location['longitude'])) {
                return response()->json([
                    'error' => "Could not find a Gujarat location for '{$city}'. Please select a valid Gujarat district.",
                ], 404);
            }

            $cacheKey = sprintf(
                'historical_weather:%s:%s:%s',
                strtolower($city),
                $from->toDateString(),
                $to->toDateString()
            );

            $historical = Cache::remember($cacheKey, now()->addHour(), function () use ($location, $from, $to) {
                $response = Http::timeout(20)->get(
                    'https://archive-api.open-meteo.com/v1/archive',
                    [
                        'latitude' => $location['latitude'],
                        'longitude' => $location['longitude'],
                        'start_date' => $from->toDateString(),
                        'end_date' => $to->toDateString(),
                        'daily' => implode(',', [
                            'temperature_2m_mean',
                            'temperature_2m_max',
                            'temperature_2m_min',
                            'precipitation_sum',
                            'relative_humidity_2m_mean',
                            'wind_speed_10m_mean',
                        ]),
                        'temperature_unit' => 'celsius',
                        'wind_speed_unit' => 'kmh',
                        'precipitation_unit' => 'mm',
                        'timezone' => 'Asia/Kolkata',
                    ]
                );

                if (!$response->successful()) {
                    Log::error('Open-Meteo historical request failed', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);

                    throw new \RuntimeException('Historical weather provider request failed.');
                }

                return $response->json();
            });

            $daily = $historical['daily'] ?? null;

            if (!$daily || empty($daily['time'])) {
                return response()->json([
                    'error' => 'No historical weather data was returned for the selected date range.',
                ], 404);
            }

            $means = $this->cleanNumericSeries($daily['temperature_2m_mean'] ?? []);
            $maxima = $this->cleanNumericSeries($daily['temperature_2m_max'] ?? []);
            $minima = $this->cleanNumericSeries($daily['temperature_2m_min'] ?? []);
            $rain = $this->cleanNumericSeries($daily['precipitation_sum'] ?? []);
            $humidity = $this->cleanNumericSeries($daily['relative_humidity_2m_mean'] ?? []);
            $wind = $this->cleanNumericSeries($daily['wind_speed_10m_mean'] ?? []);

            if (empty($means) || empty($maxima) || empty($minima)) {
                return response()->json([
                    'error' => 'Historical temperature data is unavailable for the selected date range.',
                ], 404);
            }

            $dayCount = count($daily['time']);

            $dailyRecords = [];

            for ($i = 0; $i < $dayCount; $i++) {
                $dailyRecords[] = [
                    'date' => $daily['time'][$i] ?? null,
                    'avg_temp' => isset($daily['temperature_2m_mean'][$i])
                        ? round((float) $daily['temperature_2m_mean'][$i], 1)
                        : null,
                    'max_temp' => isset($daily['temperature_2m_max'][$i])
                        ? round((float) $daily['temperature_2m_max'][$i], 1)
                        : null,
                    'min_temp' => isset($daily['temperature_2m_min'][$i])
                        ? round((float) $daily['temperature_2m_min'][$i], 1)
                        : null,
                    'rainfall' => isset($daily['precipitation_sum'][$i])
                        ? round((float) $daily['precipitation_sum'][$i], 1)
                        : null,
                    'humidity' => isset($daily['relative_humidity_2m_mean'][$i])
                        ? round((float) $daily['relative_humidity_2m_mean'][$i], 1)
                        : null,
                    'wind' => isset($daily['wind_speed_10m_mean'][$i])
                        ? round((float) $daily['wind_speed_10m_mean'][$i], 1)
                        : null,
                ];
            }

            return response()->json([
                'location' => $location['name'] ?? $city,
                'latitude' => $location['latitude'],
                'longitude' => $location['longitude'],
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'days' => $dayCount,
                'avg_temp' => round(array_sum($means) / count($means), 1),
                'max_temp' => round(max($maxima), 1),
                'min_temp' => round(min($minima), 1),
                'total_rainfall' => round(array_sum($rain), 1),
                'avg_humidity' => round(array_sum($humidity) / max(count($humidity), 1), 1),
                'avg_wind' => round(array_sum($wind) / max(count($wind), 1), 1),
                'daily' => $dailyRecords,
                'source' => 'Open-Meteo Historical Weather API',
            ]);
        } catch (\Throwable $e) {
            Log::error('Historical weather exception', [
                'message' => $e->getMessage(),
                'city' => $request->input('city'),
                'from' => $request->input('from'),
                'to' => $request->input('to'),
            ]);

            return response()->json([
                'error' => 'Unable to fetch historical weather data right now. Please try again.',
            ], 502);
        }
    }

    private function cleanNumericSeries(array $values): array
    {
        return array_values(array_filter($values, static function ($value) {
            return is_numeric($value);
        }));
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
                    Log::error('Groq API call failed', [
                        'status' => $aiResponse->status(),
                        'body' => $aiResponse->body(),
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Groq API exception: ' . $e->getMessage());
            }
        }

        return response()->json(['reply' => $reply]);
    }
}
