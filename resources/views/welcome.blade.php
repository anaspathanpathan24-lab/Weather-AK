<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gujarat Weather Dashboard - AK</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-[#131521] flex h-screen font-sans overflow-hidden text-gray-300">

    <!-- Sidebar Section (Unchanged) -->
    <aside class="w-72 bg-[#1b1f30] text-gray-400 flex flex-col h-full border-r border-[#262a40] z-10 shrink-0 hidden lg:flex">
        <nav class="flex-1 overflow-y-auto py-6 px-4 space-y-2 scrollbar-hide">
            <a href="#" class="flex items-center gap-3 px-4 py-3 bg-blue-500 text-white rounded-xl transition shadow-lg shadow-blue-500/30">
                <i class="fa-solid fa-house w-5 text-center"></i><span class="font-medium">Home</span>
            </a>
            <a href="#" class="flex items-center gap-3 px-4 py-3 hover:bg-[#262a40] hover:text-white rounded-xl transition">
                <i class="fa-solid fa-chart-line w-5 text-center"></i><span>Reports</span>
                <span class="ml-auto bg-[#32364a] text-white text-[10px] px-2 py-0.5 rounded-full">4</span>
            </a>
            <a href="#" class="flex items-center gap-3 px-4 py-3 hover:bg-[#262a40] hover:text-white rounded-xl transition">
                <i class="fa-solid fa-triangle-exclamation w-5 text-center"></i><span>Weather alerts</span>
                <span class="ml-auto w-2 h-2 bg-blue-500 rounded-full"></span>
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

    <!-- Main Content Area (Updated to support Dashboard Grid) -->
    <main class="flex-1 overflow-y-auto p-4 md:p-8 relative scrollbar-hide">
        
        <!-- Dashboard Header -->
        <div class="w-full max-w-7xl mx-auto mb-8 text-center lg:text-left">
            <h1 class="text-3xl md:text-4xl font-extrabold text-white tracking-wide">Gujarat Weather Dashboard</h1>
            <p class="text-blue-400 mt-2">Real-time weather for all districts & talukas</p>
        </div>

        <div class="w-full max-w-7xl mx-auto flex flex-col lg:flex-row gap-6">
            
            <!-- LEFT COLUMN: Existing Weather Card -->
            <div class="w-full lg:w-[400px] shrink-0">
                <div class="bg-[#1b1f30] p-8 rounded-3xl shadow-xl w-full border border-[#262a40]">
                    <div class="flex space-x-2 mb-6">
                        <input type="text" id="city" list="gujarat-cities" placeholder="Search Gujarat city..." 
                            class="w-full px-5 py-3 bg-[#131521] text-white border border-[#262a40] rounded-xl focus:outline-none focus:border-blue-500 transition">
                        
                        <datalist id="gujarat-cities">
                            <option value="Ahmedabad"></option>
                            <option value="Surat"></option>
                            <option value="Vadodara"></option>
                            <option value="Rajkot"></option>
                            <option value="Bhavnagar"></option>
                            <option value="Jamnagar"></option>
                            <option value="Junagadh"></option>
                            <option value="Gandhinagar"></option>
                            <option value="Mehsana"></option>
                            <option value="Surendranagar"></option>
                        </datalist>

                        <button id="search-btn" class="bg-blue-500 hover:bg-blue-600 text-white px-5 py-3 rounded-xl font-medium transition-colors shadow-lg">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </button>
                    </div>

                    <div class="text-center mt-6">
                        <h2 class="text-2xl font-bold text-white" id="city-name">City Name</h2>
                        <p class="text-blue-400 font-medium capitalize mt-1" id="weather-desc">Clear Sky</p>
                        
                        <div class="my-8 flex flex-col items-center">
                            <span class="text-7xl font-bold text-white tracking-tighter" id="temp">25°</span>
                            <!-- Feature 1: Feels Like -->
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

            <!-- RIGHT COLUMN: Extended Features Dashboard (Hidden until searched) -->
            <div id="extended-features" class="flex-1 flex-col gap-6 hidden">
                
                <!-- Feature 2: Hourly Forecast -->
                <div class="bg-[#1b1f30] p-6 rounded-3xl border border-[#262a40] shadow-xl w-full">
                    <h3 class="text-gray-400 font-semibold mb-5 text-sm uppercase tracking-wider"><i class="fa-regular fa-clock mr-2"></i> Hourly Forecast</h3>
                    <div id="hourly-container" class="flex overflow-x-auto scrollbar-hide gap-6 pb-2 snap-x">
                        <!-- JS injects hourly here -->
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                    <!-- Feature 3: 5-Day Forecast -->
                    <div class="bg-[#1b1f30] p-6 rounded-3xl border border-[#262a40] shadow-xl">
                        <h3 class="text-gray-400 font-semibold mb-5 text-sm uppercase tracking-wider"><i class="fa-regular fa-calendar-days mr-2"></i> 5-Day Forecast</h3>
                        <div id="daily-container" class="flex flex-col space-y-1">
                            <!-- JS injects daily here -->
                        </div>
                    </div>

                    <!-- Right Sub-column for Rain & UV -->
                    <div class="flex flex-col gap-6">
                        <!-- Feature 4: Rain Probability -->
                        <div class="bg-[#1b1f30] p-6 rounded-3xl border border-[#262a40] shadow-xl flex-1">
                            <h3 class="text-gray-400 font-semibold mb-5 text-sm uppercase tracking-wider"><i class="fa-solid fa-umbrella mr-2"></i> Rain Probability</h3>
                            <div id="rain-container" class="flex overflow-x-auto scrollbar-hide gap-6 pb-2 snap-x">
                                <!-- JS injects rain probability here -->
                            </div>
                        </div>

                        <!-- Feature 5: UV Index -->
                        <div class="bg-[#1b1f30] p-6 rounded-3xl border border-[#262a40] shadow-xl flex-1">
                            <h3 class="text-gray-400 font-semibold mb-5 text-sm uppercase tracking-wider"><i class="fa-regular fa-sun mr-2"></i> UV Index</h3>
                            <div id="uv-container" class="h-full flex flex-col justify-center pb-4">
                                <!-- JS injects UV Index here -->
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <!-- JavaScript -->
    <script>
        document.getElementById('search-btn').addEventListener('click', async () => {
            const city = document.getElementById('city').value.trim();
            if (!city) {
                alert("Please enter a city name!");
                return;
            }

            try {
                const response = await fetch(`/api/weather?city=${encodeURIComponent(city)}`);
                const data = await response.json();

                if (!response.ok) {
                    alert("City not found! Please check the spelling.");
                    return;
                }

                // Handle both old controller format and new combined controller format safely
                const current = data.current ? data.current : data;

                // 1. UPDATE MAIN CARD
                document.getElementById('city-name').innerText = current.name;
                document.getElementById('weather-desc').innerText = current.weather[0].description;
                document.getElementById('temp').innerText = Math.round(current.main.temp) + '°';
                document.getElementById('humidity').innerText = current.main.humidity + '%';
                document.getElementById('wind').innerText = current.wind.speed + ' km/h';

                // Feels Like
                const feelsLikeContainer = document.getElementById('feels-like-container');
                if (current.main.feels_like !== undefined) {
                    document.getElementById('feels-like-temp').innerText = Math.round(current.main.feels_like);
                    feelsLikeContainer.classList.remove('hidden');
                } else {
                    feelsLikeContainer.classList.add('hidden');
                }

                // If Backend is not updated yet, stop here gracefully to avoid crashing.
                if (!data.forecast) return;

                // Reveal Dashboard
                document.getElementById('extended-features').classList.replace('hidden', 'flex');

                // 2 & 4. HOURLY FORECAST & RAIN PROBABILITY
                const hourlyContainer = document.getElementById('hourly-container');
                const rainContainer = document.getElementById('rain-container');
                hourlyContainer.innerHTML = '';
                rainContainer.innerHTML = '';

                // Take next 6 time slots
                data.forecast.list.slice(0, 6).forEach(item => {
                    const date = new Date(item.dt * 1000);
                    let hours = date.getHours();
                    const ampm = hours >= 12 ? 'PM' : 'AM';
                    hours = hours % 12 || 12;
                    const timeStr = `${hours} ${ampm}`;
                    const iconUrl = `https://openweathermap.org/img/wn/${item.weather[0].icon}.png`;
                    const temp = Math.round(item.main.temp);
                    const pop = Math.round((item.pop || 0) * 100);

                    // Hourly Item
                    hourlyContainer.innerHTML += `
                        <div class="flex flex-col items-center min-w-[60px] snap-center">
                            <span class="text-xs text-gray-400 mb-2">${timeStr}</span>
                            <img src="${iconUrl}" alt="icon" class="w-8 h-8">
                            <span class="font-semibold text-white mt-2">${temp}°C</span>
                        </div>
                    `;

                    // Rain Item
                    rainContainer.innerHTML += `
                        <div class="flex flex-col items-center min-w-[60px] snap-center">
                            <span class="text-xs text-gray-400 mb-2">${timeStr}</span>
                            <i class="fa-solid fa-cloud-rain text-blue-400 my-2"></i>
                            <span class="font-semibold text-white">${pop}%</span>
                        </div>
                    `;
                });

                // 3. 5-DAY FORECAST
                const dailyContainer = document.getElementById('daily-container');
                dailyContainer.innerHTML = '';
                const dailyData = {};
                
                // Group forecast by day
                data.forecast.list.forEach(item => {
                    const dateStr = item.dt_txt.split(' ')[0];
                    if (!dailyData[dateStr]) {
                        dailyData[dateStr] = { min: item.main.temp_min, max: item.main.temp_max, icon: item.weather[0].icon, dt: item.dt };
                    } else {
                        if (item.main.temp_min < dailyData[dateStr].min) dailyData[dateStr].min = item.main.temp_min;
                        if (item.main.temp_max > dailyData[dateStr].max) dailyData[dateStr].max = item.main.temp_max;
                        // Grab midday icon if available
                        if (item.dt_txt.includes("12:00:00")) dailyData[dateStr].icon = item.weather[0].icon;
                    }
                });

                const dayNames = ['SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT'];
                const days = Object.keys(dailyData).slice(0, 5);
                
                days.forEach(date => {
                    const dayObj = dailyData[date];
                    const dayName = dayNames[new Date(dayObj.dt * 1000).getDay()];
                    const iconUrl = `https://openweathermap.org/img/wn/${dayObj.icon}.png`;
                    
                    dailyContainer.innerHTML += `
                        <div class="flex items-center justify-between py-2 border-b border-[#262a40] last:border-0">
                            <span class="text-sm font-medium text-gray-300 w-10">${dayName}</span>
                            <img src="${iconUrl}" alt="icon" class="w-8 h-8">
                            <div class="flex gap-4 w-24 justify-end">
                                <span class="text-sm font-bold text-white">${Math.round(dayObj.max)}°</span>
                                <span class="text-sm font-medium text-gray-500">${Math.round(dayObj.min)}°</span>
                            </div>
                        </div>
                    `;
                });

                // 5. UV INDEX
                const uvContainer = document.getElementById('uv-container');
                if (data.uv && data.uv.value !== undefined) {
                    const uvi = Math.round(data.uv.value);
                    let category = 'Low', msg = 'Minimal protection needed.', color = 'text-green-400';
                    
                    if (uvi >= 11) { category = 'Extreme'; msg = 'Avoid prolonged outdoor exposure.'; color = 'text-purple-400'; }
                    else if (uvi >= 8) { category = 'Very High'; msg = 'Extra protection recommended. Avoid direct sun.'; color = 'text-red-400'; }
                    else if (uvi >= 6) { category = 'High'; msg = 'Use sunscreen, sunglasses and avoid prolonged exposure.'; color = 'text-orange-400'; }
                    else if (uvi >= 3) { category = 'Moderate'; msg = 'Use sunscreen and seek shade during peak hours.'; color = 'text-yellow-400'; }

                    uvContainer.innerHTML = `
                        <div class="flex items-end gap-3 mb-2">
                            <span class="text-5xl font-bold text-white">${uvi}</span>
                            <span class="text-lg font-semibold ${color} mb-1">${category}</span>
                        </div>
                        <p class="text-sm text-gray-400 mt-1 leading-relaxed">${msg}</p>
                    `;
                } else {
                    uvContainer.innerHTML = '<p class="text-sm text-gray-400">Data unavailable</p>';
                }

            } catch (error) {
                console.error("Error:", error);
                alert("Something went wrong!");
            }
        });
    </script>
</body>
</html>