<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weather App - AK</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen font-sans">

    <div class="bg-white p-8 rounded-2xl shadow-xl w-96">
        <h1 class="text-3xl font-bold text-center text-blue-600 mb-6">Weather App</h1>
        
        <!-- Search Box -->
        <div class="flex space-x-2 mb-6">
            <input type="text" id="city" placeholder="Enter city name..." 
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            <button id="search-btn" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                Search
            </button>
        </div>

        <!-- Weather Display Section -->
        <div class="text-center mt-6">
            <h2 class="text-2xl font-semibold text-gray-800" id="city-name">City Name</h2>
            <p class="text-gray-500 capitalize" id="weather-desc">Clear Sky</p>
            
            <div class="my-6">
                <span class="text-6xl font-bold text-gray-800" id="temp">25°C</span>
            </div>
            
            <div class="flex justify-between text-gray-600 mt-6 border-t pt-4 px-2">
                <div>
                    <p class="text-sm">Humidity</p>
                    <p class="font-semibold" id="humidity">60%</p>
                </div>
                <div>
                    <p class="text-sm">Wind Speed</p>
                    <p class="font-semibold" id="wind">5 km/h</p>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript hamesha yahan (body close hone se pehle) aati hai -->
    <script>
        document.getElementById('search-btn').addEventListener('click', async () => {
            const city = document.getElementById('city').value.trim();
            if (!city) {
                alert("Please enter a city name!");
                return;
            }

            try {
                // Laravel backend route ko call karna
                const response = await fetch(`/api/weather?city=${encodeURIComponent(city)}`);
                const data = await response.json();

                if (response.ok) {
                    // Real data ko HTML UI mein update karna
                    document.getElementById('city-name').innerText = data.name;
                    document.getElementById('weather-desc').innerText = data.weather[0].description;
                    document.getElementById('temp').innerText = Math.round(data.main.temp) + '°C';
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