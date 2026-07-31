<?php

function fetchJson(string $url): ?array
{
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'user_agent' => 'GujaratWeatherDashboard/1.0'
        ]
    ]);

    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        return null;
    }

    $data = json_decode($response, true);
    return json_last_error() === JSON_ERROR_NONE ? $data : null;
}

function findCity(string $city): ?array
{
    $local = findLocalCity($city);
    if ($local !== null) {
        return $local;
    }

    $url = 'https://geocoding-api.open-meteo.com/v1/search?' . http_build_query([
        'name' => $city,
        'count' => 1,
        'language' => 'en',
        'format' => 'json'
    ]);
    $data = fetchJson($url);
    return $data['results'][0] ?? null;
}

function findLocalCity(string $city): ?array
{
    $cities = [
        'ahmedabad' => ['name' => 'Ahmedabad', 'latitude' => 23.0225, 'longitude' => 72.5714],
        'surat' => ['name' => 'Surat', 'latitude' => 21.1702, 'longitude' => 72.8311],
        'vadodara' => ['name' => 'Vadodara', 'latitude' => 22.3072, 'longitude' => 73.1812],
        'rajkot' => ['name' => 'Rajkot', 'latitude' => 22.3039, 'longitude' => 70.8022],
        'bhavnagar' => ['name' => 'Bhavnagar', 'latitude' => 21.7645, 'longitude' => 72.1519],
        'jamnagar' => ['name' => 'Jamnagar', 'latitude' => 22.4707, 'longitude' => 70.0577],
        'junagadh' => ['name' => 'Junagadh', 'latitude' => 21.5222, 'longitude' => 70.4579],
        'gandhinagar' => ['name' => 'Gandhinagar', 'latitude' => 23.2156, 'longitude' => 72.6369],
        'anand' => ['name' => 'Anand', 'latitude' => 22.5645, 'longitude' => 72.9289],
        'nadiad' => ['name' => 'Nadiad', 'latitude' => 22.6916, 'longitude' => 72.8634],
        'morbi' => ['name' => 'Morbi', 'latitude' => 22.8173, 'longitude' => 70.8377],
        'surendranagar' => ['name' => 'Surendranagar', 'latitude' => 22.7201, 'longitude' => 71.6495],
        'bharuch' => ['name' => 'Bharuch', 'latitude' => 21.7051, 'longitude' => 72.9959],
        'navsari' => ['name' => 'Navsari', 'latitude' => 20.9467, 'longitude' => 72.9520],
        'veraval' => ['name' => 'Veraval', 'latitude' => 20.9159, 'longitude' => 70.3629],
        'porbandar' => ['name' => 'Porbandar', 'latitude' => 21.6417, 'longitude' => 69.6293],
        'godhra' => ['name' => 'Godhra', 'latitude' => 22.7788, 'longitude' => 73.6143],
        'dahod' => ['name' => 'Dahod', 'latitude' => 22.8379, 'longitude' => 74.2531],
        'botad' => ['name' => 'Botad', 'latitude' => 22.1704, 'longitude' => 71.6684],
        'amreli' => ['name' => 'Amreli', 'latitude' => 21.6015, 'longitude' => 71.2204],
        'deesa' => ['name' => 'Deesa', 'latitude' => 24.2585, 'longitude' => 72.1907],
        'bhuj' => ['name' => 'Bhuj', 'latitude' => 23.2419, 'longitude' => 69.6669],
        'palanpur' => ['name' => 'Palanpur', 'latitude' => 24.1724, 'longitude' => 72.4383],
        'himmatnagar' => ['name' => 'Himmatnagar', 'latitude' => 23.5969, 'longitude' => 72.9630],
        'mehsana' => ['name' => 'Mehsana', 'latitude' => 23.5880, 'longitude' => 72.3693],
        'patan' => ['name' => 'Patan', 'latitude' => 23.8493, 'longitude' => 72.1266],
        'kheda' => ['name' => 'Kheda', 'latitude' => 22.7522, 'longitude' => 72.6853],
        'valsad' => ['name' => 'Valsad', 'latitude' => 20.5992, 'longitude' => 72.9342],
        'vapi' => ['name' => 'Vapi', 'latitude' => 20.3893, 'longitude' => 72.9106],
        'daman' => ['name' => 'Daman', 'latitude' => 20.3974, 'longitude' => 72.8328],
        'diu' => ['name' => 'Diu', 'latitude' => 20.7144, 'longitude' => 70.9874],
    ];

    $key = strtolower(trim($city));
    if (!isset($cities[$key])) {
        return null;
    }

    return $cities[$key] + ['country_code' => 'IN'];
}

function weatherDescription(int $code): string
{
    return match (true) {
        $code === 0 => 'Clear sky',
        in_array($code, [1, 2, 3], true) => 'Partly cloudy',
        in_array($code, [45, 48], true) => 'Fog',
        in_array($code, [51, 53, 55, 56, 57], true) => 'Drizzle',
        in_array($code, [61, 63, 65, 66, 67], true) => 'Rain',
        in_array($code, [71, 73, 75, 77], true) => 'Snow',
        in_array($code, [80, 81, 82], true) => 'Rain showers',
        in_array($code, [85, 86], true) => 'Snow showers',
        in_array($code, [95, 96, 99], true) => 'Thunderstorm',
        default => 'Unknown'
    };
}

function weatherIcon(int $code): string
{
    return match (true) {
        $code === 0 => '01d',
        in_array($code, [1, 2], true) => '02d',
        $code === 3 => '04d',
        in_array($code, [45, 48], true) => '50d',
        in_array($code, [51, 53, 55, 56, 57, 61, 63, 65, 66, 67, 80, 81, 82], true) => '10d',
        in_array($code, [71, 73, 75, 77, 85, 86], true) => '13d',
        default => '11d'
    };
}

function fallbackCurrentWeather(array $location): array
{
    $code = fallbackWeatherCode();
    $temperature = fallbackTemperature($location);

    return [
        'location' => [
            'name' => $location['name'],
            'country' => $location['country_code'] ?? '',
            'coordinates' => [
                'lat' => $location['latitude'],
                'lon' => $location['longitude']
            ]
        ],
        'weather' => [
            'main' => weatherDescription($code),
            'description' => weatherDescription($code),
            'icon' => weatherIcon($code)
        ],
        'temperature' => [
            'current' => $temperature,
            'feels_like' => null,
            'min' => null,
            'max' => null
        ],
        'humidity' => fallbackHumidity(),
        'wind' => ['speed' => 12 / 3.6],
        'timestamp' => time(),
        'timezone' => 'Asia/Kolkata',
        'source' => 'demo'
    ];
}

function fallbackForecast(array $location): array
{
    $forecast = [];
    $baseTemperature = fallbackTemperature($location);

    for ($day = 0; $day < 5; $day++) {
        $time = strtotime("+{$day} days");
        $code = $day % 2 === 0 ? fallbackWeatherCode() : 2;

        $forecast[] = [
            'day' => date('l', $time),
            'date' => date('Y-m-d', $time),
            'temperature' => [
                'min' => $baseTemperature - 4 + ($day % 2),
                'max' => $baseTemperature + 2 + ($day % 3)
            ],
            'weather' => [
                'main' => weatherDescription($code),
                'description' => weatherDescription($code),
                'icon' => weatherIcon($code)
            ]
        ];
    }

    return [
        'city' => $location['name'],
        'country' => $location['country_code'] ?? '',
        'forecast' => $forecast,
        'source' => 'demo'
    ];
}

function fallbackWeatherCode(): int
{
    $month = (int) date('n');
    return in_array($month, [6, 7, 8, 9], true) ? 80 : 2;
}

function fallbackTemperature(array $location): int
{
    $coastalCities = ['Surat', 'Navsari', 'Valsad', 'Vapi', 'Daman', 'Diu', 'Porbandar', 'Veraval'];
    return in_array($location['name'], $coastalCities, true) ? 29 : 31;
}

function fallbackHumidity(): int
{
    $month = (int) date('n');
    return in_array($month, [6, 7, 8, 9], true) ? 78 : 48;
}