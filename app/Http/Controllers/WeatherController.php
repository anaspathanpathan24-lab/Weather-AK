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

                $currentResponse = Http::get(
                    "https://api.openweathermap.org/data/2.5/weather?lat={$lat}&lon={$lon}&units=metric&appid={$apiKey}"
                );
            } else {
                $city = $request->city ?? 'Mahesana';

                $currentResponse = Http::get(
                    "https://api.openweathermap.org/data/2.5/weather?q=" .
                    urlencode($city) .
                    "&units=metric&appid={$apiKey}"
                );
            }

            if (!$currentResponse->successful()) {
                return response()->json([
                    'error' => 'Location not found.'
                ], 404);
            }

            $currentData = $currentResponse->json();

            $lat =
                $currentData['coord']['lat'] ??
                23.5880;

            $lon =
                $currentData['coord']['lon'] ??
                72.3693;

            $forecastResponse = Http::get(
                "https://api.openweathermap.org/data/2.5/forecast?lat={$lat}&lon={$lon}&units=metric&appid={$apiKey}"
            );

            return response()->json([
                'current' => $currentData,

                'forecast' =>
                    $forecastResponse->successful()
                        ? $forecastResponse->json()
                        : null,

                'uv' =>
                    $this->getUvIndex(
                        $lat,
                        $lon
                    ),
            ]);

        } catch (\Exception $e) {

            Log::error(
                'Weather API exception: ' .
                $e->getMessage()
            );

            return response()->json([
                'error' =>
                    'Server error occurred.'
            ], 500);
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

        $cacheKey =
            $prefix .
            '_location:' .
            strtolower(
                $city
            );

        return Cache::remember(
            $cacheKey,
            now()->addDay(),
            function () use ($city) {

                try {

                    $response =
                        Http::timeout(10)->get(
                            'https://geocoding-api.open-meteo.com/v1/search',
                            [
                                'name' =>
                                    $city .
                                    ', Gujarat',

                                'count' =>
                                    10,

                                'language' =>
                                    'en',

                                'format' =>
                                    'json',

                                'countryCode' =>
                                    'IN',
                            ]
                        );

                    if (
                        !$response->successful()
                    ) {
                        return null;
                    }

                    $results =
                        $response->json(
                            'results',
                            []
                        );

                    foreach (
                        $results as $result
                    ) {

                        $countryCode =
                            strtoupper(
                                (string) (
                                    $result['country_code']
                                    ?? ''
                                )
                            );

                        $admin1 =
                            strtolower(
                                trim(
                                    (string) (
                                        $result['admin1']
                                        ?? ''
                                    )
                                )
                            );

                        if (
                            $countryCode === 'IN' &&
                            $admin1 === 'gujarat'
                        ) {

                            return [
                                'name' =>
                                    $result['name']
                                    ?? $city,

                                'latitude' =>
                                    $result['latitude']
                                    ?? null,

                                'longitude' =>
                                    $result['longitude']
                                    ?? null,

                                'timezone' =>
                                    $result['timezone']
                                    ?? 'Asia/Kolkata',
                            ];
                        }
                    }

                    return null;

                } catch (\Throwable $e) {

                    Log::warning(
                        'Gujarat geocoding failed',
                        [
                            'city' =>
                                $city,

                            'message' =>
                                $e->getMessage(),
                        ]
                    );

                    return null;
                }
            }
        );
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
     * AI Meteorologist.
     */
    public function askMeteorologist(
        Request $request
    ) {

        $prompt =
            $request->prompt ??
            '';

        $aiApiKey =
            env('GROQ_API_KEY');

        $reply =
            "Namaste! Based on current Gujarat climate data, conditions are pleasant around 28°C–32°C. Let me know if you need specific district details!";

        if ($aiApiKey) {

            try {

                $aiResponse =
                    Http::timeout(10)
                        ->withToken(
                            $aiApiKey
                        )
                        ->post(
                            "https://api.groq.com/openai/v1/chat/completions",
                            [
                                'model' =>
                                    'openai/gpt-oss-120b',

                                'messages' => [

                                    [
                                        'role' =>
                                            'system',

                                        'content' =>
                                            'You are a professional Gujarat Weather Assistant. Answer concisely, supporting English, Gujarati and Hinglish.',
                                    ],

                                    [
                                        'role' =>
                                            'user',

                                        'content' =>
                                            $prompt,
                                    ],
                                ],
                            ]
                        );

                if (
                    $aiResponse->successful()
                ) {

                    $responseData =
                        $aiResponse->json();

                    $groqText =
                        $responseData[
                            'choices'
                        ][0][
                            'message'
                        ][
                            'content'
                        ] ??
                        null;

                    if ($groqText) {

                        $reply =
                            $groqText;
                    }

                } else {

                    Log::error(
                        'Groq API call failed',
                        [
                            'status' =>
                                $aiResponse->status(),

                            'body' =>
                                $aiResponse->body(),
                        ]
                    );
                }

            } catch (\Exception $e) {

                Log::error(
                    'Groq API exception: ' .
                    $e->getMessage()
                );
            }
        }

        return response()->json([
            'reply' =>
                $reply
        ]);
    }
}