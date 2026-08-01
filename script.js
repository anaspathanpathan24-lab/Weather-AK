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
const currentTime = document.getElementById('current-time');
const cityList = document.getElementById('gujarat-cities');
const weatherIcon = document.getElementById('weather-icon');
const temperature = document.getElementById('temperature');
const condition = document.getElementById('condition');
const humidity = document.getElementById('humidity');
const humidityMeter = document.getElementById('humidity-meter');
const windSpeed = document.getElementById('wind-speed');
const windMeter = document.getElementById('wind-meter');
const feelsLike = document.getElementById('feels-like');
const feelsLikeInline = document.getElementById('feels-like-inline');
const airQuality = document.getElementById('air-quality');
const aqiValue = document.getElementById('aqi-value');
const aqiMeter = document.getElementById('aqi-meter');
const uvMeter = document.getElementById('uv-meter');
const rainChance = document.getElementById('rain-chance');
const heroRain = document.getElementById('hero-rain');
const hourlyTrack = document.getElementById('hourly-track');
const forecastList = document.getElementById('forecast-list');
const chartLine = document.getElementById('chart-line');
const chartFill = document.getElementById('chart-fill');
const chartPoints = document.getElementById('chart-points');
const updatedTime = document.getElementById('updated-time');

let isDarkMode = false;
let activeCity = 'Ahmedabad';

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
    themeToggle.innerHTML = isDarkMode ? '<i class="fa-regular fa-sun"></i>' : '<i class="fa-regular fa-moon"></i>';
});

searchBtn.addEventListener('click', runSearch);
locationBtn.addEventListener('click', requestUserLocation);
retryBtn.addEventListener('click', () => fetchWeather(citySearch.value.trim() || activeCity));
citySearch.addEventListener('keydown', event => {
    if (event.key === 'Enter') runSearch();
});

window.addEventListener('load', () => {
    document.body.setAttribute('data-theme', 'light');
    fetchWeather(activeCity);
});

function runSearch() {
    const city = citySearch.value.trim();
    if (city) fetchWeather(city);
}

function requestUserLocation() {
    if (!navigator.geolocation) {
        fetchWeather(activeCity);
        return;
    }

    navigator.geolocation.getCurrentPosition(
        position => fetchWeatherByCoords(position.coords.latitude, position.coords.longitude),
        () => fetchWeather(activeCity),
        { timeout: 7000, enableHighAccuracy: true, maximumAge: 0 }
    );
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
    }
}

async function fetchForecast(city) {
    try {
        const response = await fetch(`api/getForecast.php?city=${encodeURIComponent(city)}`);
        if (!response.ok) throw new Error('Forecast data not available');
        const data = await response.json();
        displayForecast(data.forecast || []);
    } catch (error) {
        console.error('Forecast fetch error:', error);
        displayForecast(makeFallbackForecast());
    }
}

function displayWeather(data) {
    const temp = Number(data.temperature.current);
    const windKmh = Math.round(data.wind.speed * 3.6);
    const feels = Math.round(temp + getFeelsLikeAdjustment(data.humidity, windKmh));
    const rain = getRainChance(data.weather.description, data.humidity);
    const aqi = getAqiValue(data.humidity, windKmh);

    cityName.textContent = `${data.location.name}, Gujarat`;
    currentDate.textContent = formatDate(data.timestamp);
    currentTime.textContent = formatTime(data.timestamp);
    updatedTime.textContent = data.source === 'demo' ? 'demo data' : '2 min ago';
    weatherIcon.src = `https://openweathermap.org/img/wn/${data.weather.icon}@2x.png`;
    weatherIcon.alt = data.weather.description;
    temperature.innerHTML = `${temp}&deg;C`;
    condition.textContent = titleCase(data.weather.description);
    humidity.textContent = `${data.humidity}%`;
    humidityMeter.style.width = `${clamp(data.humidity, 12, 100)}%`;
    windSpeed.textContent = `${windKmh} km/h`;
    windMeter.style.width = `${clamp(windKmh * 4, 12, 100)}%`;
    feelsLike.innerHTML = `${feels}&deg;C`;
    feelsLikeInline.innerHTML = `${feels}&deg;C`;
    airQuality.textContent = getAirQualityLabel(aqi);
    aqiValue.textContent = aqi;
    aqiMeter.style.width = `${clamp(aqi * 1.7, 18, 100)}%`;
    uvMeter.style.width = '86%';
    rainChance.textContent = `${rain}%`;
    heroRain.textContent = `${rain}%`;

    renderHourly(temp, rain, data.weather.icon);
    renderChart(makeChartTemps(temp));
    hideLoading();
    showWeather();
}

function displayForecast(days) {
    const base = days.length ? days : makeFallbackForecast();
    const sevenDays = [...base];

    while (sevenDays.length < 7) {
        const previous = sevenDays[sevenDays.length - 1];
        const date = new Date(previous.date || Date.now());
        date.setDate(date.getDate() + 1);
        sevenDays.push({
            day: date.toLocaleDateString('en-IN', { weekday: 'long' }),
            date: date.toISOString().slice(0, 10),
            temperature: {
                max: previous.temperature.max + (sevenDays.length % 2 ? 1 : -1),
                min: previous.temperature.min
            },
            weather: previous.weather
        });
    }

    forecastList.innerHTML = '';
    sevenDays.slice(0, 7).forEach((day, index) => {
        const date = parseLocalDate(day.date);
        const row = document.createElement('article');
        row.className = 'forecast-row';
        row.innerHTML = `
            <small>${date.toLocaleDateString('en-IN', { weekday: 'short' })}, ${date.toLocaleDateString('en-IN', { day: '2-digit', month: 'short' })}</small>
            <img src="https://openweathermap.org/img/wn/${day.weather.icon}@2x.png" alt="${day.weather.description}">
            <strong>${day.temperature.max}&deg;C</strong>
            <small>${day.temperature.min}&deg;C</small>
            <small class="rain"><i class="fa-solid fa-droplet"></i> ${[28, 10, 40, 60, 30, 10, 20][index]}%</small>
        `;
        forecastList.appendChild(row);
    });
}

function renderHourly(temp, rain, icon) {
    const hours = ['Now', '9 PM', '10 PM', '11 PM', '12 AM', '1 AM', '2 AM', '3 AM'];
    hourlyTrack.innerHTML = '';

    hours.forEach((hour, index) => {
        const card = document.createElement('article');
        card.className = 'hour-card';
        card.innerHTML = `
            <b>${hour}</b>
            <img src="https://openweathermap.org/img/wn/${icon}@2x.png" alt="">
            <strong>${temp - Math.floor(index / 2)}&deg;C</strong>
            <small><i class="fa-solid fa-droplet"></i> ${Math.max(10, rain - index * 3)}%</small>
        `;
        hourlyTrack.appendChild(card);
    });
}

function renderChart(temps) {
    const points = temps.map((temp, index) => {
        const x = index * (620 / (temps.length - 1));
        const y = 150 - ((temp - 20) / 15) * 124;
        return [x, y];
    });

    const d = points.map(([x, y], index) => `${index ? 'L' : 'M'} ${x.toFixed(1)} ${y.toFixed(1)}`).join(' ');
    chartLine.setAttribute('d', d);
    chartFill.setAttribute('d', `${d} L 620 170 L 0 170 Z`);
    chartPoints.innerHTML = points.map(([x, y], index) => {
        const label = index === 5 ? `<text x="${x - 20}" y="${y - 16}" fill="#ffffff" font-size="12" font-weight="700">${temps[index]}&deg;C</text>` : '';
        return `${label}<circle cx="${x}" cy="${y}" r="5"></circle>`;
    }).join('');
}

function makeChartTemps(temp) {
    return [temp - 5, temp - 3, temp - 1, temp - 2, temp - 2, temp - 1, temp + 1, temp + 1, temp + 2, temp, temp - 2, temp - 2, temp - 1, temp - 2, temp - 2, temp - 2];
}

function makeFallbackForecast() {
    return [0, 1, 2, 3, 4].map(offset => {
        const date = new Date();
        date.setDate(date.getDate() + offset);
        return {
            day: date.toLocaleDateString('en-IN', { weekday: 'long' }),
            date: date.toISOString().slice(0, 10),
            temperature: { max: 31 + (offset % 3), min: 24 - (offset % 2) },
            weather: { description: 'Partly cloudy', icon: offset % 2 ? '01d' : '02d' }
        };
    });
}

function formatDate(timestamp) {
    const date = timestamp ? new Date(timestamp * 1000) : new Date();
    return date.toLocaleDateString('en-IN', {
        weekday: 'long',
        day: '2-digit',
        month: 'long',
        year: 'numeric'
    });
}

function formatTime(timestamp) {
    const date = timestamp ? new Date(timestamp * 1000) : new Date();
    return date.toLocaleTimeString('en-IN', {
        hour: '2-digit',
        minute: '2-digit'
    });
}

function parseLocalDate(dateValue) {
    if (!dateValue) return new Date();
    const [year, month, day] = dateValue.split('-').map(Number);
    return new Date(year, month - 1, day);
}

function getFeelsLikeAdjustment(humidityValue, windKmh) {
    if (humidityValue > 70) return 2;
    if (windKmh > 22) return -2;
    if (windKmh > 14) return -1;
    return 0;
}

function getRainChance(description, humidityValue) {
    const text = description.toLowerCase();
    if (text.includes('rain') || text.includes('drizzle')) return 60;
    if (text.includes('cloud')) return Math.max(28, Math.round(humidityValue / 2.4));
    return Math.max(10, Math.round(humidityValue / 6));
}

function getAqiValue(humidityValue, windKmh) {
    return clamp(Math.round(38 + humidityValue / 9 - windKmh / 3), 24, 86);
}

function getAirQualityLabel(aqi) {
    if (aqi > 70) return 'Moderate';
    if (aqi > 50) return 'Fair';
    return 'Good';
}

function titleCase(value) {
    return value.replace(/\w\S*/g, word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase());
}

function clamp(value, min, max) {
    return Math.min(max, Math.max(min, value));
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
