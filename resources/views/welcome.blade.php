<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gujarat Weather Dashboard - AK</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-[#131521] flex h-screen font-sans overflow-hidden text-gray-300">

    <!-- Sidebar Section -->
    <aside class="w-72 bg-[#1b1f30] text-gray-400 flex flex-col h-full border-r border-[#262a40] z-10 shrink-0">
        <!-- Navigation Links -->
        <nav class="flex-1 overflow-y-auto py-6 px-4 space-y-2 scrollbar-hide">
            <a href="#" class="flex items-center gap-3 px-4 py-3 bg-blue-500 text-white rounded-xl transition shadow-lg shadow-blue-500/30">
                <i class="fa-solid fa-house w-5 text-center"></i>
                <span class="font-medium">Home</span>
            </a>
            <a href="#" class="flex items-center gap-3 px-4 py-3 hover:bg-[#262a40] hover:text-white rounded-xl transition">
                <i class="fa-solid fa-chart-line w-5 text-center"></i>
                <span>Reports</span>
                <span class="ml-auto bg-[#32364a] text-white text-[10px] px-2 py-0.5 rounded-full">4</span>
            </a>
            <a href="#" class="flex items-center gap-3 px-4 py-3 hover:bg-[#262a40] hover:text-white rounded-xl transition">
                <i class="fa-solid fa-triangle-exclamation w-5 text-center"></i>
                <span>Weather alerts</span>
                <span class="ml-auto w-2 h-2 bg-blue-500 rounded-full"></span>
            </a>
            <a href="#" class="flex items-center gap-3 px-4 py-3 hover:bg-[#262a40] hover:text-white rounded-xl transition">
                <i class="fa-solid fa-temperature-half w-5 text-center"></i>
                <span>Meteorological cases</span>
            </a>
            <a href="#" class="flex items-center gap-3 px-4 py-3 hover:bg-[#262a40] hover:text-white rounded-xl transition">
                <i class="fa-solid fa-file-invoice-dollar w-5 text-center"></i>
                <span>Tariffs</span>
            </a>
            <a href="#" class="flex items-center gap-3 px-4 py-3 hover:bg-[#262a40] hover:text-white rounded-xl transition">
                <i class="fa-regular fa-comment-dots w-5 text-center"></i>
                <span>Support centre</span>
            </a>
            <a href="#" class="flex items-center gap-3 px-4 py-3 hover:bg-[#262a40] hover:text-white rounded-xl transition">
                <i class="fa-solid fa-circle-info w-5 text-center"></i>
                <span>About us</span>
            </a>
            <a href="#" class="flex items-center gap-3 px-4 py-3 hover:bg-[#262a40] hover:text-white rounded-xl transition">
                <i class="fa-solid fa-gear w-5 text-center"></i>
                <span>Settings</span>
            </a>
        </nav>

        <!-- Bottom Actions -->
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
    <main class="flex-1 flex flex-col items-center justify-center relative p-8">
        
        <!-- Dashboard Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-extrabold text-white tracking-wide">Gujarat Weather Dashboard</h1>
            <p class="text-blue-400 mt-2">Real-time weather for all districts & talukas</p>
        </div>

        <!-- Weather Card -->
        <div class="bg-[#1b1f30] p-8 rounded-3xl shadow-xl w-[450px] z-10 border border-[#262a40]">
            
            <!-- Search Box with Datalist -->
            <div class="flex space-x-2 mb-6">
                <input type="text" id="city" list="gujarat-cities" placeholder="Search Gujarat city (e.g., Ahmedabad)..." 
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
                    <option value="Anand"></option>
                    <option value="Navsari"></option>
                    <option value="Morbi"></option>
                    <option value="Bharuch"></option>
                    <option value="Surendranagar"></option>
                    <option value="Porbandar"></option>
                    <option value="Mehsana"></option>
                    <option value="Bhuj"></option>
                    <option value="Amreli"></option>
                    <option value="Patan"></option>
                    <option value="Palanpur"></option>
                    <option value="Dahod"></option>
                    <option value="Botad"></option>
                    <option value="Nadiad"></option>
                    <option value="Godhra"></option>
                    <option value="Vapi"></option>
                    <option value="Valsad"></option>
                    <option value="Gondal"></option>
                    <option value="Jetpur"></option>
                    <option value="Kalol"></option>
                    <option value="Deesa"></option>
                    <option value="Mahuva"></option>
                    <option value="Keshod"></option>
                    <option value="Wadhwan"></option>
                    <option value="Ankleshwar"></option>
                    <option value="Bardoli"></option>
                    <option value="Vyara"></option>
                    <option value="Halol"></option>
                    <option value="Kadi"></option>
                    <option value="Visnagar"></option>
                    <option value="Dholka"></option>
                    <option value="Viramgam"></option>
                    <option value="Sanand"></option>
                    <option value="Mandvi"></option>
                    <option value="Anjar"></option>
                </datalist>

                <button id="search-btn" class="bg-blue-500 hover:bg-blue-600 text-white px-5 py-3 rounded-xl font-medium transition-colors shadow-lg">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
            </div>

            <!-- Weather Display Section -->
            <div class="text-center mt-6">
                <h2 class="text-2xl font-bold text-white" id="city-name">City Name</h2>
                <p class="text-blue-400 font-medium capitalize mt-1" id="weather-desc">Clear Sky</p>
                
                <div class="my-8 flex flex-col items-center">
                    <span class="text-7xl font-bold text-white tracking-tighter" id="temp">25°</span>
                    <!-- Naya Feels Like Element -->
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

                if (response.ok) {
                    document.getElementById('city-name').innerText = data.name;
                    document.getElementById('weather-desc').innerText = data.weather[0].description;
                    document.getElementById('temp').innerText = Math.round(data.main.temp) + '°';
                    document.getElementById('humidity').innerText = data.main.humidity + '%';
                    document.getElementById('wind').innerText = data.wind.speed + ' km/h';

                    // Feature 1: Feels Like Temperature Logic
                    const feelsLikeContainer = document.getElementById('feels-like-container');
                    const feelsLikeTemp = document.getElementById('feels-like-temp');
                    
                    if (data.main && data.main.feels_like !== undefined && data.main.feels_like !== null) {
                        feelsLikeTemp.innerText = Math.round(data.main.feels_like);
                        feelsLikeContainer.classList.remove('hidden');
                    } else {
                        // Gracefully hide if data is missing
                        feelsLikeContainer.classList.add('hidden');
                    }

                } else {
                    alert("City not found! Please check the spelling.");
                }
            } catch (error) {
                console.error("Error fetching weather data:", error);
                alert("Something went wrong!");
            }
        });
    </script>
</body>
</html>