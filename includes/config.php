<?php
/**
 * Configuration file for API keys and settings
 * 
 * This file contains sensitive information like API keys.
 * Make sure this file is not accessible via web (place outside web root if possible)
 */

// OpenWeatherMap API Key
// Get your free API key from: https://openweathermap.org/api
define('OPENWEATHER_API_KEY', 'your_api_key_here');
define('OPENWEATHER_API_KEY_CONFIGURED', OPENWEATHER_API_KEY !== 'your_api_key_here');

// API Base URLs
define('OPENWEATHER_BASE_URL', 'https://api.openweathermap.org/data/2.5/');
define('WEATHER_ENDPOINT', 'weather');
define('FORECAST_ENDPOINT', 'forecast');

// Units for temperature (metric for Celsius)
define('UNITS', 'metric');
?>