<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weather Dashboard - AK</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome CDN (Icons ke liye) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-[#121212] flex h-screen font-sans overflow-hidden">

    <!-- Sidebar Section -->
    <aside class="w-72 bg-[#1c1c1e] text-gray-400 flex flex-col h-full border-r border-gray-800 shadow-2xl z-10 shrink-0">
        
        <!-- Profile Section (Ab sidebar yahan se shuru hoga) -->
        <div class="p-5 flex justify-between items-center border-b border-gray-800 mt-2">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-[#df6a44] text-white flex items-center justify-center font-bold text-lg">
                    A
                </div>
                <span class="text-white font-medium">Anas Khan</span>
            </div>
            <button class="hover:text-white transition"><i class="fa-solid fa-angles-left"></i></button>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-1 overflow-y-auto py-5 px-3 space-y-1.5 scrollbar-hide">
            <a href="#" class="flex items-center gap-3 px-3 py-2.5 bg-[#3a2824] text-white rounded-lg transition">
                <i class="fa-solid fa-house w-5 text-center text-[#df6a44]"></i>
                <span class="font-medium">Home</span>
            </a>
            <a href="#" class="flex items-center gap-3 px-3 py-2.5 hover:bg-gray-800 hover:text-white rounded-lg transition">
                <i class="fa-solid fa-chart-line w-5 text-center"></i>
                <span>Reports</span>
                <span class="ml-auto bg-[#df6a44] text-white text-[10px] px-1.5 py-0.5 rounded-full">4</span>
            </a>
            <a href="#" class="flex items-center gap-3 px-3 py-2.5 hover:bg-gray-800 hover:text-white rounded-lg transition">
                <i class="fa-solid fa-triangle-exclamation w-5 text-center"></i>
                <span>Weather alerts</span>
                <span class="ml-auto w-2 h-2 bg-[#df6a44] rounded-full"></span>
            </a>
            <a href="#" class="flex items-center gap-3 px-3 py-2.5 hover:bg-gray-800 hover:text-white rounded-lg transition">
                <i class="fa-solid fa-temperature-half w-5 text-center"></i>
                <span>Meteorological cases</span>
            </a>
            <a href="#" class="flex items-center gap-3 px-3 py-2.5 hover:bg-gray-800 hover:text-white rounded-lg transition">
                <i class="fa-solid fa-file-invoice-dollar w-5 text-center"></i>
                <span>Tariffs</span>
            </a>
            <a href="#" class="flex items-center gap-3 px-3 py-2.5 hover:bg-gray-800 hover:text-white rounded-lg transition">
                <i class="fa-regular fa-comment-dots w-5 text-center"></i>
                <span>Support centre</span>
            </a>
            <a href="#" class="flex items-center gap-3 px-3 py-2.5 hover:bg-gray-800 hover:text-white rounded-lg transition">
                <i class="fa-solid fa-circle-info w-5 text-center"></i>
                <span>About us</span>
            </a>
            <a href="#" class="flex items-center gap-3 px-3 py-2.5 hover:bg-gray-800 hover:text-white rounded-lg transition">
                <i class="fa-solid fa-gear w-5 text-center"></i>
                <span>Settings</span>
            </a>
        </nav>

        <!-- Bottom Actions (Button & Dark Mode) -->
        <div class="p-5 space-y-6 border-t border-gray-800">
            <button class="w-full bg-[#df6a44] hover:bg-[#c95936] text-white py-2.5 rounded-xl flex items-center justify-center gap-2 font-medium transition shadow-lg shadow-orange-900/20">
                <i class="fa-solid fa-pen"></i> Ask meteorologist
            </button>
            <div class="flex justify-between items-center cursor-pointer">
                <div class="flex items-center gap-2 text-white font-medium">
                    <i class="fa-solid fa-circle-half-stroke"></i> Dark mode
                </div>
                <!-- Toggle Switch UI -->
                <div class="w-11 h-6 bg-[#df6a44] rounded-full flex items-center px-1 transition-all">
                    <div class="w-4 h-4 bg-white rounded-full transform translate-x-5 shadow-sm"></div>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="flex-1 flex items-center justify-center relative bg-[url('https://images.unsplash.com/photo-1504608524841-42fe6f032b4b?q=80&w=2000&auto=format&fit=crop')] bg-cover bg-center">
        <!-- Dark Overlay -->
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
        
        <!-- Weather Card -->
        <div class="bg-white/90 backdrop-blur-md p-8 rounded-3xl shadow-2xl w-[400px] z-10 border border-white/20">
            <h1 class="text-3xl font-bold text-center text-gray-800 mb-6">Weather Explorer</h1>
            
            <!-- Search Box -->
            <div class="flex space-x-2 mb-6">
                <input type="text" id="city" placeholder="Enter city name..." 
                    class="w-full px-4 py-3 bg-gray-100 border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#df6a44] transition">
                <button id="search-btn" class="bg-[#1c1c1e] hover:bg-[#2c2c2e] text-white px-5 py-3 rounded-xl font-medium transition-colors shadow-lg">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
            </div>

            <!-- Weather Display Section -->
            <div class="text-center mt-6">
                <h2 class="text-2xl font-bold text-gray-800" id="city-name">City Name</h2>
                <p class="text-[#df6a44] font-medium capitalize mt-1" id="weather-desc">Clear Sky</p>
                
                <div class="my-8">
                    <span class="text-7xl font-bold text-gray-800 tracking-tighter" id="temp">25°</span>
                </div>
                
                <div class="flex justify-between text-gray-600 mt-6 border-t border-gray-200 pt-6 px-4">
                    <div class="flex flex-col items-center">
                        <i class="fa-solid fa-droplet text-blue-400 mb-2 text-xl"></i>
                        <p class="text-sm font-medium">Humidity</p>
                        <p class="font-bold text-gray-800" id="humidity">60%</p>
                    </div>
                    <div class="flex flex-col items-center">
                        <i class="fa-solid fa-wind text-gray-400 mb-2 text-xl"></i>
                        <p class="text-sm font-medium">Wind</p>
                        <p class="font-bold text-gray-800" id="wind">5 km/h</p>
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