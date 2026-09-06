<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gujarat Weather Dashboard - AK</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-[#131521] flex h-screen font-sans overflow-hidden text-gray-300">

    <!-- Sidebar Section -->
    <aside class="w-72 bg-[#1b1f30] text-gray-400 flex flex-col h-full border-r border-[#262a40] z-10 shrink-0 hidden lg:flex">
        <nav class="flex-1 overflow-y-auto py-6 px-4 space-y-2 scrollbar-hide">
            <a href="#" class="flex items-center gap-3 px-4 py-3 bg-blue-500 text-white rounded-xl transition shadow-lg shadow-blue-500/30">
                <i class="fa-solid fa-house w-5 text-center"></i><span class="font-medium">Home</span>
            </a>
            <a href="#" class="flex items-center gap-3 px-4 py-3 hover:bg-[#262a40] hover:text-white rounded-xl transition">
                <i class="fa-solid fa-chart-line w-5 text-center"></i><span>Reports</span>
                <span class="ml-auto bg-[#32364a] text-white text-[10px] px-2 py-0.5 rounded-full">4</span>
            </a>
            <a href="#" id="sidebar-alerts-btn" class="flex items-center gap-3 px-4 py-3 hover:bg-[#262a40] hover:text-white rounded-xl transition">
                <i class="fa-solid fa-triangle-exclamation w-5 text-center"></i><span>Weather alerts</span>
                <span id="sidebar-alerts-badge" class="ml-auto bg-blue-500 text-white text-[10px] px-2 py-0.5 rounded-full hidden">0</span>
            </a>
            <a href="#" class="flex items-center gap-3 px-4 py-3 hover:bg-[#262a40] hover:text-white rounded-xl transition">
                <i class="fa-solid fa-temperature-half w-5 text-center"></i><span>Meteorological cases</span>
            </a>
            <a href="#" class="flex items-center gap-3 px-4 py-3 hover:bg-[#262a40] hover:text-white rounded-xl transition">
                <i class="fa-solid fa-file-invoice-dollar w-5 text-center"></i><span>Tariffs</span>
            </a>
            <a href="#" class="flex items-center gap-3 px-4 py-3 hover:bg-[#262a40] hover:text-white rounded-xl transition">
                <i class="fa-regular fa-comment-dots w-5 text-center"></i><span>Support centre</span>
            </a>
            <a href="#" class="flex items-center gap-3 px-4 py-3 hover:bg-[#262a40] hover:text-white rounded-xl transition">
                <i class="fa-solid fa-circle-info w-5 text-center"></i><span>About us</span>
            </a>
            <a href="#" class="flex items-center gap-3 px-4 py-3 hover:bg-[#262a40] hover:text-white rounded-xl transition">
                <i class="fa-solid fa-gear w-5 text-center"></i><span>Settings</span>
            </a>
        </nav>
        <div class="p-5 space-y-6 border-t border-[#262a40]">
            <button class="w-full bg-[#1b2f4f] hover:bg-blue-600 text-blue-400 hover:text-white border border-blue-900/50 py-3 rounded-xl flex items-center justify-center gap-2 font-medium transition">
                <i class="fa-regular fa-comment"></i> Ask meteorologist
            </button>
            <div class="flex justify-between items-center cursor-pointer px-2">
                <div class="flex items-center gap-2 text-gray-300 font-medium">
                    <i class="fa-solid fa-moon"></i> Dark mode
                </div>
                <div class="w-11 h-6 bg-blue-500 rounded-full flex items-center px-1 transition-all">
                    <div class="w-4 h-4 bg-white rounded-full transform translate-x-5 shadow-sm"></div>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="flex-1 overflow-y-auto p-4 md:p-8 relative scrollbar-hide">
        
        <div class="w-full max-w-7xl mx-auto mb-8 text-center lg:text-left flex justify-between items-center">
            <div>
                <h1 class="text-3xl md:text-4xl font-extrabold text-white tracking-wide">Gujarat Weather Dashboard</h1>
                <p class="text-blue-400 mt-2">Real-time weather for all districts & talukas</p>
            </div>
            <div id="alert-box" class="hidden bg-orange-500/20 text-orange-400 px-4 py-2 rounded-lg border border-orange-500/50 text-sm max-w-xs"></div>
        </div>

        <div class="w-full max-w-7xl mx-auto flex flex-col lg:flex-row gap-6 mb-8">
            
            <!-- LEFT COLUMN: Main Weather Card -->
            <div class="w-full lg:w-[400px] shrink-0">
                <div class="bg-[#1b1f30] p-8 rounded-3xl shadow-xl w-full border border-[#262a40]">
                    <!-- Search Box -->
                    <div class="flex space-x-2 mb-6">
                        <input type="text" id="city" placeholder="Search city (e.g., Mahesana)..." 
                            class="w-full px-4 py-3 bg-[#131521] text-white border border-[#262a40] rounded-xl focus:outline-none focus:border-blue-500 transition">
                        
                        <button id="search-btn" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-3 rounded-xl font-medium transition-colors shadow-lg">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </button>
                        
                        <button id="location-btn" title="Use My Location" class="bg-[#262a40] hover:bg-[#32364a] text-blue-400 px-4 py-3 rounded-xl font-medium transition-colors shadow-lg">
                            <i class="fa-solid fa-location-crosshairs"></i>
                        </button>
                    </div>

                    <div class="text-center mt-6">
                        <div class="flex items-center justify-center gap-3">
                            <h2 class="text-2xl font-bold text-white" id="city-name">City Name</h2>
                            <button id="favorite-btn" class="text-gray-500 hover:text-yellow-400 transition text-xl hidden">
                                <i class="fa-regular fa-star" id="favorite-icon"></i>
                            </button>
                        </div>
                        <p class="text-blue-400 font-medium capitalize mt-1" id="weather-desc">Clear Sky</p>
                        
                        <div class="my-8 flex flex-col items-center">
                            <span class="text-7xl font-bold text-white tracking-tighter" id="temp">25°</span>
                            <p class="text-gray-400 mt-2 text-sm font-medium hidden" id="feels-like-container">Feels like <span id="feels-like-temp"></span>°C</p>
                        </div>
                        
                        <div class="flex justify-between text-gray-400 mt-6 border-t border-[#262a40] pt-6 px-4">
                            <div class="flex flex-col items-center">
                                <i class="fa-solid fa-droplet text-blue-500 mb-2 text-xl"></i>
                                <p class="text-sm font-medium">Humidity</p>
                                <p class="font-bold text-white mt-1" id="humidity">60%</p>
                            </div>
                            <div class="flex flex-col items-center">
                                <i class="fa-solid fa-wind text-gray-400 mb-2 text-xl"></i>
                                <p class="text-sm font-medium">Wind</p>
                                <p class="font-bold text-white mt-1" id="wind">5 km/h</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: Extended Features Dashboard -->
            <div id="extended-features" class="flex-1 flex-col gap-6 hidden">
                
                <!-- Hourly Forecast -->
                <div class="bg-[#1b1f30] p-6 rounded-3xl border border-[#262a40] shadow-xl w-full">
                    <h3 class="text-gray-400 font-semibold mb-5 text-sm uppercase tracking-wider"><i class="fa-regular fa-clock mr-2"></i> Hourly Forecast</h3>
                    <div id="hourly-container" class="flex overflow-x-auto scrollbar-hide gap-6 pb-2 snap-x"></div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- 5-Day Forecast -->
                    <div class="bg-[#1b1f30] p-6 rounded-3xl border border-[#262a40] shadow-xl">
                        <h3 class="text-gray-400 font-semibold mb-5 text-sm uppercase tracking-wider"><i class="fa-regular fa-calendar-days mr-2"></i> 5-Day Forecast</h3>
                        <div id="daily-container" class="flex flex-col space-y-1"></div>
                    </div>

                    <!-- Rain Probability & UV Index -->
                    <div class="flex flex-col gap-6">
                        <div class="bg-[#1b1f30] p-6 rounded-3xl border border-[#262a40] shadow-xl flex-1">
                            <h3 class="text-gray-400 font-semibold mb-5 text-sm uppercase tracking-wider"><i class="fa-solid fa-umbrella mr-2"></i> Rain Probability</h3>
                            <div id="rain-container" class="flex overflow-x-auto scrollbar-hide gap-6 pb-2 snap-x"></div>
                        </div>
                        <div class="bg-[#1b1f30] p-6 rounded-3xl border border-[#262a40] shadow-xl flex-1">
                            <h3 class="text-gray-400 font-semibold mb-5 text-sm uppercase tracking-wider"><i class="fa-regular fa-sun mr-2"></i> UV Index</h3>
                            <div id="uv-container" class="h-full flex flex-col justify-center pb-2"></div>
                        </div>
                    </div>
                </div>

                <!-- Temperature Trend Graph -->
                <div class="bg-[#1b1f30] p-6 rounded-3xl border border-[#262a40] shadow-xl w-full">
                    <h3 class="text-gray-400 font-semibold mb-5 text-sm uppercase tracking-wider"><i class="fa-solid fa-chart-area mr-2"></i> Temperature Trend</h3>
                    <div id="temp-graph-container" class="w-full h-32 relative overflow-x-auto scrollbar-hide flex items-center">
                        <p class="text-sm text-gray-400">Loading graph...</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Sunrise & Sunset -->
                    <div class="bg-[#1b1f30] p-6 rounded-3xl border border-[#262a40] shadow-xl lg:col-span-1">
                        <h3 class="text-gray-400 font-semibold mb-5 text-sm uppercase tracking-wider"><i class="fa-solid fa-cloud-sun mr-2"></i> Sun Tracking</h3>
                        <div class="flex flex-col gap-4" id="sun-tracking-container"></div>
                    </div>

                    <!-- Weather Alerts Section -->
                    <div id="alerts-section" class="bg-[#1b1f30] p-6 rounded-3xl border border-[#262a40] shadow-xl lg:col-span-2">
                        <h3 class="text-gray-400 font-semibold mb-5 text-sm uppercase tracking-wider"><i class="fa-solid fa-bell mr-2"></i> Weather Alerts</h3>
                        <div id="alerts-container" class="flex flex-col gap-3"></div>
                    </div>
                </div>

            </div>
        </div>

        <!-- LOWER SECTION: Favorites & Gujarat Explorer -->
        <div class="w-full max-w-7xl mx-auto flex flex-col gap-8 pb-10">
            <!-- Favorites -->
            <div class="bg-[#1b1f30] p-6 rounded-3xl border border-[#262a40] shadow-xl w-full">
                <h3 class="text-gray-400 font-semibold mb-4 text-sm uppercase tracking-wider"><i class="fa-solid fa-star text-yellow-500 mr-2"></i> My Favorite Locations</h3>
                <div id="favorites-container" class="flex overflow-x-auto scrollbar-hide gap-4 pb-2">
                    <p class="text-sm text-gray-500 italic" id="no-favorites-msg">No favorites added yet.</p>
                </div>
            </div>

            <!-- Gujarat Explorer -->
            <div class="bg-[#1b1f30] p-6 rounded-3xl border border-[#262a40] shadow-xl w-full">
                <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                    <h3 class="text-gray-400 font-semibold text-sm uppercase tracking-wider"><i class="fa-solid fa-map-location-dot text-blue-400 mr-2"></i> Gujarat Weather Explorer</h3>
                    <input type="text" id="district-filter" placeholder="Filter districts (e.g., Mahesana)..." class="px-4 py-2 bg-[#131521] text-white border border-[#262a40] rounded-lg focus:outline-none focus:border-blue-500 text-sm w-full md:w-64">
                </div>
                <div id="districts-grid" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3"></div>
            </div>
        </div>
    </main>

    <script>
        const gujaratDistricts = [
            "Ahmedabad", "Amreli", "Anand", "Aravalli", "Banaskantha", "Bharuch", "Bhavnagar", 
            "Botad", "Chhota Udepur", "Dahod", "Dang", "Devbhumi Dwarka", "Gandhinagar", 
            "Gir Somnath", "Jamnagar", "Junagadh", "Kheda", "Kutch", "Mahisagar", "Mahesana", 
            "Morbi", "Narmada", "Navsari", "Panchmahal", "Patan", "Porbandar", "Rajkot", 
            "Sabarkantha", "Surat", "Surendranagar", "Tapi", "Vadodara", "Valsad"
        ];

        let currentActiveCity = "";

        document.addEventListener('DOMContentLoaded', () => {
            renderDistrictsGrid(gujaratDistricts);
            renderFavorites();
        });

        document.getElementById('sidebar-alerts-btn').addEventListener('click', (e) => {
            e.preventDefault();
            const alertsSec = document.getElementById('alerts-section');
            if(!alertsSec.closest('#extended-features').classList.contains('hidden')) {
                alertsSec.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });

        async function fetchWeatherData(queryUrl) {
            showAlert(''); 
            try {
                const response = await fetch(queryUrl);
                const data = await response.json();
                if (!response.ok) { showAlert("Location not found!"); return; }
                updateDashboardUI(data);
            } catch (error) {
                console.error(error);
                showAlert("Network error. Please try again.");
            }
        }

        function updateDashboardUI(data) {
            const current = data.current ? data.current : data;
            currentActiveCity = current.name;

            document.getElementById('city-name').innerText = current.name;
            document.getElementById('weather-desc').innerText = current.weather[0].description;
            document.getElementById('temp').innerText = Math.round(current.main.temp) + '°';
            document.getElementById('humidity').innerText = current.main.humidity + '%';
            document.getElementById('wind').innerText = current.wind.speed + ' km/h';

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
                if (!dailyData[dateStr]) dailyData[dateStr] = { min: item.main.temp_min, max: item.main.temp_max, icon: item.weather[0].icon, dt: item.dt };
                else {
                    if (item.main.temp_min < dailyData[dateStr].min) dailyData[dateStr].min = item.main.temp_min;
                    if (item.main.temp_max > dailyData[dateStr].max) dailyData[dateStr].max = item.main.temp_max;
                    if (item.dt_txt.includes("12:00:00")) dailyData[dateStr].icon = item.weather[0].icon;
                }
            });

            const dayNames = ['SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT'];
            Object.keys(dailyData).slice(0, 5).forEach(date => {
                const dayObj = dailyData[date];
                const dayName = dayNames[new Date(dayObj.dt * 1000).getDay()];
                dailyContainer.innerHTML += `
                    <div class="flex items-center justify-between py-2 border-b border-[#262a40] last:border-0">
                        <span class="text-sm font-medium text-gray-300 w-10">${dayName}</span>
                        <img src="https://openweathermap.org/img/wn/${dayObj.icon}.png" class="w-8 h-8">
                        <div class="flex gap-4 w-24 justify-end">
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
                if (uviVal >= 11) { category = 'Extreme'; msg = 'Avoid prolonged outdoor exposure. Stay in shade when possible.'; color = 'text-purple-400'; }
                else if (uviVal >= 8) { category = 'Very High'; msg = 'Extra protection is recommended. Avoid prolonged exposure.'; color = 'text-red-400'; }
                else if (uviVal >= 6) { category = 'High'; msg = 'Use sunscreen, sunglasses and seek shade during peak hours.'; color = 'text-orange-400'; }
                else if (uviVal >= 3) { category = 'Moderate'; msg = 'Use sunscreen and consider wearing sunglasses.'; color = 'text-yellow-400'; }

                uvContainer.innerHTML = `
                    <div class="flex items-end gap-3 mb-2">
                        <span class="text-5xl font-bold text-white">${uviVal}</span>
                        <span class="text-lg font-semibold ${color} mb-1">${category}</span>
                    </div>
                    <div class="mt-2 text-sm text-gray-300">Peak UV: <span class="font-medium text-white">10:00 AM – 3:00 PM</span></div>
                    <p class="text-xs text-gray-400 mt-2 leading-relaxed border-t border-[#262a40] pt-2">${msg}</p>`;
            } else {
                uvContainer.innerHTML = '<p class="text-sm text-gray-400">UV data unavailable</p>';
            }

            const graphContainer = document.getElementById('temp-graph-container');
            const graphData = forecastList.slice(0, 6);
            if(graphData.length > 0) {
                const temps = graphData.map(d => Math.round(d.main.temp));
                const minT = Math.min(...temps) - 2;
                const maxT = Math.max(...temps) + 2;
                const range = (maxT - minT) || 1;
                const width = 600; const height = 100;
                const step = width / (temps.length - 1);
                
                let points = [];
                let svgHtml = `<svg viewBox="-20 0 640 120" class="w-full min-w-[500px] h-full overflow-visible">`;
                
                temps.forEach((t, i) => {
                    let x = i * step;
                    let y = height - ((t - minT) / range) * (height - 30) - 20;
                    points.push(`${x},${y}`);
                    
                    let timeStr = new Date(graphData[i].dt * 1000).toLocaleTimeString([], { hour: 'numeric', hour12: true });
                    
                    svgHtml += `
                        <text x="${x}" y="${y - 12}" fill="white" font-size="14" font-weight="bold" text-anchor="middle">${t}°</text>
                        <circle cx="${x}" cy="${y}" r="4" fill="#3b82f6" />
                        <text x="${x}" y="${height + 15}" fill="#9ca3af" font-size="12" text-anchor="middle">${timeStr}</text>
                    `;
                });
                svgHtml += `<polyline points="${points.join(' ')}" fill="none" stroke="#3b82f6" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />`;
                svgHtml += `</svg>`;
                graphContainer.innerHTML = svgHtml;
            } else {
                graphContainer.innerHTML = '<p class="text-sm text-gray-400">Temperature trend unavailable</p>';
            }

            const sunContainer = document.getElementById('sun-tracking-container');
            if (current.sys.sunrise && current.sys.sunset) {
                const srDate = new Date(current.sys.sunrise * 1000);
                const ssDate = new Date(current.sys.sunset * 1000);
                const srTime = srDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                const ssTime = ssDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                
                const diffMs = ssDate - srDate;
                const hrs = Math.floor(diffMs / 3600000);
                const mins = Math.floor((diffMs % 3600000) / 60000);

                sunContainer.innerHTML = `
                    <div class="flex items-center justify-between border-b border-[#262a40] pb-2">
                        <span class="text-sm text-gray-400"><i class="fa-solid fa-sun text-yellow-500 mr-2"></i> Sunrise</span>
                        <span class="font-bold text-white">${srTime}</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-[#262a40] pb-2">
                        <span class="text-sm text-gray-400"><i class="fa-solid fa-moon text-blue-300 mr-2"></i> Sunset</span>
                        <span class="font-bold text-white">${ssTime}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-400"><i class="fa-solid fa-stopwatch text-green-400 mr-2"></i> Daylight</span>
                        <span class="font-bold text-white">${hrs}h ${mins}m</span>
                    </div>
                `;
            } else {
                sunContainer.innerHTML = '<p class="text-sm text-gray-400">Sun tracking unavailable</p>';
            }

            const alertsContainer = document.getElementById('alerts-container');
            const alertBadge = document.getElementById('sidebar-alerts-badge');
            let activeAlerts = [];

            const maxTemp = current.main.temp;
            const windSpeed = current.wind.speed;
            const hasHeavyRain = forecastList.some(item => (item.pop || 0) >= 0.7 || item.weather[0].main === 'Thunderstorm');

            if (maxTemp >= 42) activeAlerts.push({ icon: 'fa-temperature-arrow-up', color: 'text-red-500', bg: 'bg-red-500/10 border-red-500/30', title: 'Extreme Heat Warning', desc: 'Temperatures have reached dangerous levels. Avoid outdoors.' });
            else if (maxTemp >= 38) activeAlerts.push({ icon: 'fa-temperature-half', color: 'text-orange-500', bg: 'bg-orange-500/10 border-orange-500/30', title: 'High Temperature', desc: `Temperature is extremely high (${Math.round(maxTemp)}°C). Stay hydrated.` });
            
            if (hasHeavyRain) activeAlerts.push({ icon: 'fa-cloud-showers-heavy', color: 'text-blue-400', bg: 'bg-blue-500/10 border-blue-500/30', title: 'Heavy Rainfall / Storm', desc: 'Heavy rain or thunderstorms are expected in this area soon.' });
            
            if (windSpeed >= 10) activeAlerts.push({ icon: 'fa-wind', color: 'text-gray-300', bg: 'bg-gray-500/20 border-gray-500/40', title: 'Strong Winds', desc: `High wind speeds detected (${windSpeed} km/h).` });
            
            if (uviVal >= 8) activeAlerts.push({ icon: 'fa-sun', color: 'text-yellow-500', bg: 'bg-yellow-500/10 border-yellow-500/30', title: 'Dangerous UV Levels', desc: 'UV Index is exceptionally high. Protect your skin and eyes.' });

            alertsContainer.innerHTML = '';
            if (activeAlerts.length > 0) {
                alertBadge.innerText = activeAlerts.length;
                alertBadge.classList.remove('hidden');
                activeAlerts.forEach(alert => {
                    alertsContainer.innerHTML += `
                        <div class="p-4 rounded-xl border ${alert.bg} flex gap-4 items-start">
                            <i class="fa-solid ${alert.icon} ${alert.color} text-xl mt-1"></i>
                            <div>
                                <h4 class="font-bold ${alert.color} text-sm">${alert.title}</h4>
                                <p class="text-xs text-gray-300 mt-1">${alert.desc}</p>
                            </div>
                        </div>`;
                });
            } else {
                alertBadge.classList.add('hidden');
                alertsContainer.innerHTML = `
                    <div class="p-4 rounded-xl border border-green-500/30 bg-green-500/10 flex items-center gap-3">
                        <i class="fa-solid fa-circle-check text-green-400 text-lg"></i>
                        <span class="text-sm text-green-400 font-medium">No active weather alerts for this location.</span>
                    </div>`;
            }
        }

        document.getElementById('favorite-btn').addEventListener('click', () => {
            if(!currentActiveCity) return;
            let favs = getFavorites();
            if (favs.includes(currentActiveCity)) {
                favs = favs.filter(c => c !== currentActiveCity);
            } else {
                favs.push(currentActiveCity);
            }
            localStorage.setItem('gujarat_weather_favorites', JSON.stringify(favs));
            updateFavoriteStarUI();
            renderFavorites();
        });

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

        async function renderFavorites() {
            const favs = getFavorites();
            const container = document.getElementById('favorites-container');
            const msg = document.getElementById('no-favorites-msg');
            
            if (favs.length === 0) {
                msg.style.display = 'block';
                container.querySelectorAll('.fav-card').forEach(c => c.remove());
                return;
            }
            msg.style.display = 'none';
            container.innerHTML = '<p class="hidden" id="no-favorites-msg"></p>';

            for (let city of favs) {
                try {
                    const res = await fetch(`/api/weather?city=${encodeURIComponent(city)}`);
                    const data = await res.json();
                    const current = data.current ? data.current : data;
                    container.innerHTML += `
                        <div onclick="fetchWeatherData('/api/weather?city=${encodeURIComponent(city)}')" 
                             class="fav-card cursor-pointer flex items-center justify-between gap-4 bg-[#262a40] hover:bg-[#32364a] px-4 py-3 rounded-xl transition min-w-[160px] snap-center border border-[#32364a]">
                            <div class="flex flex-col">
                                <span class="text-white font-medium text-sm flex items-center gap-1"><i class="fa-solid fa-star text-yellow-500 text-[10px]"></i> ${city}</span>
                                <span class="text-gray-400 text-xs capitalize">${current.weather[0].description}</span>
                            </div>
                            <span class="text-lg font-bold text-white">${Math.round(current.main.temp)}°</span>
                        </div>`;
                } catch(e) {}
            }
        }

        document.getElementById('search-btn').addEventListener('click', () => {
            const city = document.getElementById('city').value.trim();
            if (city) fetchWeatherData(`/api/weather?city=${encodeURIComponent(city)}`);
        });

        document.getElementById('location-btn').addEventListener('click', () => {
            if (navigator.geolocation) {
                showAlert('Detecting location...', false);
                navigator.geolocation.getCurrentPosition(
                    (position) => { fetchWeatherData(`/api/weather?lat=${position.coords.latitude}&lon=${position.coords.longitude}`); },
                    (error) => { showAlert("Location permission was denied. Please search manually."); }
                );
            } else showAlert("Geolocation is not supported by this browser.");
        });

        function renderDistrictsGrid(districts) {
            const grid = document.getElementById('districts-grid');
            grid.innerHTML = '';
            districts.forEach(d => {
                grid.innerHTML += `<button onclick="fetchWeatherData('/api/weather?city=${d}')" class="district-btn text-left px-3 py-2 bg-[#262a40] hover:bg-blue-500 hover:text-white text-gray-300 text-sm rounded-lg transition border border-[#32364a]">${d}</button>`;
            });
        }

        document.getElementById('district-filter').addEventListener('input', (e) => {
            renderDistrictsGrid(gujaratDistricts.dir ? [] : gujaratDistricts.filter(d => d.toLowerCase().includes(e.target.value.toLowerCase())));
        });

        function showAlert(message, isError = true) {
            const box = document.getElementById('alert-box');
            if (!message) { box.classList.add('hidden'); return; }
            box.innerText = message;
            box.classList.replace(isError ? 'text-blue-400' : 'text-orange-400', isError ? 'text-orange-400' : 'text-blue-400');
            box.classList.remove('hidden');
            if(isError) setTimeout(() => box.classList.add('hidden'), 5000);
        }
    </script>
</body>
</html>