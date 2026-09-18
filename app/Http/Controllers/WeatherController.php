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

        if (!$apiKey) {
            return response()->json([
                'error' => 'OpenWeather API key is not configured.'
            ], 503);
        }

        try {
            $hasCoordinates = $request->has('lat') || $request->has('lon');

            if ($hasCoordinates) {
                if (!$request->has('lat') || !$request->has('lon')) {
                    return response()->json([
                        'error' => 'Both latitude and longitude are required.'
                    ], 400);
                }

                if (
                    !is_numeric($request->lat) ||
                    !is_numeric($request->lon) ||
                    (float) $request->lat < -90 ||
                    (float) $request->lat > 90 ||
                    (float) $request->lon < -180 ||
                    (float) $request->lon > 180
                ) {
                    return response()->json([
                        'error' => 'Invalid latitude or longitude.'
                    ], 400);
                }

                $lat = (float) $request->lat;
                $lon = (float) $request->lon;
                $location = null;
            } else {
                $city = trim((string) $request->input('city', ''));

                if ($city === '') {
                    return response()->json([
                        'error' => 'Please enter a Gujarat city or district name.'
                    ], 400);
                }

                $location = $this->resolveGujaratLocation(
                    $city,
                    'weather'
                );

                if (
                    !$location ||
                    !is_numeric($location['latitude'] ?? null) ||
                    !is_numeric($location['longitude'] ?? null)
                ) {
                    return response()->json([
                        'error' => "Could not find a Gujarat location for '{$city}'. Please check the spelling and try again."
                    ], 404);
                }

                $lat = (float) $location['latitude'];
                $lon = (float) $location['longitude'];
            }

            $cacheKey = sprintf(
                'weather_current_forecast:%s:%s',
                number_format($lat, 4, '.', ''),
                number_format($lon, 4, '.', '')
            );

            $weatherData = Cache::remember(
                $cacheKey,
                now()->addMinutes(1),
                function () use ($apiKey, $lat, $lon) {
                    $currentResponse = Http::timeout(10)->get(
                        'https://api.openweathermap.org/data/2.5/weather',
                        [
                            'lat' => $lat,
                            'lon' => $lon,
                            'units' => 'metric',
                            'appid' => $apiKey,
                        ]
                    );

                    if (!$currentResponse->successful()) {
                        $status = $currentResponse->status();

                        Log::error(
                            'OpenWeather current weather request failed',
                            [
                                'status' => $status,
                                'body' => $currentResponse->body(),
                                'lat' => $lat,
                                'lon' => $lon,
                            ]
                        );

                        if ($status === 401) {
                            throw new \RuntimeException('OPENWEATHER_AUTH_ERROR');
                        }

                        if ($status === 429) {
                            throw new \RuntimeException('OPENWEATHER_RATE_LIMIT');
                        }

                        throw new \RuntimeException('OPENWEATHER_PROVIDER_ERROR');
                    }

                    $currentData = $currentResponse->json();

                    if (
                        !is_array($currentData) ||
                        !isset($currentData['coord']['lat'], $currentData['coord']['lon']) ||
                        !isset($currentData['main'], $currentData['weather'][0])
                    ) {
                        throw new \RuntimeException('OPENWEATHER_INVALID_RESPONSE');
                    }

                    $resolvedLat = (float) $currentData['coord']['lat'];
                    $resolvedLon = (float) $currentData['coord']['lon'];

                    $forecastResponse = Http::timeout(10)->get(
                        'https://api.openweathermap.org/data/2.5/forecast',
                        [
                            'lat' => $resolvedLat,
                            'lon' => $resolvedLon,
                            'units' => 'metric',
                            'appid' => $apiKey,
                        ]
                    );

                    if (!$forecastResponse->successful()) {
                        Log::warning(
                            'OpenWeather forecast request failed',
                            [
                                'status' => $forecastResponse->status(),
                                'body' => $forecastResponse->body(),
                                'lat' => $resolvedLat,
                                'lon' => $resolvedLon,
                            ]
                        );
                    }

                    return [
                        'current' => $currentData,
                        'forecast' => $forecastResponse->successful()
                            ? $forecastResponse->json()
                            : null,
                        'uv' => $this->getUvIndex(
                            $resolvedLat,
                            $resolvedLon
                        ),
                    ];
                }
            );

            if (!is_array($weatherData) || !isset($weatherData['current'])) {
                return response()->json([
                    'error' => 'Weather data is currently unavailable.'
                ], 502);
            }

            if ($location && !empty($location['name'])) {
                $weatherData['current']['name'] = $location['name'];
            }

            $weatherData['location'] = $location;

            return response()->json($weatherData);

        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'OPENWEATHER_AUTH_ERROR') {
                return response()->json([
                    'error' => 'Weather service authentication failed. Please check the OpenWeather API key.'
                ], 503);
            }

            if ($e->getMessage() === 'OPENWEATHER_RATE_LIMIT') {
                return response()->json([
                    'error' => 'Weather service rate limit reached. Please try again shortly.'
                ], 429);
            }

            if ($e->getMessage() === 'OPENWEATHER_INVALID_RESPONSE') {
                return response()->json([
                    'error' => 'Weather service returned an invalid response.'
                ], 502);
            }

            Log::error(
                'Weather provider exception',
                [
                    'message' => $e->getMessage(),
                    'city' => $request->input('city'),
                    'lat' => $request->input('lat'),
                    'lon' => $request->input('lon'),
                ]
            );

            return response()->json([
                'error' => 'Weather service is temporarily unavailable.'
            ], 502);

        } catch (\Throwable $e) {
            Log::error(
                'Weather API exception',
                [
                    'message' => $e->getMessage(),
                    'city' => $request->input('city'),
                    'lat' => $request->input('lat'),
                    'lon' => $request->input('lon'),
                ]
            );

            return response()->json([
                'error' => 'Server error occurred while loading weather data.'
            ], 500);
        }
    }


    /**
     * Central Gujarat location search endpoint.
     */
    public function searchLocations(Request $request)
    {
        $validated = $request->validate([
            'q' => [
                'required',
                'string',
                'min:2',
                'max:100'
            ],
        ]);

        $query = trim($validated['q']);

        try {
            $location = $this->resolveGujaratLocation(
                $query,
                'locations'
            );

            if (!$location) {
                return response()->json([
                    'query' => $query,
                    'locations' => [],
                    'error' => "No Gujarat location found for '{$query}'. Please check the spelling."
                ], 404);
            }

            return response()->json([
                'query' => $query,
                'locations' => [$location],
            ]);
        } catch (\Throwable $e) {
            Log::error(
                'Location search exception',
                [
                    'query' => $query,
                    'message' => $e->getMessage(),
                ]
            );

            return response()->json([
                'error' => 'Location search is temporarily unavailable.'
            ], 502);
        }
    }


    /**
     * Dynamic UV Index.
     */
    private function getUvIndex(
        float $lat,
        float $lon
    ): array {

        try {

            $response = Http::timeout(8)->get(
                'https://api.open-meteo.com/v1/forecast',
                [
                    'latitude' =>
                        $lat,

                    'longitude' =>
                        $lon,

                    'hourly' =>
                        'uv_index',

                    'forecast_days' =>
                        2,

                    'timezone' =>
                        'auto',
                ]
            );

            if (!$response->successful()) {

                return [
                    'available' =>
                        false,

                    'error' =>
                        'UV data is currently unavailable.',
                ];
            }

            $data =
                $response->json();

            $times =
                $data['hourly']['time'] ??
                [];

            $uvValues =
                $data['hourly']['uv_index'] ??
                [];

            $timezone =
                $data['timezone'] ??
                'UTC';

            if (
                empty($times) ||
                empty($uvValues)
            ) {

                return [
                    'available' =>
                        false,

                    'error' =>
                        'No UV data available for this location.',
                ];
            }

            $now =
                new \DateTimeImmutable(
                    'now',
                    new \DateTimeZone(
                        $timezone
                    )
                );

            $currentDate =
                $now->format('Y-m-d');

            $currentIndex =
                0;

            $smallestDifference =
                PHP_INT_MAX;

            $peakIndex =
                null;

            $peakValue =
                -1;

            foreach (
                $times as $index => $time
            ) {

                if (
                    !isset(
                        $uvValues[$index]
                    ) ||
                    $uvValues[$index] === null
                ) {
                    continue;
                }

                $value =
                    (float) $uvValues[$index];

                try {

                    $dateTime =
                        new \DateTimeImmutable(
                            $time,
                            new \DateTimeZone(
                                $timezone
                            )
                        );

                } catch (\Exception $e) {

                    continue;
                }

                $difference =
                    abs(
                        $dateTime->getTimestamp() -
                        $now->getTimestamp()
                    );

                if (
                    $difference <
                    $smallestDifference
                ) {

                    $smallestDifference =
                        $difference;

                    $currentIndex =
                        $index;
                }

                if (
                    $dateTime->format('Y-m-d') ===
                    $currentDate &&
                    $value > $peakValue
                ) {

                    $peakValue =
                        $value;

                    $peakIndex =
                        $index;
                }
            }

            $currentUv =
                isset(
                    $uvValues[$currentIndex]
                )
                    ? (float)
                        $uvValues[$currentIndex]
                    : null;

            if ($currentUv === null) {

                return [
                    'available' =>
                        false,

                    'error' =>
                        'Current UV data is unavailable.',
                ];
            }

            $currentUv =
                round(
                    $currentUv,
                    1
                );

            $category =
                $this->getUvCategory(
                    $currentUv
                );

            $peakTime =
                null;

            if (
                $peakIndex !== null &&
                isset(
                    $times[$peakIndex]
                )
            ) {

                try {

                    $peakDateTime =
                        new \DateTimeImmutable(
                            $times[$peakIndex],
                            new \DateTimeZone(
                                $timezone
                            )
                        );

                    $peakTime =
                        $peakDateTime->format(
                            'g:i A'
                        );

                } catch (\Exception $e) {

                    $peakTime =
                        null;
                }
            }

            return [
                'available' =>
                    true,

                'value' =>
                    $currentUv,

                'category' =>
                    $category['name'],

                'level' =>
                    $category['level'],

                'color' =>
                    $category['color'],

                'recommendation' =>
                    $category['recommendation'],

                'peak_value' =>
                    $peakValue >= 0
                        ? round(
                            $peakValue,
                            1
                        )
                        : null,

                'peak_time' =>
                    $peakTime,

                'timezone' =>
                    $timezone,

                'updated_at' =>
                    $now->format(
                        'Y-m-d H:i:s'
                    ),
            ];

        } catch (\Throwable $e) {

            Log::warning(
                'UV API error: ' .
                $e->getMessage()
            );

            return [
                'available' =>
                    false,

                'error' =>
                    'Unable to load UV data right now.',
            ];
        }
    }


    /**
     * Standard UV categories.
     */
    private function getUvCategory(
        float $uv
    ): array {

        if ($uv <= 2) {

            return [
                'name' =>
                    'Low',

                'level' =>
                    'low',

                'color' =>
                    '#22c55e',

                'recommendation' =>
                    'Low UV risk. Normal outdoor activity is generally fine.'
            ];
        }

        if ($uv <= 5) {

            return [
                'name' =>
                    'Moderate',

                'level' =>
                    'moderate',

                'color' =>
                    '#eab308',

                'recommendation' =>
                    'Protection is recommended, especially around midday.'
            ];
        }

        if ($uv <= 7) {

            return [
                'name' =>
                    'High',

                'level' =>
                    'high',

                'color' =>
                    '#f97316',

                'recommendation' =>
                    'Use sunscreen, protective clothing and seek shade around midday.'
            ];
        }

        if ($uv <= 10) {

            return [
                'name' =>
                    'Very High',

                'level' =>
                    'very-high',

                'color' =>
                    '#ef4444',

                'recommendation' =>
                    'Extra protection is essential. Avoid prolonged midday sun.'
            ];
        }

        return [
            'name' =>
                'Extreme',

            'level' =>
                'extreme',

            'color' =>
                '#a855f7',

            'recommendation' =>
                'Extreme UV risk. Avoid midday sun and use full sun protection.'
        ];
    }


    /**
     * Existing Historical Weather feature.
     */
    public function getHistorical(
        Request $request
    ) {

        $validated =
            $request->validate([
                'city' => [
                    'required',
                    'string',
                    'max:100'
                ],

                'from' => [
                    'required',
                    'date_format:Y-m-d'
                ],

                'to' => [
                    'required',
                    'date_format:Y-m-d',
                    'after_or_equal:from'
                ],
            ]);

        try {

            $city =
                trim(
                    $validated['city']
                );

            $from =
                Carbon::createFromFormat(
                    'Y-m-d',
                    $validated['from']
                )->startOfDay();

            $to =
                Carbon::createFromFormat(
                    'Y-m-d',
                    $validated['to']
                )->startOfDay();

            $latestCompletedDate =
                Carbon::yesterday()
                    ->startOfDay();

            $earliestSupportedDate =
                Carbon::create(
                    1940,
                    1,
                    1
                )->startOfDay();

            if (
                $from->lt(
                    $earliestSupportedDate
                )
            ) {

                return response()->json([
                    'error' =>
                        'Historical data is available from 01-01-1940 onward.',
                ], 422);
            }

            if (
                $to->gt(
                    $latestCompletedDate
                )
            ) {

                return response()->json([
                    'error' =>
                        'Please select a To Date up to yesterday for historical analysis.',
                ], 422);
            }

            $location =
                $this->resolveGujaratLocation(
                    $city,
                    'historical'
                );

            if (
                !$location ||
                !isset(
                    $location['latitude'],
                    $location['longitude']
                )
            ) {

                return response()->json([
                    'error' =>
                        "Could not find a Gujarat location for '{$city}'. Please select a valid Gujarat district.",
                ], 404);
            }

            $cacheKey =
                sprintf(
                    'historical_weather:%s:%s:%s',
                    strtolower($city),
                    $from->toDateString(),
                    $to->toDateString()
                );

            $historical =
                Cache::remember(
                    $cacheKey,
                    now()->addHour(),
                    function () use (
                        $location,
                        $from,
                        $to
                    ) {

                        $response =
                            Http::timeout(20)->get(
                                'https://archive-api.open-meteo.com/v1/archive',
                                [
                                    'latitude' =>
                                        $location['latitude'],

                                    'longitude' =>
                                        $location['longitude'],

                                    'start_date' =>
                                        $from->toDateString(),

                                    'end_date' =>
                                        $to->toDateString(),

                                    'daily' =>
                                        implode(
                                            ',',
                                            [
                                                'temperature_2m_mean',
                                                'temperature_2m_max',
                                                'temperature_2m_min',
                                                'precipitation_sum',
                                                'relative_humidity_2m_mean',
                                                'wind_speed_10m_mean',
                                            ]
                                        ),

                                    'temperature_unit' =>
                                        'celsius',

                                    'wind_speed_unit' =>
                                        'kmh',

                                    'precipitation_unit' =>
                                        'mm',

                                    'timezone' =>
                                        'Asia/Kolkata',
                                ]
                            );

                        if (
                            !$response->successful()
                        ) {

                            Log::error(
                                'Open-Meteo historical request failed',
                                [
                                    'status' =>
                                        $response->status(),

                                    'body' =>
                                        $response->body(),
                                ]
                            );

                            throw new \RuntimeException(
                                'Historical weather provider request failed.'
                            );
                        }

                        return $response->json();
                    }
                );

            $daily =
                $historical['daily'] ??
                null;

            if (
                !$daily ||
                empty(
                    $daily['time']
                )
            ) {

                return response()->json([
                    'error' =>
                        'No historical weather data was returned for the selected date range.',
                ], 404);
            }

            $means =
                $this->cleanNumericSeries(
                    $daily['temperature_2m_mean'] ??
                    []
                );

            $maxima =
                $this->cleanNumericSeries(
                    $daily['temperature_2m_max'] ??
                    []
                );

            $minima =
                $this->cleanNumericSeries(
                    $daily['temperature_2m_min'] ??
                    []
                );

            $rain =
                $this->cleanNumericSeries(
                    $daily['precipitation_sum'] ??
                    []
                );

            $humidity =
                $this->cleanNumericSeries(
                    $daily['relative_humidity_2m_mean'] ??
                    []
                );

            $wind =
                $this->cleanNumericSeries(
                    $daily['wind_speed_10m_mean'] ??
                    []
                );

            if (
                empty($means) ||
                empty($maxima) ||
                empty($minima)
            ) {

                return response()->json([
                    'error' =>
                        'Historical temperature data is unavailable for the selected date range.',
                ], 404);
            }

            $dayCount =
                count(
                    $daily['time']
                );

            $dailyRecords =
                $this->buildDailyRecords(
                    $daily,
                    $dayCount
                );

            return response()->json([
                'location' =>
                    $location['name'] ??
                    $city,

                'latitude' =>
                    $location['latitude'],

                'longitude' =>
                    $location['longitude'],

                'from' =>
                    $from->toDateString(),

                'to' =>
                    $to->toDateString(),

                'days' =>
                    $dayCount,

                'avg_temp' =>
                    round(
                        array_sum($means) /
                        max(
                            count($means),
                            1
                        ),
                        1
                    ),

                'max_temp' =>
                    round(
                        max($maxima),
                        1
                    ),

                'min_temp' =>
                    round(
                        min($minima),
                        1
                    ),

                'total_rainfall' =>
                    round(
                        array_sum($rain),
                        1
                    ),

                'avg_humidity' =>
                    round(
                        array_sum($humidity) /
                        max(
                            count($humidity),
                            1
                        ),
                        1
                    ),

                'avg_wind' =>
                    round(
                        array_sum($wind) /
                        max(
                            count($wind),
                            1
                        ),
                        1
                    ),

                'daily' =>
                    $dailyRecords,

                'source' =>
                    'Open-Meteo Historical Weather API',
            ]);

        } catch (\Throwable $e) {

            Log::error(
                'Historical weather exception',
                [
                    'message' =>
                        $e->getMessage(),

                    'city' =>
                        $request->input(
                            'city'
                        ),

                    'from' =>
                        $request->input(
                            'from'
                        ),

                    'to' =>
                        $request->input(
                            'to'
                        ),
                ]
            );

            return response()->json([
                'error' =>
                    'Unable to fetch historical weather data right now. Please try again.',
            ], 502);
        }
    }


    /**
     * ==========================================
     * WEATHER ANALYTICS
     * ==========================================
     *
     * Provides real daily historical weather data
     * for 7, 30, 90 and 365 day periods.
     *
     * Supports:
     * - Current selected period
     * - Previous equal-length period
     * - Daily temperature
     * - Rainfall
     * - Humidity
     * - Wind
     */
    public function getWeatherAnalytics(
        Request $request
    ) {

        $validated =
            $request->validate([
                'city' => [
                    'required',
                    'string',
                    'max:100'
                ],

                'days' => [
                    'required',
                    'integer',
                    'in:7,30,90,365'
                ],

                'previous' => [
                    'nullable',
                    'boolean'
                ],
            ]);

        try {

            $city =
                trim(
                    $validated['city']
                );

            $days =
                (int) $validated['days'];

            $isPrevious =
                filter_var(
                    $request->input(
                        'previous',
                        false
                    ),
                    FILTER_VALIDATE_BOOLEAN
                );

            $latestCompletedDate =
                Carbon::yesterday()
                    ->startOfDay();

            /*
             * Current period:
             *
             * yesterday -> backwards
             *
             * Previous period:
             *
             * immediately before current period
             */
            if ($isPrevious) {

                $currentPeriodFrom =
                    $latestCompletedDate
                        ->copy()
                        ->subDays(
                            $days - 1
                        );

                $to =
                    $currentPeriodFrom
                        ->copy()
                        ->subDay();

                $from =
                    $to
                        ->copy()
                        ->subDays(
                            $days - 1
                        );

            } else {

                $to =
                    $latestCompletedDate
                        ->copy();

                $from =
                    $to
                        ->copy()
                        ->subDays(
                            $days - 1
                        );
            }

            /*
             * Resolve city/district to actual Gujarat
             * coordinates.
             */
            $location =
                $this->resolveGujaratLocation(
                    $city,
                    'analytics'
                );

            if (
                !$location ||
                !isset(
                    $location['latitude'],
                    $location['longitude']
                )
            ) {

                return response()->json([
                    'error' =>
                        "Could not find a Gujarat location for '{$city}'. Please select a valid Gujarat district.",
                ], 404);
            }

            /*
             * Open-Meteo Archive.
             */
            $cacheKey =
                sprintf(
                    'weather_analytics:%s:%s:%s:%s',
                    strtolower(
                        $city
                    ),
                    $from->toDateString(),
                    $to->toDateString(),
                    $isPrevious
                        ? 'previous'
                        : 'current'
                );

            $historical =
                Cache::remember(
                    $cacheKey,
                    now()->addHour(),
                    function () use (
                        $location,
                        $from,
                        $to
                    ) {

                        $response =
                            Http::timeout(25)->get(
                                'https://archive-api.open-meteo.com/v1/archive',
                                [
                                    'latitude' =>
                                        $location['latitude'],

                                    'longitude' =>
                                        $location['longitude'],

                                    'start_date' =>
                                        $from->toDateString(),

                                    'end_date' =>
                                        $to->toDateString(),

                                    'daily' =>
                                        implode(
                                            ',',
                                            [
                                                'temperature_2m_mean',
                                                'temperature_2m_max',
                                                'temperature_2m_min',
                                                'precipitation_sum',
                                                'relative_humidity_2m_mean',
                                                'wind_speed_10m_mean',
                                            ]
                                        ),

                                    'temperature_unit' =>
                                        'celsius',

                                    'wind_speed_unit' =>
                                        'kmh',

                                    'precipitation_unit' =>
                                        'mm',

                                    'timezone' =>
                                        'Asia/Kolkata',
                                ]
                            );

                        if (
                            !$response->successful()
                        ) {

                            Log::error(
                                'Open-Meteo Analytics request failed',
                                [
                                    'status' =>
                                        $response->status(),

                                    'body' =>
                                        $response->body(),

                                    'from' =>
                                        $from->toDateString(),

                                    'to' =>
                                        $to->toDateString(),
                                ]
                            );

                            throw new \RuntimeException(
                                'Analytics weather provider request failed.'
                            );
                        }

                        return $response->json();
                    }
                );

            $daily =
                $historical['daily'] ??
                null;

            if (
                !$daily ||
                empty(
                    $daily['time']
                )
            ) {

                return response()->json([
                    'error' =>
                        'No historical analytics data is available for the selected period.',
                ], 404);
            }

            $dailyRecords =
                $this->buildDailyRecords(
                    $daily,
                    count(
                        $daily['time']
                    )
                );

            /*
             * Only retain days that actually contain
             * usable weather data.
             */
            $dailyRecords =
                array_values(
                    array_filter(
                        $dailyRecords,
                        function ($record) {

                            return
                                $record['avg_temp'] !== null ||
                                $record['max_temp'] !== null ||
                                $record['min_temp'] !== null ||
                                $record['rainfall'] !== null ||
                                $record['humidity'] !== null ||
                                $record['wind'] !== null;
                        }
                    )
                );

            if (
                empty($dailyRecords)
            ) {

                return response()->json([
                    'error' =>
                        'Historical analytics data is unavailable for this location and period.',
                ], 404);
            }

            /*
             * Build summary statistics.
             */
            $averageTemperatures =
                array_values(
                    array_filter(
                        array_column(
                            $dailyRecords,
                            'avg_temp'
                        ),
                        static function ($value) {
                            return is_numeric(
                                $value
                            );
                        }
                    )
                );

            $maximumTemperatures =
                array_values(
                    array_filter(
                        array_column(
                            $dailyRecords,
                            'max_temp'
                        ),
                        static function ($value) {
                            return is_numeric(
                                $value
                            );
                        }
                    )
                );

            $minimumTemperatures =
                array_values(
                    array_filter(
                        array_column(
                            $dailyRecords,
                            'min_temp'
                        ),
                        static function ($value) {
                            return is_numeric(
                                $value
                            );
                        }
                    )
                );

            $rainfallValues =
                array_values(
                    array_filter(
                        array_column(
                            $dailyRecords,
                            'rainfall'
                        ),
                        static function ($value) {
                            return is_numeric(
                                $value
                            );
                        }
                    )
                );

            $humidityValues =
                array_values(
                    array_filter(
                        array_column(
                            $dailyRecords,
                            'humidity'
                        ),
                        static function ($value) {
                            return is_numeric(
                                $value
                            );
                        }
                    )
                );

            $windValues =
                array_values(
                    array_filter(
                        array_column(
                            $dailyRecords,
                            'wind'
                        ),
                        static function ($value) {
                            return is_numeric(
                                $value
                            );
                        }
                    )
                );

            return response()->json([
                'location' =>
                    $location['name'] ??
                    $city,

                'latitude' =>
                    $location['latitude'],

                'longitude' =>
                    $location['longitude'],

                'timezone' =>
                    'Asia/Kolkata',

                'from' =>
                    $from->toDateString(),

                'to' =>
                    $to->toDateString(),

                'days_requested' =>
                    $days,

                'days_available' =>
                    count($dailyRecords),

                'previous_period' =>
                    $isPrevious,

                'avg_temp' =>
                    !empty(
                        $averageTemperatures
                    )
                        ? round(
                            array_sum(
                                $averageTemperatures
                            ) /
                            count(
                                $averageTemperatures
                            ),
                            1
                        )
                        : null,

                'max_temp' =>
                    !empty(
                        $maximumTemperatures
                    )
                        ? round(
                            max(
                                $maximumTemperatures
                            ),
                            1
                        )
                        : null,

                'min_temp' =>
                    !empty(
                        $minimumTemperatures
                    )
                        ? round(
                            min(
                                $minimumTemperatures
                            ),
                            1
                        )
                        : null,

                'total_rainfall' =>
                    round(
                        array_sum(
                            $rainfallValues
                        ),
                        1
                    ),

                'avg_humidity' =>
                    !empty(
                        $humidityValues
                    )
                        ? round(
                            array_sum(
                                $humidityValues
                            ) /
                            count(
                                $humidityValues
                            ),
                            1
                        )
                        : null,

                'avg_wind' =>
                    !empty(
                        $windValues
                    )
                        ? round(
                            array_sum(
                                $windValues
                            ) /
                            count(
                                $windValues
                            ),
                            1
                        )
                        : null,

                /*
                 * Frontend charts consume this as `list`.
                 */
                'list' =>
                    $dailyRecords,

                /*
                 * Same data is also exposed as `data`
                 * for compatibility.
                 */
                'data' =>
                    $dailyRecords,

                'source' =>
                    'Open-Meteo Historical Weather API',
            ]);

        } catch (\Throwable $e) {

            Log::error(
                'Weather Analytics exception',
                [
                    'message' =>
                        $e->getMessage(),

                    'city' =>
                        $request->input(
                            'city'
                        ),

                    'days' =>
                        $request->input(
                            'days'
                        ),

                    'previous' =>
                        $request->input(
                            'previous'
                        ),
                ]
            );

            return response()->json([
                'error' =>
                    'Unable to fetch weather analytics data right now. Please try again.',
            ], 502);
        }
    }


    /**
     * Resolve a Gujarat location using
     * Open-Meteo Geocoding.
     */
    private function resolveGujaratLocation(
        string $city,
        string $prefix = 'location'
    ): ?array {
        $normalizedInput = $this->normalizeGujaratLocationInput($city);

        if ($normalizedInput === '') {
            return null;
        }

        $cacheKey =
            'gujarat_location:' .
            $this->locationSearchKey($normalizedInput);

        return Cache::remember(
            $cacheKey,
            now()->addDay(),
            function () use ($normalizedInput, $city) {
                try {
                    $candidateName =
                        $this->canonicalGujaratLocationName($normalizedInput)
                        ?? $normalizedInput;

                    $tryGeocode = function (string $searchName): ?array {
                        $response = Http::timeout(8)->get(
                            'https://geocoding-api.open-meteo.com/v1/search',
                            [
                                'name' => $searchName . ', Gujarat',
                                'count' => 10,
                                'language' => 'en',
                                'format' => 'json',
                                'countryCode' => 'IN',
                            ]
                        );

                        if (!$response->successful()) {
                            Log::error(
                                'Open-Meteo geocoding request failed',
                                [
                                    'status' => $response->status(),
                                    'search_name' => $searchName,
                                ]
                            );

                            throw new \RuntimeException('LOCATION_PROVIDER_ERROR');
                        }

                        foreach ($response->json('results', []) as $result) {
                            $countryCode = strtoupper(
                                (string) ($result['country_code'] ?? '')
                            );

                            $admin1 = strtolower(
                                trim((string) ($result['admin1'] ?? ''))
                            );

                            $lat = $result['latitude'] ?? null;
                            $lon = $result['longitude'] ?? null;

                            if (
                                $countryCode === 'IN' &&
                                $admin1 === 'gujarat' &&
                                is_numeric($lat) &&
                                is_numeric($lon)
                            ) {
                                return [
                                    'id' => $result['id'] ?? null,
                                    'name' => $this->canonicalResolvedLocationName(
                                        (string) ($result['name'] ?? $searchName)
                                    ),
                                    'latitude' => (float) $lat,
                                    'longitude' => (float) $lon,
                                    'timezone' => $result['timezone'] ?? 'Asia/Kolkata',
                                    'state' => $result['admin1'] ?? 'Gujarat',
                                    'country' => $result['country'] ?? 'India',
                                    'country_code' => $countryCode,
                                ];
                            }
                        }

                        return null;
                    };

                    $location = $tryGeocode($candidateName);

                    if (
                        !$location &&
                        strcasecmp($candidateName, $normalizedInput) !== 0
                    ) {
                        $location = $tryGeocode($normalizedInput);
                    }

                    if (!$location) {
                        $fuzzyName = $this->findFuzzyGujaratLocationName(
                            $normalizedInput
                        );

                        if (
                            $fuzzyName &&
                            strcasecmp($fuzzyName, $candidateName) !== 0
                        ) {
                            $location = $tryGeocode($fuzzyName);
                        }
                    }

                    return $location;

                } catch (\Throwable $e) {
                    Log::warning(
                        'Gujarat geocoding failed',
                        [
                            'city' => $city,
                            'message' => $e->getMessage(),
                        ]
                    );

                    if ($e->getMessage() === 'LOCATION_PROVIDER_ERROR') {
                        throw $e;
                    }

                    return null;
                }
            }
        );
    }

    private function normalizeGujaratLocationInput(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        $value = preg_replace('/[^\pL\pN,\s-]+/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        $value = trim($value, " \t\n\r\0\x0B,");

        for ($i = 0; $i < 3; $i++) {
            $cleaned = preg_replace(
                '/(?:,|\s)+(?:gujarat|gujrat|india|in)\s*$/iu',
                '',
                $value
            );

            if (!is_string($cleaned) || $cleaned === $value) {
                break;
            }

            $value = trim($cleaned);
        }

        return $value;
    }

    private function locationSearchKey(string $value): string
    {
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/i', ' ', $value) ?? $value;
        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }

    private function canonicalGujaratLocationName(string $value): ?string
    {
        $key = $this->locationSearchKey($value);

        $aliases = [
            'mahesana' => 'Mehsana',
            'mehsana' => 'Mehsana',
            'mehsna' => 'Mehsana',
            'maheshana' => 'Mehsana',
            'ahmedbad' => 'Ahmedabad',
            'ahmadabad' => 'Ahmedabad',
            'ahmedabad city' => 'Ahmedabad',
            'baroda' => 'Vadodara',
            'vadodora' => 'Vadodara',
            'vadodra' => 'Vadodara',
            'gandhinager' => 'Gandhinagar',
            'gandhinagar' => 'Gandhinagar',
            'bhavnagr' => 'Bhavnagar',
            'jamnagr' => 'Jamnagar',
            'rajkoot' => 'Rajkot',
            'rajkote' => 'Rajkot',
            'surath' => 'Surat',
            'bharuchh' => 'Bharuch',
            'palanpur city' => 'Palanpur',
            'himatnagar city' => 'Himatnagar',
            'himatnager' => 'Himatnagar',
            'modasa city' => 'Modasa',
            'dahad' => 'Dahod',
            'dahhod' => 'Dahod',
            'dahodh' => 'Dahod',
            'banas kantha' => 'Banaskantha',
            'banaskantha' => 'Banaskantha',
            'chotta udepur' => 'Chhota Udepur',
            'chhotaudepur' => 'Chhota Udepur',
            'devbhoomi dwarka' => 'Devbhumi Dwarka',
            'devbhumi dwarka' => 'Devbhumi Dwarka',
            'gir somnath' => 'Gir Somnath',
            'panch mahal' => 'Panchmahal',
            'panchmahal' => 'Panchmahal',
            'kachchh' => 'Kutch',
            'kachh' => 'Kutch',
            'kutch' => 'Kutch',
            'buj' => 'Bhuj',
            'bhuj' => 'Bhuj',
        ];

        return $aliases[$key] ?? null;
    }

    private function canonicalResolvedLocationName(string $value): string
    {
        $canonical = $this->canonicalGujaratLocationName($value);
        return $canonical ?? trim($value);
    }

    private function findFuzzyGujaratLocationName(string $value): ?string
    {
        $key = $this->locationSearchKey($value);

        if (strlen($key) < 4) {
            return null;
        }

        $candidates = [
            'Ahmedabad', 'Amreli', 'Anand', 'Aravalli', 'Banaskantha',
            'Bharuch', 'Bhavnagar', 'Botad', 'Chhota Udepur', 'Dahod',
            'Dang', 'Devbhumi Dwarka', 'Gandhinagar', 'Gir Somnath',
            'Jamnagar', 'Junagadh', 'Kheda', 'Kutch', 'Mahisagar',
            'Mehsana', 'Morbi', 'Narmada', 'Navsari', 'Panchmahal',
            'Patan', 'Porbandar', 'Rajkot', 'Sabarkantha', 'Surat',
            'Surendranagar', 'Tapi', 'Vadodara', 'Valsad', 'Bhuj',
            'Dholka', 'Godhra', 'Himatnagar', 'Modasa', 'Nadiad',
            'Palanpur', 'Vapi', 'Veraval', 'Gandhidham', 'Ankleshwar',
            'Bardoli', 'Botad', 'Kalol', 'Sanand', 'Sidhpur', 'Unjha',
            'Mundra', 'Mandvi', 'Dwarka', 'Somnath', 'Porbandar',
        ];

        $best = null;
        $bestScore = 0.0;
        $inputPhonetic = metaphone($key);

        foreach ($candidates as $candidate) {
            $candidateKey = $this->locationSearchKey($candidate);
            $distance = levenshtein($key, $candidateKey);
            $maxLength = max(strlen($key), strlen($candidateKey));
            $similarity = 0.0;

            similar_text($key, $candidateKey, $similarity);

            $phoneticMatch =
                $inputPhonetic !== '' &&
                $inputPhonetic === metaphone($candidateKey);

            $distanceScore =
                $maxLength > 0
                    ? 1 - ($distance / $maxLength)
                    : 0;

            $score = max(
                $distanceScore,
                $similarity / 100,
                $phoneticMatch ? 0.92 : 0
            );

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $candidate;
            }
        }

        return $bestScore >= 0.78 ? $best : null;
    }


    /**
     * Convert Open-Meteo daily arrays into
     * chart-friendly records.
     */
    private function buildDailyRecords(
        array $daily,
        int $dayCount
    ): array {

        $records = [];

        for (
            $i = 0;
            $i < $dayCount;
            $i++
        ) {

            $records[] = [
                'date' =>
                    $daily['time'][$i]
                    ?? null,

                'avg_temp' =>
                    isset(
                        $daily[
                            'temperature_2m_mean'
                        ][$i]
                    ) &&
                    is_numeric(
                        $daily[
                            'temperature_2m_mean'
                        ][$i]
                    )
                        ? round(
                            (float)
                            $daily[
                                'temperature_2m_mean'
                            ][$i],
                            1
                        )
                        : null,

                'max_temp' =>
                    isset(
                        $daily[
                            'temperature_2m_max'
                        ][$i]
                    ) &&
                    is_numeric(
                        $daily[
                            'temperature_2m_max'
                        ][$i]
                    )
                        ? round(
                            (float)
                            $daily[
                                'temperature_2m_max'
                            ][$i],
                            1
                        )
                        : null,

                'min_temp' =>
                    isset(
                        $daily[
                            'temperature_2m_min'
                        ][$i]
                    ) &&
                    is_numeric(
                        $daily[
                            'temperature_2m_min'
                        ][$i]
                    )
                        ? round(
                            (float)
                            $daily[
                                'temperature_2m_min'
                            ][$i],
                            1
                        )
                        : null,

                'rainfall' =>
                    isset(
                        $daily[
                            'precipitation_sum'
                        ][$i]
                    ) &&
                    is_numeric(
                        $daily[
                            'precipitation_sum'
                        ][$i]
                    )
                        ? round(
                            (float)
                            $daily[
                                'precipitation_sum'
                            ][$i],
                            1
                        )
                        : null,

                'humidity' =>
                    isset(
                        $daily[
                            'relative_humidity_2m_mean'
                        ][$i]
                    ) &&
                    is_numeric(
                        $daily[
                            'relative_humidity_2m_mean'
                        ][$i]
                    )
                        ? round(
                            (float)
                            $daily[
                                'relative_humidity_2m_mean'
                            ][$i],
                            1
                        )
                        : null,

                'wind' =>
                    isset(
                        $daily[
                            'wind_speed_10m_mean'
                        ][$i]
                    ) &&
                    is_numeric(
                        $daily[
                            'wind_speed_10m_mean'
                        ][$i]
                    )
                        ? round(
                            (float)
                            $daily[
                                'wind_speed_10m_mean'
                            ][$i],
                            1
                        )
                        : null,
            ];
        }

        return $records;
    }


    /**
     * Keep numeric values only.
     */
    private function cleanNumericSeries(
        array $values
    ): array {

        return array_values(
            array_filter(
                $values,
                static function ($value) {

                    return is_numeric(
                        $value
                    );
                }
            )
        );
    }


    /**
     * Air Quality.
     */
    public function getAirQuality(
        Request $request
    ) {

        $validated =
            $request->validate([
                'lat' => [
                    'required',
                    'numeric',
                    'between:-90,90'
                ],

                'lon' => [
                    'required',
                    'numeric',
                    'between:-180,180'
                ],
            ]);

        $apiKey =
            env(
                'OPENWEATHER_API_KEY'
            );

        if (!$apiKey) {

            return response()->json([
                'error' =>
                    'OpenWeather API key is not configured.'
            ], 503);
        }

        $lat =
            (float)
            $validated['lat'];

        $lon =
            (float)
            $validated['lon'];

        try {

            $cacheKey =
                sprintf(
                    'air_quality:%s:%s',
                    round($lat, 4),
                    round($lon, 4)
                );

            $airQuality =
                Cache::remember(
                    $cacheKey,
                    now()->addMinutes(5),
                    function () use (
                        $lat,
                        $lon,
                        $apiKey
                    ) {

                        $response =
                            Http::timeout(10)->get(
                                'https://api.openweathermap.org/data/2.5/air_pollution',
                                [
                                    'lat' =>
                                        $lat,

                                    'lon' =>
                                        $lon,

                                    'appid' =>
                                        $apiKey,
                                ]
                            );

                        if (
                            !$response->successful()
                        ) {

                            Log::error(
                                'OpenWeather Air Pollution API failed',
                                [
                                    'status' =>
                                        $response->status(),

                                    'body' =>
                                        $response->body(),

                                    'lat' =>
                                        $lat,

                                    'lon' =>
                                        $lon,
                                ]
                            );

                            throw new \RuntimeException(
                                'Air quality provider request failed.'
                            );
                        }

                        return $response->json();
                    }
                );

            $item =
                $airQuality['list'][0]
                ?? null;

            $components =
                $item['components']
                ?? null;

            if (
                !$item ||
                !isset(
                    $item['main']['aqi']
                ) ||
                !$components
            ) {

                return response()->json([
                    'error' =>
                        'Air quality data is currently unavailable for this location.'
                ], 404);
            }

            $aqi =
                (int)
                $item['main']['aqi'];

            $categories = [

                1 => [
                    'name' =>
                        'Good',

                    'color' =>
                        '#22c55e',

                    'health' =>
                        'Air quality is good. Enjoy normal outdoor activities.',
                ],

                2 => [
                    'name' =>
                        'Fair',

                    'color' =>
                        '#eab308',

                    'health' =>
                        'Air quality is acceptable. Sensitive people should monitor symptoms.',
                ],

                3 => [
                    'name' =>
                        'Moderate',

                    'color' =>
                        '#f59e0b',

                    'health' =>
                        'Consider reducing prolonged outdoor exertion if you are sensitive to air pollution.',
                ],

                4 => [
                    'name' =>
                        'Poor',

                    'color' =>
                        '#f97316',

                    'health' =>
                        'Reduce prolonged outdoor activity, especially for sensitive groups.',
                ],

                5 => [
                    'name' =>
                        'Very Poor',

                    'color' =>
                        '#ef4444',

                    'health' =>
                        'Limit outdoor exposure and consider using a mask in polluted conditions.',
                ],
            ];

            $category =
                $categories[$aqi]
                ?? $categories[5];

            return response()->json([
                'aqi' =>
                    $aqi,

                'category' =>
                    $category['name'],

                'color' =>
                    $category['color'],

                'health_recommendation' =>
                    $category['health'],

                'scale' =>
                    'OpenWeather AQI 1-5',

                'updated_at' =>
                    isset(
                        $item['dt']
                    )
                        ? date(
                            DATE_ATOM,
                            (int)
                            $item['dt']
                        )
                        : now()->toIso8601String(),

                'components' => [

                    'pm2_5' =>
                        isset(
                            $components['pm2_5']
                        )
                            ? round(
                                (float)
                                $components['pm2_5'],
                                1
                            )
                            : null,

                    'pm10' =>
                        isset(
                            $components['pm10']
                        )
                            ? round(
                                (float)
                                $components['pm10'],
                                1
                            )
                            : null,

                    'co' =>
                        isset(
                            $components['co']
                        )
                            ? round(
                                (float)
                                $components['co'],
                                1
                            )
                            : null,

                    'no2' =>
                        isset(
                            $components['no2']
                        )
                            ? round(
                                (float)
                                $components['no2'],
                                1
                            )
                            : null,

                    'so2' =>
                        isset(
                            $components['so2']
                        )
                            ? round(
                                (float)
                                $components['so2'],
                                1
                            )
                            : null,
                ],

                'units' =>
                    'µg/m³',
            ]);

        } catch (\Throwable $e) {

            Log::error(
                'Air quality exception',
                [
                    'message' =>
                        $e->getMessage(),

                    'lat' =>
                        $lat,

                    'lon' =>
                        $lon,
                ]
            );

            return response()->json([
                'error' =>
                    'Unable to load air quality data right now.'
            ], 502);
        }
    }


    /**
     * AI Weather Assistant.
     *
     * Uses the location mentioned in the user's prompt when available.
     * Otherwise it uses the currently selected dashboard location.
     */
    public function askMeteorologist(Request $request)
    {
        $validated = $request->validate([
            'prompt' => [
                'required',
                'string',
                'max:2000',
            ],

            'context' => [
                'nullable',
                'array',
            ],

            'history' => [
                'nullable',
                'array',
                'max:20',
            ],

            'location' => [
                'nullable',
                'string',
                'max:100',
            ],
        ]);

        $prompt = trim($validated['prompt']);
        $context = $validated['context'] ?? [];
        $history = $validated['history'] ?? [];

        $selectedLocation = trim((string) (
            $validated['location'] ?? data_get($context, 'current.name', '')
        ));

        /*
         * IMPORTANT:
         * If the user explicitly mentions another Gujarat location,
         * fetch weather for THAT location instead of blindly using the
         * currently selected dashboard city.
         */
        $requestedLocation =
            $this->detectGujaratLocationFromPrompt($prompt);

        if ($requestedLocation) {
            $freshContext =
                $this->fetchMeteorologistWeatherContext($requestedLocation);

            if ($freshContext !== null) {
                $context = $freshContext;
                $selectedLocation = $requestedLocation;
            } else {
                return response()->json([
                    'reply' =>
                        "I couldn't load current weather data for {$requestedLocation}. Please try again in a moment.",
                    'location' => $requestedLocation,
                ], 200);
            }
        }

        $currentWeather = data_get($context, 'current');
        $forecast = data_get($context, 'forecast');
        $uv = data_get($context, 'uv');

        $weatherAvailable =
            is_array($currentWeather) &&
            isset($currentWeather['main'], $currentWeather['weather']) &&
            !empty($currentWeather['weather']);

        if (!$weatherAvailable) {
            return response()->json([
                'reply' =>
                    'Current weather data is unavailable for the selected location. Please load a valid Gujarat location and try again.',
                'location' => $selectedLocation ?: null,
            ], 200);
        }

        $aiApiKey = env('GROQ_API_KEY');

        if (!$aiApiKey) {
            Log::error('Groq API key is not configured.');

            return response()->json([
                'error' =>
                    'AI Weather Assistant is unavailable because the AI API key is not configured.',
            ], 503);
        }

        $weatherContext = [
            'selected_location' =>
                $selectedLocation ?: ($currentWeather['name'] ?? 'Unknown'),

            'current_weather' => [
                'city' => $currentWeather['name'] ?? null,
                'temperature_c' => data_get($currentWeather, 'main.temp'),
                'feels_like_c' => data_get($currentWeather, 'main.feels_like'),
                'humidity_percent' => data_get($currentWeather, 'main.humidity'),
                'pressure_hpa' => data_get($currentWeather, 'main.pressure'),
                'wind_speed_kmh' => data_get($currentWeather, 'wind.speed'),
                'wind_direction_deg' => data_get($currentWeather, 'wind.deg'),
                'visibility_m' => data_get($currentWeather, 'visibility'),
                'condition' => data_get($currentWeather, 'weather.0.description'),
            ],

            'forecast' => $this->prepareMeteorologistForecast($forecast),

            'uv' => [
                'available' => data_get($uv, 'available', false),
                'value' => data_get($uv, 'value'),
                'category' => data_get($uv, 'category'),
                'peak_value' => data_get($uv, 'peak_value'),
                'peak_time' => data_get($uv, 'peak_time'),
            ],
        ];

        $conversationHistory = [];

        foreach (array_slice($history, -12) as $message) {
            if (!is_array($message)) {
                continue;
            }

            $role = $message['role'] ?? null;
            $content = trim((string) ($message['content'] ?? ''));

            if (
                !in_array($role, ['user', 'assistant'], true) ||
                $content === ''
            ) {
                continue;
            }

            $conversationHistory[] = [
                'role' => $role,
                'content' => $content,
            ];
        }

        $systemPrompt =
            'You are the AI Weather Assistant for the Gujarat Weather Intelligence Platform.\n\n' .
            'Answer the user dynamically using only the supplied real weather context.\n' .
            'Always prioritize the location explicitly mentioned in the current user prompt.\n' .
            'If no location is mentioned, use the selected location in the context.\n' .
            'For rain questions, inspect forecast rain probability and forecast condition.\n' .
            'For travel questions, consider rain, wind, visibility, temperature and severe weather when available.\n' .
            'For outdoor timing questions, consider temperature, rain and UV when available.\n' .
            'For farmer questions, provide cautious weather-based guidance only from the supplied data.\n' .
            'Never invent weather values, locations, forecast periods or conditions.\n' .
            'If the requested information is not available, clearly say that the data is unavailable.\n' .
            'Keep answers concise and practical.\n' .
            'Reply in the user\'s language style when practical: English, Gujarati or Hinglish.\n\n' .
            'REAL WEATHER CONTEXT:\n' .
            json_encode(
                $weatherContext,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );

        $messages = [
            [
                'role' => 'system',
                'content' => $systemPrompt,
            ],
        ];

        foreach ($conversationHistory as $message) {
            $messages[] = $message;
        }

        $messages[] = [
            'role' => 'user',
            'content' => $prompt,
        ];

        try {
            $aiResponse = Http::timeout(20)
                ->withToken($aiApiKey)
                ->post(
                    'https://api.groq.com/openai/v1/chat/completions',
                    [
                        'model' => 'openai/gpt-oss-120b',
                        'messages' => $messages,
                        'temperature' => 0.3,
                        'max_tokens' => 500,
                    ]
                );

            if (!$aiResponse->successful()) {
                Log::error(
                    'Groq AI Weather request failed',
                    [
                        'status' => $aiResponse->status(),
                        'body' => $aiResponse->body(),
                    ]
                );

                return response()->json([
                    'error' =>
                        'AI Weather Assistant is temporarily unavailable. Please try again.',
                ], 502);
            }

            $responseData = $aiResponse->json();
            $reply = data_get($responseData, 'choices.0.message.content');

            if (!is_string($reply) || trim($reply) === '') {
                return response()->json([
                    'error' =>
                        'The AI Weather Assistant returned no usable answer.',
                ], 502);
            }

            return response()->json([
                'reply' => trim($reply),
                'location' =>
                    $selectedLocation ?: ($currentWeather['name'] ?? null),
            ]);
        } catch (\Throwable $e) {
            Log::error(
                'Meteorologist AI exception',
                [
                    'message' => $e->getMessage(),
                ]
            );

            return response()->json([
                'error' =>
                    'Unable to connect to the AI Weather Assistant right now.',
            ], 502);
        }
    }


    /**
     * Detect a Gujarat location mentioned in the user's current prompt.
     */
    private function detectGujaratLocationFromPrompt(string $prompt): ?string
    {
        $locations = $this->getGujaratLocationCandidates();

        usort(
            $locations,
            static fn ($a, $b) => strlen($b) <=> strlen($a)
        );

        foreach ($locations as $location) {
            if (
                preg_match(
                    '/(?<![A-Za-z])' .
                    preg_quote($location, '/') .
                    '(?![A-Za-z])/iu',
                    $prompt
                )
            ) {
                $resolved = $this->resolveGujaratLocation(
                    $location,
                    'meteorologist'
                );

                if ($resolved && isset($resolved['name'])) {
                    return $resolved['name'];
                }
            }
        }

        if (preg_match(
            '/(?:\bin\b|\bfor\b|\bat\b|\babout\b)\s+([A-Za-z][A-Za-z\s-]{1,45}?)(?=\s+(?:weather|forecast|today|tomorrow|now|temperature|rain|rainfall|wind|humidity|outside|travel)\b|[?.!,]|$)/iu',
            $prompt,
            $matches
        )) {
            $candidate = trim($matches[1]);

            if ($candidate !== '') {
                $location = $this->resolveGujaratLocation(
                    $candidate,
                    'meteorologist'
                );

                if ($location && isset($location['name'])) {
                    return $location['name'];
                }
            }
        }

        return null;
    }

    private function getGujaratLocationCandidates(): array
    {
        return [
            'Devbhumi Dwarka', 'Gir Somnath', 'Chhota Udepur',
            'Bhavnagar', 'Gandhinagar', 'Ahmedabad', 'Amreli', 'Anand',
            'Aravalli', 'Banaskantha', 'Bharuch', 'Botad', 'Dahod', 'Dang',
            'Jamnagar', 'Junagadh', 'Kheda', 'Kutch', 'Mahisagar', 'Mahesana',
            'Mehsana', 'Morbi', 'Narmada', 'Navsari', 'Panchmahal', 'Patan',
            'Porbandar', 'Rajkot', 'Sabarkantha', 'Surat', 'Surendranagar',
            'Tapi', 'Vadodara', 'Valsad', 'Bhuj', 'Dholka', 'Godhra',
            'Himatnagar', 'Modasa', 'Nadiad', 'Palanpur', 'Vapi', 'Veraval',
            'Gandhidham', 'Ankleshwar', 'Bardoli', 'Kalol', 'Sanand', 'Sidhpur',
            'Unjha', 'Mundra', 'Mandvi', 'Dwarka', 'Somnath'
        ];
    }


    /**
     * Fetch fresh OpenWeather data for the location mentioned by the user.
     */
    private function fetchMeteorologistWeatherContext(string $city): ?array
    {
        $apiKey = env('OPENWEATHER_API_KEY');

        if (!$apiKey) {
            Log::error('OpenWeather API key is not configured.');
            return null;
        }

        try {
            $location = $this->resolveGujaratLocation(
                $city,
                'meteorologist'
            );

            if (
                !$location ||
                !is_numeric($location['latitude'] ?? null) ||
                !is_numeric($location['longitude'] ?? null)
            ) {
                return null;
            }

            $lat = (float) $location['latitude'];
            $lon = (float) $location['longitude'];

            $currentResponse = Http::timeout(10)->get(
                'https://api.openweathermap.org/data/2.5/weather',
                [
                    'lat' => $lat,
                    'lon' => $lon,
                    'units' => 'metric',
                    'appid' => $apiKey,
                ]
            );

            if (!$currentResponse->successful()) {
                Log::warning(
                    'AI current weather provider request failed',
                    [
                        'status' => $currentResponse->status(),
                        'city' => $city,
                    ]
                );
                return null;
            }

            $current = $currentResponse->json();
            $current['name'] = $location['name'] ?? ($current['name'] ?? $city);

            $forecastResponse = Http::timeout(10)->get(
                'https://api.openweathermap.org/data/2.5/forecast',
                [
                    'lat' => $lat,
                    'lon' => $lon,
                    'units' => 'metric',
                    'appid' => $apiKey,
                ]
            );

            return [
                'current' => $current,
                'forecast' => $forecastResponse->successful()
                    ? $forecastResponse->json()
                    : null,
                'uv' => $this->getUvIndex($lat, $lon),
            ];
        } catch (\Throwable $e) {
            Log::warning(
                'AI Weather location fetch failed',
                [
                    'city' => $city,
                    'message' => $e->getMessage(),
                ]
            );

            return null;
        }
    }


    /**
     * Prepare only useful forecast information for the AI.
     */
    private function prepareMeteorologistForecast($forecast): array
    {
        if (
            is_array($forecast) &&
            isset($forecast['list']) &&
            is_array($forecast['list'])
        ) {
            $forecast = $forecast['list'];
        }

        if (!is_array($forecast)) {
            return [];
        }

        $prepared = [];

        foreach (array_slice($forecast, 0, 16) as $item) {
            if (!is_array($item)) {
                continue;
            }

            $prepared[] = [
                'time' => $item['dt_txt'] ?? null,
                'temperature_c' => data_get($item, 'main.temp'),
                'feels_like_c' => data_get($item, 'main.feels_like'),
                'humidity_percent' => data_get($item, 'main.humidity'),
                'rain_probability' => isset($item['pop'])
                    ? round((float) $item['pop'] * 100)
                    : null,
                'rainfall_3h_mm' => data_get($item, 'rain.3h'),
                'wind_speed_kmh' => data_get($item, 'wind.speed'),
                'wind_direction_deg' => data_get($item, 'wind.deg'),
                'visibility_m' => $item['visibility'] ?? null,
                'condition' => data_get($item, 'weather.0.description'),
            ];
        }

        return $prepared;
    }
}
