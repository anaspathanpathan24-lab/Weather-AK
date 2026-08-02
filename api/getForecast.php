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

$city = isset($_GET['city']) ? trim($_GET['city']) : '';
if ($city === '' || !preg_match('/^[a-zA-Z\s\-\']+$/', $city) || strlen($city) > 100) {
    http_response_code(400);
    echo json_encode(['error' => 'Valid city parameter is required']);
    exit;
}

$location = findCity($city);
if ($location === null) {
    http_response_code(404);
    echo json_encode(['error' => 'City not found or weather service is unavailable.']);
    exit;
}

$forecastUrl = 'https://api.open-meteo.com/v1/forecast?' . http_build_query([
    'latitude' => $location['latitude'],
    'longitude' => $location['longitude'],
    'daily' => 'weather_code,temperature_2m_max,temperature_2m_min',
    'timezone' => 'auto',
    'forecast_days' => 5
]);
$forecast = fetchJson($forecastUrl);

if ($forecast === null || !isset($forecast['daily'])) {
    echo json_encode(fallbackForecast($location));
    exit;
}

$daily = [];
foreach ($forecast['daily']['time'] as $index => $date) {
    $code = (int) $forecast['daily']['weather_code'][$index];
    $daily[] = [
        'day' => date('l', strtotime($date)),
        'date' => $date,
        'temperature' => [
            'min' => round($forecast['daily']['temperature_2m_min'][$index]),
            'max' => round($forecast['daily']['temperature_2m_max'][$index])
        ],
        'weather' => [
            'main' => weatherDescription($code),
            'description' => weatherDescription($code),
            'icon' => weatherIcon($code)
        ]
    ];
}

echo json_encode([
    'city' => $location['name'],
    'country' => $location['country_code'] ?? '',
    'forecast' => $daily,
    'source' => 'live'
]);
