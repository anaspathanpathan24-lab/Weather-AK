<?php
// Suppress PHP warnings/notices from being emitted in responses
@ini_set('display_errors', '0');
@error_reporting(0);

require_once '../includes/weather_helpers.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use GET request.']);
    exit;
}

$city = isset($_GET['city']) ? trim($_GET['city']) : '';
$lat = isset($_GET['lat']) ? trim($_GET['lat']) : '';
$lon = isset($_GET['lon']) ? trim($_GET['lon']) : '';
$location = null;

if ($city !== '') {
    if (!preg_match('/^[a-zA-Z\s\-\']+$/', $city) || strlen($city) > 100) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid city name']);
        exit;
    }
    $location = findCity($city);
} elseif (is_numeric($lat) && is_numeric($lon) && $lat >= -90 && $lat <= 90 && $lon >= -180 && $lon <= 180) {
    $location = [
        'name' => 'Your Location',
        'country_code' => '',
        'latitude' => (float) $lat,
        'longitude' => (float) $lon
    ];
}

if ($location === null) {
    http_response_code(404);
    echo json_encode(['error' => 'City not found or weather service is unavailable.']);
    exit;
}

$weatherUrl = 'https://api.open-meteo.com/v1/forecast?' . http_build_query([
    'latitude' => $location['latitude'],
    'longitude' => $location['longitude'],
    'current' => 'temperature_2m,relative_humidity_2m,weather_code,wind_speed_10m',
    'timezone' => 'auto'
]);
$weather = fetchJson($weatherUrl);

if ($weather === null || !isset($weather['current'])) {
    echo json_encode(fallbackCurrentWeather($location));
    exit;
}

$code = (int) $weather['current']['weather_code'];
echo json_encode([
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
        'current' => round($weather['current']['temperature_2m']),
        'feels_like' => null,
        'min' => null,
        'max' => null
    ],
    'humidity' => $weather['current']['relative_humidity_2m'],
    'wind' => ['speed' => $weather['current']['wind_speed_10m'] / 3.6],
    'timestamp' => strtotime($weather['current']['time']),
    'timezone' => $weather['timezone'],
    'source' => 'live'
]);
