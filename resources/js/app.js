const gujaratDistricts = [
    "Ahmedabad", "Amreli", "Anand", "Aravalli", "Banaskantha", "Bharuch", "Bhavnagar", 
    "Botad", "Chhota Udepur", "Dahod", "Dang", "Devbhumi Dwarka", "Gandhinagar", 
    "Gir Somnath", "Jamnagar", "Junagadh", "Kheda", "Kutch", "Mahisagar", "Mahesana", 
    "Morbi", "Narmada", "Navsari", "Panchmahal", "Patan", "Porbandar", "Rajkot", 
    "Sabarkantha", "Surat", "Surendranagar", "Tapi", "Vadodara", "Valsad"
];

let currentActiveCity = "";
let liveDashboardCache = {};

document.addEventListener('DOMContentLoaded', () => {
    renderDistrictsGrid(gujaratDistricts);
    renderFavorites();
    populateHistoricalDropdowns();
    initInteractiveMap();
    fetchWeatherData('/api/weather?city=Mahesana');
});

function toggleMobileSidebar(forceClose) {
    const sidebar = document.getElementById('mobile-sidebar');
    const overlay = document.getElementById('mobile-sidebar-overlay');
    const isOpen = sidebar.classList.contains('translate-x-0');

    if (forceClose || isOpen) {
        sidebar.classList.remove('translate-x-0');
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
    } else {
        sidebar.classList.remove('-translate-x-full');
        sidebar.classList.add('translate-x-0');
        overlay.classList.remove('hidden');
    }
}

function switchTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.nav-link').forEach(el => {
        el.classList.remove('bg-blue-500', 'text-white', 'shadow-lg', 'shadow-blue-500/30');
    });

    document.getElementById(`tab-${tabName}`).classList.remove('hidden');
    if(tabName !== 'map' && tabName !== 'historical' && tabName !== 'ai') {
        document.getElementById('link-dashboard').classList.add('bg-blue-500', 'text-white', 'shadow-lg', 'shadow-blue-500/30');
    } else {
        document.getElementById(`link-${tabName}`).classList.add('bg-blue-500', 'text-white', 'shadow-lg', 'shadow-blue-500/30');
    }

    if(tabName === 'historical') {
        fetchHistoricalData();
    }
}

document.getElementById('sidebar-alerts-btn').addEventListener('click', (e) => {
    e.preventDefault();
    switchTab('dashboard');
    toggleMobileSidebar(true);
    setTimeout(() => {
        const alertsSec = document.getElementById('alerts-section');
        if(alertsSec) alertsSec.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, 100);
});

async function fetchWeatherData(queryUrl) {
    showAlert(''); 
    try {
        const response = await fetch(queryUrl);
        const contentType = response.headers.get("content-type");
        if (contentType && contentType.indexOf("application/json") !== -1) {
            const data = await response.json();
            if (!response.ok) { showAlert(data.error || "Location not found!"); return; }
            updateDashboardUI(data);
        } else {
            showAlert("Server error. Please check your backend.");
        }
    } catch (error) {
        console.error(error);
        showAlert("Network error. Please try again.");
    }
}

function updateDashboardUI(data) {
    const current = data.current ? data.current : data;
    currentActiveCity = current.name;
    liveDashboardCache[current.name] = data;

    document.getElementById('city-name').innerText = current.name;
    document.getElementById('weather-desc').innerText = current.weather[0].description;
    document.getElementById('temp').innerText = Math.round(current.main.temp) + '°';
    document.getElementById('humidity').innerText = current.main.humidity + '%';
    document.getElementById('wind').innerText = current.wind.speed + ' km/h';
    document.getElementById('pressure').innerText = (current.main.pressure ?? '--') + ' hPa';

    if (current.weather[0].icon) {
        document.getElementById('weather-icon').src = `https://openweathermap.org/img/wn/${current.weather[0].icon}@4x.png`;
        document.getElementById('weather-icon').alt = current.weather[0].description;
    }

    const feelsLikeContainer = document.getElementById('feels-like-container');
    if (current.main.feels_like) {
        document.getElementById('feels-like-temp').innerText = Math.round(current.main.feels_like);
        feelsLikeContainer.classList.remove('hidden');
    } else {
        feelsLikeContainer.classList.add('hidden');
    }

    document.getElementById('favorite-btn').classList.remove('hidden');
    updateFavoriteStarUI();

    if (!data.forecast) return;
    document.getElementById('extended-features').classList.replace('hidden', 'flex');

    const forecastList = data.forecast.list;
    
    const hourlyContainer = document.getElementById('hourly-container');
    const rainContainer = document.getElementById('rain-container');
    hourlyContainer.innerHTML = ''; rainContainer.innerHTML = '';

    forecastList.slice(0, 6).forEach(item => {
        const date = new Date(item.dt * 1000);
        const timeStr = date.toLocaleTimeString([], { hour: 'numeric', hour12: true });
        const iconUrl = `https://openweathermap.org/img/wn/${item.weather[0].icon}.png`;
        const temp = Math.round(item.main.temp);
        const pop = Math.round((item.pop || 0) * 100);

        hourlyContainer.innerHTML += `
            <div class="flex flex-col items-center min-w-[60px] snap-center">
                <span class="text-xs text-gray-400 mb-2">${timeStr}</span>
                <img src="${iconUrl}" alt="icon" class="w-8 h-8">
                <span class="font-semibold text-white mt-2">${temp}°C</span>
            </div>`;

        rainContainer.innerHTML += `
            <div class="flex flex-col items-center min-w-[60px] snap-center">
                <span class="text-xs text-gray-400 mb-2">${timeStr}</span>
                <i class="fa-solid fa-cloud-rain text-blue-400 my-2"></i>
                <span class="font-semibold text-white">${pop}%</span>
            </div>`;
    });

    const dailyContainer = document.getElementById('daily-container');
    dailyContainer.innerHTML = '';
    const dailyData = {};
    
    forecastList.forEach(item => {
        const dateStr = item.dt_txt.split(' ')[0];
        if (!dailyData[dateStr]) {
            dailyData[dateStr] = { 
                min: item.main.temp_min, 
                max: item.main.temp_max, 
                icon: item.weather[0].icon, 
                dt: item.dt,
                desc: item.weather[0].description,
                humidity: item.main.humidity,
                wind: item.wind.speed
            };
        } else {
            if (item.main.temp_min < dailyData[dateStr].min) dailyData[dateStr].min = item.main.temp_min;
            if (item.main.temp_max > dailyData[dateStr].max) dailyData[dateStr].max = item.main.temp_max;
            if (item.dt_txt.includes("12:00:00")) {
                dailyData[dateStr].icon = item.weather[0].icon;
                dailyData[dateStr].desc = item.weather[0].description;
                dailyData[dateStr].humidity = item.main.humidity;
                dailyData[dateStr].wind = item.wind.speed;
            }
        }
    });

    const dayNames = ['SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT'];
    Object.keys(dailyData).slice(0, 7).forEach(date => {
        const dayObj = dailyData[date];
        const dayName = dayNames[new Date(dayObj.dt * 1000).getDay()];
        const dateFormatted = new Date(dayObj.dt * 1000).toLocaleDateString('en-GB', { day: '2-digit', month: 'short' });
        
        const safeData = JSON.stringify({
            date: `${dayName}, ${dateFormatted}`,
            desc: dayObj.desc,
            icon: dayObj.icon,
            max: Math.round(dayObj.max),
            min: Math.round(dayObj.min),
            humidity: dayObj.humidity,
            wind: dayObj.wind
        }).replace(/"/g, '&quot;');

        dailyContainer.innerHTML += `
            <div onclick="showForecastDetails('${safeData}')" class="flex items-center justify-between p-3 rounded-2xl hover:bg-[#262a40] cursor-pointer transition border border-transparent hover:border-[#32364a] mb-1">
                <div class="flex flex-col w-20">
                    <span class="text-sm font-bold text-gray-200">${dayName}</span>
                    <span class="text-xs text-gray-500">${dateFormatted}</span>
                </div>
                <div class="flex items-center gap-3 flex-1">
                    <img src="https://openweathermap.org/img/wn/${dayObj.icon}.png" class="w-8 h-8">
                    <span class="text-xs text-blue-100 capitalize hidden sm:block truncate">${dayObj.desc}</span>
                </div>
                <div class="flex gap-3 justify-end w-20">
                    <span class="text-sm font-bold text-white">${Math.round(dayObj.max)}°</span>
                    <span class="text-sm font-medium text-gray-500">${Math.round(dayObj.min)}°</span>
                </div>
            </div>`;
    });

    const uvContainer = document.getElementById('uv-container');
    let uviVal = 0;
    if (data.uv && data.uv.value !== undefined) {
        uviVal = Math.round(data.uv.value);
        let category = 'Low', msg = 'Minimal sun protection needed.', color = 'text-green-400';
        if (uviVal >= 11) { category = 'Extreme'; msg = 'Avoid prolonged outdoor exposure.'; color = 'text-purple-400'; }
        else if (uviVal >= 8) { category = 'Very High'; msg = 'Extra protection is recommended.'; color = 'text-red-400'; }
        else if (uviVal >= 6) { category = 'High'; msg = 'Use sunscreen and seek shade.'; color = 'text-orange-400'; }
        else if (uviVal >= 3) { category = 'Moderate'; msg = 'Consider wearing sunglasses.'; color = 'text-yellow-400'; }

        uvContainer.innerHTML = `
            <div class="flex items-end gap-3 mb-2">
                <span class="text-5xl font-bold text-white">${uviVal}</span>
                <span class="text-lg font-semibold ${color} mb-1">${category}</span>
            </div>
            <p class="text-xs text-gray-400 mt-2 leading-relaxed border-t border-[#262a40] pt-2">${msg}</p>`;
    } else {
        uvContainer.innerHTML = '<p class="text-sm text-gray-400">UV data unavailable</p>';
    }

    const sunContainer = document.getElementById('sun-tracking-container');
    if (current.sys && current.sys.sunrise && current.sys.sunset) {
        const srDate = new Date(current.sys.sunrise * 1000);
        const ssDate = new Date(current.sys.sunset * 1000);
        const srTime = srDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        const ssTime = ssDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

        sunContainer.innerHTML = `
            <div class="flex items-center justify-between border-b border-[#262a40] pb-2">
                <span class="text-gray-400"><i class="fa-solid fa-sun text-yellow-500 mr-2"></i> Sunrise</span>
                <span class="font-bold text-white">${srTime}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-gray-400"><i class="fa-solid fa-sunset text-orange-400 mr-2"></i> Sunset</span>
                <span class="font-bold text-white">${ssTime}</span>
            </div>
        `;
    }
}

function showForecastDetails(dataStr) {
    const data = JSON.parse(dataStr);
    document.getElementById('modal-date').innerText = data.date;
    document.getElementById('modal-desc').innerText = data.desc;
    document.getElementById('modal-max').innerText = data.max;
    document.getElementById('modal-min').innerText = data.min;
    document.getElementById('modal-icon').src = `https://openweathermap.org/img/wn/${data.icon}@4x.png`;
    document.getElementById('forecast-modal').classList.remove('hidden');
}

function closeForecastModal() {
    document.getElementById('forecast-modal').classList.add('hidden');
}

function renderDistrictsGrid(districts) {
    const grid = document.getElementById('districts-grid');
    if(!grid) return;
    grid.innerHTML = '';
    districts.forEach(d => {
        grid.innerHTML += `<button onclick="switchTab('dashboard'); fetchWeatherData('/api/weather?city=${d}')" class="district-btn text-left px-3 py-2 bg-[#262a40] hover:bg-blue-500 text-gray-300 text-sm rounded-lg transition">${d}</button>`;
    });
}

function getFavorites() { return JSON.parse(localStorage.getItem('gujarat_weather_favorites')) || []; }
function updateFavoriteStarUI() {
    const icon = document.getElementById('favorite-icon');
    if (getFavorites().includes(currentActiveCity)) {
        icon.classList.replace('fa-regular', 'fa-solid');
        icon.classList.add('text-yellow-500');
    } else {
        icon.classList.replace('fa-solid', 'fa-regular');
        icon.classList.remove('text-yellow-500');
    }
}
function showAlert(message, isError = true) {
    const box = document.getElementById('alert-box');
    if (!message || !box) { if(box) box.classList.add('hidden'); return; }
    box.innerText = message;
    box.classList.remove('hidden');
    if(isError) setTimeout(() => box.classList.add('hidden'), 5000);
}
