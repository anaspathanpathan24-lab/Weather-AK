const citySearch = document.getElementById('city-search');
const searchBtn = document.getElementById('search-btn');
const themeToggle = document.getElementById('theme-toggle');
const locationBtn = document.getElementById('location-btn');
const loading = document.getElementById('loading');
const weatherContainer = document.getElementById('weather-container');
const errorMessage = document.getElementById('error-message');
const errorText = document.getElementById('error-text');
const retryBtn = document.getElementById('retry-btn');
const cityName = document.getElementById('city-name');
const currentDate = document.getElementById('current-date');
const cityList = document.getElementById('gujarat-cities');
const weatherIcon = document.getElementById('weather-icon');
const temperature = document.getElementById('temperature');
const condition = document.getElementById('condition');
const humidity = document.getElementById('humidity');
const windSpeed = document.getElementById('wind-speed');
const feelsLike = document.getElementById('feels-like');
const airQuality = document.getElementById('air-quality');
const forecastCity = document.getElementById('forecast-city');
const dailySummary = document.getElementById('daily-summary');
const forecastGrid = document.getElementById('forecast-grid');

let isDarkMode = false;
let activeCity = 'Ahmedabad';
const locationRequestInterval = 5 * 60 * 1000;

fetch('api/cities.json')
    .then(response => response.json())
    .then(cities => {
        cityList.replaceChildren(...cities.map(city => {
            const option = document.createElement('option');
            option.value = city;
            return option;
        }));
    })
    .catch(error => console.error('City list error:', error));

themeToggle.addEventListener('click', () => {
    isDarkMode = !isDarkMode;
    document.body.setAttribute('data-theme', isDarkMode ? 'dark' : 'light');
    themeToggle.innerHTML = isDarkMode ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
});

document.body.setAttribute('data-theme', 'light');

function requestUserLocation() {
    if (!navigator.geolocation) {
        fetchWeather(activeCity);
        return;
    }

    navigator.geolocation.getCurrentPosition(
        position => {
            const { latitude, longitude } = position.coords;
            fetchWeatherByCoords(latitude, longitude);
        },
        error => {
            console.log('Geolocation error:', error);
            fetchWeather(activeCity);
        },
        { timeout: 7000, enableHighAccuracy: true, maximumAge: 0 }
    );
}

window.addEventListener('load', () => {
    requestUserLocation();
    setInterval(requestUserLocation, locationRequestInterval);
});

searchBtn.addEventListener('click', runSearch);
locationBtn.addEventListener('click', requestUserLocation);
citySearch.addEventListener('keydown', event => {
    if (event.key === 'Enter') {
        runSearch();
    }
});

retryBtn.addEventListener('click', () => {
    fetchWeather(citySearch.value.trim() || activeCity);
});

function runSearch() {
    const city = citySearch.value.trim();
    if (city) {
        fetchWeather(city);
    }
}

async function fetchWeatherByCoords(lat, lon) {
    showLoading();
    try {
        const response = await fetch(`api/getWeather.php?lat=${lat}&lon=${lon}`);
        const data = await response.json();
        if (!response.ok) throw new Error(data.error || 'Weather data not available');
        displayWeather(data);
        fetchForecast(data.location.name);
    } catch (error) {
        showError(error.message);
        console.error('Weather fetch error:', error);
    }
}

async function fetchWeather(city) {
    showLoading();
    try {
        const response = await fetch(`api/getWeather.php?city=${encodeURIComponent(city)}`);
        const data = await response.json();
        if (!response.ok) throw new Error(data.error || `City "${city}" not found.`);
        activeCity = data.location.name;
        displayWeather(data);
        fetchForecast(activeCity);
    } catch (error) {
        showError(error.message);
        console.error('Weather fetch error:', error);
    }
}

async function fetchForecast(city) {
    try {
        const response = await fetch(`api/getForecast.php?city=${encodeURIComponent(city)}`);
        if (!response.ok) throw new Error('Forecast data not available');
        const data = await response.json();
        displayForecast(data);
    } catch (error) {
        console.error('Forecast fetch error:', error);
    }
}

function displayWeather(data) {
    const temp = Number(data.temperature.current);
    const windKmh = Math.round(data.wind.speed * 3.6);
    const feels = Math.round(temp + getFeelsLikeAdjustment(data.humidity, windKmh));

    setWeatherScene(data.weather, data.timezone);
    cityName.textContent = data.location.name;
    forecastCity.textContent = data.location.country || 'Gujarat';
    currentDate.textContent = new Date().toLocaleDateString('en-IN', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
    weatherIcon.src = `https://openweathermap.org/img/wn/${data.weather.icon}@2x.png`;
    temperature.innerHTML = `${temp}&deg;C`;
    condition.textContent = data.weather.description;
    humidity.textContent = `${data.humidity}%`;
    windSpeed.textContent = `${windKmh} km/h`;
    feelsLike.innerHTML = `${feels}&deg;C`;
    airQuality.textContent = getAirQualityLabel(data.humidity, windKmh);
    const sourceLabel = data.source === 'demo' ? 'Demo reading: ' : '';
    dailySummary.textContent = `${sourceLabel}${data.location.name} is seeing ${data.weather.description.toLowerCase()} with humidity at ${data.humidity}% and winds near ${windKmh} km/h.`;

    hideLoading();
    showWeather();
}

function setWeatherScene(weather, timezone) {
    const weatherMain = (weather.main || weather.description || '').toLowerCase();
    const isNight = weather.icon?.endsWith('n') || isNightInTimezone(timezone);
    let scene = isNight ? 'night' : 'clear';

    if (weatherMain.includes('thunder')) scene = 'thunderstorm';
    else if (weatherMain.includes('snow')) scene = 'snow';
    else if (weatherMain.includes('rain') || weatherMain.includes('drizzle')) scene = 'rain';
    else if (weatherMain.includes('mist') || weatherMain.includes('fog') || weatherMain.includes('haze')) scene = 'mist';
    else if (weatherMain.includes('cloud')) scene = isNight ? 'night-cloudy' : 'cloudy';

    document.body.dataset.weather = scene;
}

function isNightInTimezone(timezone) {
    if (!timezone) return false;
    try {
        const hour = Number(new Intl.DateTimeFormat('en-US', {
            hour: 'numeric',
            hour12: false,
            timeZone: timezone
        }).format(new Date()));
        return hour < 6 || hour >= 18;
    } catch (error) {
        return false;
    }
}

function displayForecast(data) {
    forecastGrid.innerHTML = '';

    data.forecast.forEach((day, index) => {
        const dayName = index === 0 ? 'Today' : day.day.substring(0, 3);
        const forecastCard = document.createElement('article');
        forecastCard.className = 'forecast-card';
        forecastCard.innerHTML = `
            <h4>${dayName}</h4>
            <img src="https://openweathermap.org/img/wn/${day.weather.icon}@2x.png" alt="${day.weather.description}">
            <div class="temp">${day.temperature.max}&deg;C</div>
            <div class="condition">${day.weather.description}</div>
            <small>${day.temperature.min}&deg;C low</small>
        `;
        forecastGrid.appendChild(forecastCard);
    });
}

function getFeelsLikeAdjustment(humidityValue, windKmh) {
    if (humidityValue > 70) return 2;
    if (windKmh > 22) return -2;
    if (windKmh > 14) return -1;
    return 0;
}

function getAirQualityLabel(humidityValue, windKmh) {
    if (humidityValue > 82 && windKmh < 8) return 'Moderate';
    if (windKmh > 28) return 'Fresh';
    return 'Good';
}

function showLoading() {
    loading.classList.remove('hidden');
    weatherContainer.classList.add('hidden');
    errorMessage.classList.add('hidden');
}

function hideLoading() {
    loading.classList.add('hidden');
}

function showWeather() {
    weatherContainer.classList.remove('hidden');
}

function showError(message) {
    errorText.textContent = message;
    errorMessage.classList.remove('hidden');
    loading.classList.add('hidden');
    weatherContainer.classList.add('hidden');
}
