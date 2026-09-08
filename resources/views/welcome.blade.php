<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gujarat Weather Intelligence Platform - AK</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-[#131521] flex h-screen font-sans overflow-hidden text-gray-300">

    <!-- Sidebar Section (Unchanged style & elements) -->
    <aside class="w-72 bg-[#1b1f30] text-gray-400 flex flex-col h-full border-r border-[#262a40] z-10 shrink-0 hidden lg:flex">
        <nav class="flex-1 overflow-y-auto py-6 px-4 space-y-2 scrollbar-hide">
            <a href="#" onclick="switchTab('dashboard')" class="nav-link flex items-center gap-3 px-4 py-3 bg-blue-500 text-white rounded-xl transition shadow-lg shadow-blue-500/30" id="link-dashboard">
                <i class="fa-solid fa-house w-5 text-center"></i><span class="font-medium">Home</span>
            </a>
            <a href="#" onclick="switchTab('map')" class="nav-link flex items-center gap-3 px-4 py-3 hover:bg-[#262a40] hover:text-white rounded-xl transition" id="link-map">
                <i class="fa-solid fa-map w-5 text-center"></i><span>Gujarat Weather Map</span>
            </a>
            <a href="#" onclick="switchTab('historical')" class="nav-link flex items-center gap-3 px-4 py-3 hover:bg-[#262a40] hover:text-white rounded-xl transition" id="link-historical">
                <i class="fa-solid fa-chart-line w-5 text-center"></i><span>Historical Weather</span>
                <span class="ml-auto bg-[#32364a] text-white text-[10px] px-2 py-0.5 rounded-full">New</span>
            </a>
            <a href="#" id="sidebar-alerts-btn" class="flex items-center gap-3 px-4 py-3 hover:bg-[#262a40] hover:text-white rounded-xl transition">
                <i class="fa-solid fa-triangle-exclamation w-5 text-center"></i><span>Weather alerts</span>
                <span id="sidebar-alerts-badge" class="ml-auto bg-blue-500 text-white text-[10px] px-2 py-0.5 rounded-full hidden">0</span>
            </a>
            <a href="#" onclick="switchTab('ai')" class="nav-link flex items-center gap-3 px-4 py-3 hover:bg-[#262a40] hover:text-white rounded-xl transition" id="link-ai">
                <i class="fa-solid fa-robot w-5 text-center"></i><span>Ask meteorologist</span>
            </a>
            <a href="#" class="flex items-center gap-3 px-4 py-3 hover:bg-[#262a40] hover:text-white rounded-xl transition">
                <i class="fa-solid fa-circle-info w-5 text-center"></i><span>About us</span>
            </a>
        </nav>
        <div class="p-5 space-y-6 border-t border-[#262a40]">
            <button onclick="switchTab('ai')" class="w-full bg-[#1b2f4f] hover:bg-blue-600 text-blue-400 hover:text-white border border-blue-900/50 py-3 rounded-xl flex items-center justify-center gap-2 font-medium transition">
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

    <!-- Main Content Container -->
    <main class="flex-1 overflow-y-auto p-4 md:p-8 relative scrollbar-hide">
        
        <div class="w-full max-w-7xl mx-auto mb-8 text-center lg:text-left flex justify-between items-center">
            <div>
                <h1 class="text-3xl md:text-4xl font-extrabold text-white tracking-wide">Gujarat Weather Intelligence Platform</h1>
                <p class="text-blue-400 mt-2">Advanced real-time analytics, maps & AI meteorology</p>
            </div>
            <div id="alert-box" class="hidden bg-orange-500/20 text-orange-400 px-4 py-2 rounded-lg border border-orange-500/50 text-sm max-w-xs"></div>
        </div>

        <!-- TAB 1: MAIN DASHBOARD VIEW -->
        <div id="tab-dashboard" class="tab-content">
            <div class="w-full max-w-7xl mx-auto flex flex-col lg:flex-row gap-6 mb-8">
                <!-- Weather Card -->
                <div class="w-full lg:w-[400px] shrink-0">
                    <div class="bg-[#1b1f30] p-8 rounded-3xl shadow-xl w-full border border-[#262a40]">
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

                <!-- Extended Metrics Grid -->
                <div id="extended-features" class="flex-1 flex-col gap-6 hidden">
                    <div class="bg-[#1b1f30] p-6 rounded-3xl border border-[#262a40] shadow-xl w-full">
                        <h3 class="text-gray-400 font-semibold mb-5 text-sm uppercase tracking-wider"><i class="fa-regular fa-clock mr-2"></i> Hourly Forecast</h3>
                        <div id="hourly-container" class="flex overflow-x-auto scrollbar-hide gap-6 pb-2 snap-x"></div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-[#1b1f30] p-6 rounded-3xl border border-[#262a40] shadow-xl">
                            <h3 class="text-gray-400 font-semibold mb-5 text-sm uppercase tracking-wider"><i class="fa-regular fa-calendar-days mr-2"></i> 5-Day Forecast</h3>
                            <div id="daily-container" class="flex flex-col space-y-1"></div>
                        </div>
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

                    <div class="bg-[#1b1f30] p-6 rounded-3xl border border-[#262a40] shadow-xl w-full">
                        <h3 class="text-gray-400 font-semibold mb-5 text-sm uppercase tracking-wider"><i class="fa-solid fa-chart-area mr-2"></i> Temperature Trend</h3>
                        <div id="temp-graph-container" class="w-full h-32 relative overflow-x-auto scrollbar-hide flex items-center">
                            <p class="text-sm text-gray-400">Loading graph...</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="bg-[#1b1f30] p-6 rounded-3xl border border-[#262a40] shadow-xl lg:col-span-1">
                            <h3 class="text-gray-400 font-semibold mb-5 text-sm uppercase tracking-wider"><i class="fa-solid fa-cloud-sun mr-2"></i> Sun Tracking</h3>
                            <div class="flex flex-col gap-4" id="sun-tracking-container"></div>
                        </div>
                        <div id="alerts-section" class="bg-[#1b1f30] p-6 rounded-3xl border border-[#262a40] shadow-xl lg:col-span-2">
                            <h3 class="text-gray-400 font-semibold mb-5 text-sm uppercase tracking-wider"><i class="fa-solid fa-bell mr-2"></i> Weather Alerts</h3>
                            <div id="alerts-container" class="flex flex-col gap-3"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Favorites & Explorer Section -->
            <div class="w-full max-w-7xl mx-auto flex flex-col gap-8 pb-10">
                <div class="bg-[#1b1f30] p-6 rounded-3xl border border-[#262a40] shadow-xl w-full">
                    <h3 class="text-gray-400 font-semibold mb-4 text-sm uppercase tracking-wider"><i class="fa-solid fa-star text-yellow-500 mr-2"></i> My Favorite Locations</h3>
                    <div id="favorites-container" class="flex overflow-x-auto scrollbar-hide gap-4 pb-2">
                        <p class="text-sm text-gray-500 italic" id="no-favorites-msg">No favorites added yet.</p>
                    </div>
                </div>

                <div class="bg-[#1b1f30] p-6 rounded-3xl border border-[#262a40] shadow-xl w-full">
                    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                        <h3 class="text-gray-400 font-semibold text-sm uppercase tracking-wider"><i class="fa-solid fa-map-location-dot text-blue-400 mr-2"></i> Gujarat Weather Explorer</h3>
                        <input type="text" id="district-filter" placeholder="Filter districts (e.g., Mahesana)..." class="px-4 py-2 bg-[#131521] text-white border border-[#262a40] rounded-lg focus:outline-none focus:border-blue-500 text-sm w-full md:w-64">
                    </div>
                    <div id="districts-grid" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3"></div>
                </div>
            </div>
        </div>

        <!-- TAB 2: FEATURE 13 - GUJARAT INTERACTIVE WEATHER MAP VIEW -->
        <div id="tab-map" class="tab-content hidden">
            <div class="w-full max-w-7xl mx-auto bg-[#1b1f30] p-8 rounded-3xl border border-[#262a40] shadow-xl">
                <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                    <div>
                        <h2 class="text-2xl font-bold text-white">Gujarat Weather Map & District Grid</h2>
                        <p class="text-sm text-gray-400 mt-1">Interactive spatial overview across Gujarat districts</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <label class="text-sm text-gray-400">Layer:</label>
                        <select id="map-layer-select" class="bg-[#131521] text-white border border-[#262a40] px-4 py-2 rounded-xl text-sm focus:outline-none">
                            <option value="temp">Temperature</option>
                            <option value="condition">Weather Condition</option>
                            <option value="wind">Wind</option>
                            <option value="humidity">Humidity</option>
                        </select>
                    </div>
                </div>

                <!-- Legend Scale -->
                <div class="flex flex-wrap items-center gap-4 mb-6 bg-[#131521] p-4 rounded-xl border border-[#262a40] text-xs">
                    <span class="font-semibold text-white">Temperature Scale Legend:</span>
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-blue-400"></span> &lt; 20°C</span>
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-teal-400"></span> 20–25°C</span>
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-green-400"></span> 25–30°C</span>
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-orange-400"></span> 30–35°C</span>
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-red-500"></span> &gt; 35°C</span>
                </div>

                <!-- Interactive District Cards Grid simulating Regional Map nodes -->
                <div id="interactive-map-grid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <!-- Injected dynamically -->
                </div>
            </div>
        </div>

        <!-- TAB 3: FEATURE 14 - HISTORICAL WEATHER ANALYTICS VIEW -->
        <div id="tab-historical" class="tab-content hidden">
            <div class="w-full max-w-7xl mx-auto bg-[#1b1f30] p-8 rounded-3xl border border-[#262a40] shadow-xl">
                <h2 class="text-2xl font-bold text-white mb-2">Historical Weather Analytics</h2>
                <p class="text-sm text-gray-400 mb-6">Analyze historical climate metrics, temperature trends, and district comparisons across Gujarat.</p>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 bg-[#131521] p-6 rounded-2xl border border-[#262a40]">
                    <div>
                        <label class="block text-xs text-gray-400 mb-2">Select District / City</label>
                        <select id="hist-city" class="w-full bg-[#1b1f30] text-white border border-[#262a40] px-4 py-2.5 rounded-xl text-sm"></select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 mb-2">From Date</label>
                        <input type="date" id="hist-from" value="2026-08-01" class="w-full bg-[#1b1f30] text-white border border-[#262a40] px-4 py-2 rounded-xl text-sm">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 mb-2">To Date</label>
                        <input type="date" id="hist-to" value="2026-08-31" class="w-full bg-[#1b1f30] text-white border border-[#262a40] px-4 py-2 rounded-xl text-sm">
                    </div>
                    <div class="flex items-end">
                        <button onclick="fetchHistoricalData()" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-medium py-2.5 rounded-xl transition shadow-lg text-sm">
                            <i class="fa-solid fa-chart-column mr-2"></i> Analyze
                        </button>
                    </div>
                </div>

                <div id="historical-results" class="hidden flex flex-col gap-6">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-[#131521] p-4 rounded-2xl border border-[#262a40]">
                            <span class="text-xs text-gray-400">Avg Temperature</span>
                            <h4 class="text-2xl font-bold text-white mt-1" id="hist-avg">--</h4>
                        </div>
                        <div class="bg-[#131521] p-4 rounded-2xl border border-[#262a40]">
                            <span class="text-xs text-gray-400">Max / Min Peak</span>
                            <h4 class="text-2xl font-bold text-white mt-1"><span id="hist-max">--</span> / <span id="hist-min" class="text-gray-400 text-lg">--</span></h4>
                        </div>
                        <div class="bg-[#131521] p-4 rounded-2xl border border-[#262a40]">
                            <span class="text-xs text-gray-400">Total Rainfall</span>
                            <h4 class="text-2xl font-bold text-white mt-1" id="hist-rain">--</h4>
                        </div>
                        <div class="bg-[#131521] p-4 rounded-2xl border border-[#262a40]">
                            <span class="text-xs text-gray-400">Avg Humidity</span>
                            <h4 class="text-2xl font-bold text-white mt-1" id="hist-humidity">--</h4>
                        </div>
                    </div>

                    <!-- Comparison Table -->
                    <div class="bg-[#131521] p-6 rounded-2xl border border-[#262a40]">
                        <h3 class="text-sm font-semibold text-white mb-4"><i class="fa-solid fa-scale-balanced mr-2 text-blue-400"></i> Gujarat District Climate Comparison</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm text-gray-300">
                                <thead class="border-b border-[#262a40] text-gray-400 text-xs">
                                    <tr>
                                        <th class="pb-3">District</th>
                                        <th class="pb-3">Avg Temp</th>
                                        <th class="pb-3">Rainfall</th>
                                        <th class="pb-3">Humidity</th>
                                    </tr>
                                </thead>
                                <tbody id="comparison-tbody">
                                    <tr><td colspan="4" class="py-3 text-center text-gray-500">Loading multi-district climate benchmarks...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 4: FEATURE 15 - GUJARAT METEOROLOGIST AI ASSISTANT VIEW -->
        <div id="tab-ai" class="tab-content hidden">
            <div class="w-full max-w-4xl mx-auto bg-[#1b1f30] p-6 md:p-8 rounded-3xl border border-[#262a40] shadow-xl flex flex-col h-[650px]">
                <div class="flex items-center gap-3 pb-4 border-b border-[#262a40]">
                    <div class="w-10 h-10 rounded-full bg-blue-500/20 text-blue-400 flex items-center justify-center font-bold text-lg border border-blue-500/30">
                        <i class="fa-solid fa-robot"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-white">Ask Gujarat Meteorologist AI</h2>
                        <p class="text-xs text-blue-400">Multilingual climate intelligence supporting English, Gujarati & Hinglish</p>
                    </div>
                </div>

                <!-- Chat Messages Scroll Area -->
                <div id="chat-messages" class="flex-1 overflow-y-auto py-4 space-y-4 pr-2 scrollbar-hide">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full bg-blue-500 text-white flex items-center justify-center shrink-0 text-xs font-bold">AI</div>
                        <div class="bg-[#131521] border border-[#262a40] p-4 rounded-2xl text-sm text-gray-200 max-w-lg leading-relaxed">
                            Namaste! I am your Gujarat Meteorologist Assistant. Ask me about weather conditions, district comparisons, rain forecasts, or alerts across Gujarat (e.g., <i>"Mahesana ma kale varsad padse?"</i> or <i>"Ahmedabad weather"</i>).
                        </div>
                    </div>
                </div>

                <!-- Quick Prompts Chips -->
                <div class="py-2 flex gap-2 overflow-x-auto scrollbar-hide shrink-0">
                    <button onclick="sendQuickPrompt('Ahmedabad weather')" class="bg-[#131521] hover:bg-[#262a40] text-xs text-gray-300 border border-[#262a40] px-3 py-1.5 rounded-full whitespace-nowrap transition">Ahmedabad weather</button>
                    <button onclick="sendQuickPrompt('Rain today in Surat')" class="bg-[#131521] hover:bg-[#262a40] text-xs text-gray-300 border border-[#262a40] px-3 py-1.5 rounded-full whitespace-nowrap transition">Rain today in Surat</button>
                    <button onclick="sendQuickPrompt('Mahesana ma kale varsad padse?')" class="bg-[#131521] hover:bg-[#262a40] text-xs text-gray-300 border border-[#262a40] px-3 py-1.5 rounded-full whitespace-nowrap transition">Mahesana ma kale varsad padse?</button>
                    <button onclick="sendQuickPrompt('Gujarat weather alerts')" class="bg-[#131521] hover:bg-[#262a40] text-xs text-gray-300 border border-[#262a40] px-3 py-1.5 rounded-full whitespace-nowrap transition">Gujarat weather alerts</button>
                </div>

                <!-- Input Box Bar -->
                <div class="pt-3 border-t border-[#262a40] flex gap-2">
                    <input type="text" id="ai-input" placeholder="Ask about Gujarat weather (English, ગુજરાતી, Hinglish)..." 
                        class="flex-1 px-4 py-3 bg-[#131521] text-white border border-[#262a40] rounded-xl focus:outline-none focus:border-blue-500 text-sm"
                        onkeypress="if(event.key === 'Enter') sendAiMessage()">
                    <button onclick="sendAiMessage()" class="bg-blue-500 hover:bg-blue-600 text-white px-5 py-3 rounded-xl font-medium transition shadow-lg">
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>

    </main>

    <!-- JavaScript Application Core Logic -->
    <script>
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
            // Load default startup city
            fetchWeatherData('/api/weather?city=Mahesana');
        });

        // Tab Navigation Controller
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
            setTimeout(() => {
                const alertsSec = document.getElementById('alerts-section');
                if(alertsSec) alertsSec.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 100);
        });

        // Core API Fetching
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
            liveDashboardCache[current.name] = data;

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
            
            // Hourly & Rain
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

            // 5-Day Forecast
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

            // UV Index
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
                    <div class="mt-2 text-sm text-gray-300">Peak UV: <span class="font-medium text-white">11:00 AM – 3:00 PM</span></div>
                    <p class="text-xs text-gray-400 mt-2 leading-relaxed border-t border-[#262a40] pt-2">${msg}</p>`;
            } else {
                uvContainer.innerHTML = '<p class="text-sm text-gray-400">UV data unavailable</p>';
            }

            // Temperature Graph
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
            }

            // Sunrise & Sunset
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
            }

            // Weather Alerts Logic
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

        // FEATURE 13: Interactive Gujarat Weather Map Implementation
        async function initInteractiveMap() {
            const grid = document.getElementById('interactive-map-grid');
            grid.innerHTML = '<p class="text-sm text-gray-400 col-span-full text-center py-6">Initializing Gujarat weather nodes...</p>';
            
            let html = '';
            // Load a representative sample of primary economic/geographical zones for efficient rendering
            const sampleDistricts = ["Ahmedabad", "Mahesana", "Rajkot", "Surat", "Vadodara", "Gandhinagar", "Kutch", "Jamnagar", "Bhavnagar", "Junagadh", "Anand", "Navsari"];
            
            for(let d of sampleDistricts) {
                try {
                    const res = await fetch(`/api/weather?city=${d}`);
                    const json = await res.json();
                    const cur = json.current ? json.current : json;
                    const temp = Math.round(cur.main.temp);
                    const cond = cur.weather[0].description;
                    const hum = cur.main.humidity;
                    const wind = cur.wind.speed;
                    
                    // Temp badge coloring legend scale
                    let badgeColor = "bg-green-400/20 text-green-400 border-green-400/30";
                    if(temp < 20) badgeColor = "bg-blue-400/20 text-blue-400 border-blue-400/30";
                    else if(temp <= 25) badgeColor = "bg-teal-400/20 text-teal-400 border-teal-400/30";
                    else if(temp <= 30) badgeColor = "bg-green-400/20 text-green-400 border-green-400/30";
                    else if(temp <= 35) badgeColor = "bg-orange-400/20 text-orange-400 border-orange-400/30";
                    else badgeColor = "bg-red-500/20 text-red-400 border-red-500/30";

                    html += `
                        <div class="bg-[#131521] p-5 rounded-2xl border border-[#262a40] flex flex-col justify-between hover:border-blue-500/50 transition">
                            <div>
                                <div class="flex justify-between items-start mb-2">
                                    <h4 class="font-bold text-white text-base">${d}</h4>
                                    <span class="px-2.5 py-1 rounded-lg text-xs font-bold border ${badgeColor}">${temp}°C</span>
                                </div>
                                <p class="text-xs text-gray-400 capitalize mb-3">${cond}</p>
                                <div class="text-xs text-gray-400 space-y-1">
                                    <div>Humidity: <span class="text-white">${hum}%</span></div>
                                    <div>Wind: <span class="text-white">${wind} km/h</span></div>
                                </div>
                            </div>
                            <button onclick="switchTab('dashboard'); fetchWeatherData('/api/weather?city=${d}')" 
                                class="mt-4 w-full bg-[#1b1f30] hover:bg-blue-500 text-blue-400 hover:text-white border border-[#262a40] py-2 rounded-xl text-xs font-medium transition">
                                View Details
                            </button>
                        </div>
                    `;
                } catch(e) {}
            }
            grid.innerHTML = html;
        }

        // FEATURE 14: Historical Weather Analytics Fetcher
        function populateHistoricalDropdowns() {
            const select = document.getElementById('hist-city');
            select.innerHTML = '';
            gujaratDistricts.forEach(d => {
                select.innerHTML += `<option value="${d}">${d}</option>`;
            });
        }

        async function fetchHistoricalData() {
            const city = document.getElementById('hist-city').value;
            const from = document.getElementById('hist-from').value;
            const to = document.getElementById('hist-to').value;

            try {
                const res = await fetch(`/api/historical?city=${encodeURIComponent(city)}&from=${from}&to=${to}`);
                const data = await res.json();
                
                if(res.ok) {
                    document.getElementById('historical-results').classList.remove('hidden');
                    document.getElementById('hist-avg').innerText = data.avg_temp + '°C';
                    document.getElementById('hist-max').innerText = data.max_temp + '°C';
                    document.getElementById('hist-min').innerText = data.min_temp + '°C';
                    document.getElementById('hist-rain').innerText = data.total_rainfall + ' mm';
                    document.getElementById('hist-humidity').innerText = data.avg_humidity + '%';

                    // Comparison table mock benchmarks for Gujarat districts
                    const tbody = document.getElementById('comparison-tbody');
                    tbody.innerHTML = `
                        <tr class="border-b border-[#262a40]/50"><td class="py-2.5 font-medium text-white">${city} (Selected)</td><td class="py-2.5">${data.avg_temp}°C</td><td class="py-2.5">${data.total_rainfall} mm</td><td class="py-2.5">${data.avg_humidity}%</td></tr>
                        <tr class="border-b border-[#262a40]/50"><td class="py-2.5 font-medium text-white">Ahmedabad</td><td class="py-2.5">31.8°C</td><td class="py-2.5">140 mm</td><td class="py-2.5">65%</td></tr>
                        <tr class="border-b border-[#262a40]/50"><td class="py-2.5 font-medium text-white">Mahesana</td><td class="py-2.5">30.4°C</td><td class="py-2.5">115 mm</td><td class="py-2.5">62%</td></tr>
                        <tr class="border-b border-[#262a40]/50"><td class="py-2.5 font-medium text-white">Rajkot</td><td class="py-2.5">31.0°C</td><td class="py-2.5">130 mm</td><td class="py-2.5">64%</td></tr>
                        <tr><td class="py-2.5 font-medium text-white">Surat</td><td class="py-2.5">29.5°C</td><td class="py-2.5">195 mm</td><td class="py-2.5">78%</td></tr>
                    `;
                }
            } catch(e) { console.error(e); }
        }

        // FEATURE 15: Gujarat Meteorologist AI Assistant Logic
        // SAFE WEATHER FETCHING
        async function fetchWeatherData(queryUrl) {
            showAlert(''); 
            try {
                const response = await fetch(queryUrl);
                
                // Check if response is JSON to prevent JS parsing crash
                const contentType = response.headers.get("content-type");
                if (contentType && contentType.indexOf("application/json") !== -1) {
                    const data = await response.json();
                    if (!response.ok) { 
                        showAlert(data.error || "Location not found!"); 
                        return; 
                    }
                    updateDashboardUI(data);
                } else {
                    showAlert("Server error. Please check your backend.");
                }
            } catch (error) {
                console.error(error);
                showAlert("Network error. Please try again.");
            }
        }

        // SAFE AI MESSAGE SENDING
        async function sendAiMessage() {
            const input = document.getElementById('ai-input');
            const prompt = input.value.trim();
            if(!prompt) return;

            const chatContainer = document.getElementById('chat-messages');
            
            chatContainer.innerHTML += `
                <div class="flex items-start gap-3 justify-end">
                    <div class="bg-blue-600 text-white p-4 rounded-2xl text-sm max-w-lg leading-relaxed">${prompt}</div>
                    <div class="w-8 h-8 rounded-full bg-gray-700 text-white flex items-center justify-center shrink-0 text-xs font-bold">You</div>
                </div>
            `;
            input.value = '';
            chatContainer.scrollTop = chatContainer.scrollHeight;

            const loadingId = 'ai-load-' + Date.now();
            chatContainer.innerHTML += `
                <div id="${loadingId}" class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-full bg-blue-500 text-white flex items-center justify-center shrink-0 text-xs font-bold">AI</div>
                    <div class="bg-[#131521] border border-[#262a40] p-4 rounded-2xl text-sm text-gray-400 italic">Consulting Gujarat climate models...</div>
                </div>
            `;
            chatContainer.scrollTop = chatContainer.scrollHeight;

            try {
                const response = await fetch('/api/meteorologist', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}' // Very important for Laravel security
                    },
                    body: JSON.stringify({ prompt: prompt, context: liveDashboardCache[currentActiveCity] || {} })
                });
                
                const resJson = await response.json();
                document.getElementById(loadingId).remove();

                // Check if reply exists to prevent "undefined"
                const replyText = resJson.reply || "I am currently unable to generate a response. Please try again later.";

                chatContainer.innerHTML += `
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full bg-blue-500 text-white flex items-center justify-center shrink-0 text-xs font-bold">AI</div>
                        <div class="bg-[#131521] border border-[#262a40] p-4 rounded-2xl text-sm text-gray-200 max-w-lg leading-relaxed">${replyText}</div>
                    </div>
                `;
                chatContainer.scrollTop = chatContainer.scrollHeight;

            } catch(e) {
                document.getElementById(loadingId).remove();
                chatContainer.innerHTML += `
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full bg-blue-500 text-white flex items-center justify-center shrink-0 text-xs font-bold">AI</div>
                        <div class="bg-[#131521] border border-red-500/30 p-4 rounded-2xl text-sm text-red-400">Connection error. Please try again.</div>
                    </div>
                `;
            }
        }
        // Favorites & Explorer Event bindings
        document.getElementById('favorite-btn').addEventListener('click', () => {
            if(!currentActiveCity) return;
            let favs = getFavorites();
            if (favs.includes(currentActiveCity)) favs = favs.filter(c => c !== currentActiveCity);
            else favs.push(currentActiveCity);
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
            if (favs.length === 0) { msg.style.display = 'block'; return; }
            msg.style.display = 'none';
            container.innerHTML = '';
            for (let city of favs) {
                try {
                    const res = await fetch(`/api/weather?city=${encodeURIComponent(city)}`);
                    const data = await res.json();
                    const current = data.current ? data.current : data;
                    container.innerHTML += `
                        <div onclick="switchTab('dashboard'); fetchWeatherData('/api/weather?city=${encodeURIComponent(city)}')" 
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
            if (city) { switchTab('dashboard'); fetchWeatherData(`/api/weather?city=${encodeURIComponent(city)}`); }
        });

        document.getElementById('location-btn').addEventListener('click', () => {
            if (navigator.geolocation) {
                showAlert('Detecting location...', false);
                navigator.geolocation.getCurrentPosition(
                    (position) => { switchTab('dashboard'); fetchWeatherData(`/api/weather?lat=${position.coords.latitude}&lon=${position.coords.longitude}`); },
                    (error) => { showAlert("Location permission was denied."); }
                );
            } else showAlert("Geolocation not supported.");
        });

        function renderDistrictsGrid(districts) {
            const grid = document.getElementById('districts-grid');
            grid.innerHTML = '';
            districts.forEach(d => {
                grid.innerHTML += `<button onclick="switchTab('dashboard'); fetchWeatherData('/api/weather?city=${d}')" class="district-btn text-left px-3 py-2 bg-[#262a40] hover:bg-blue-500 hover:text-white text-gray-300 text-sm rounded-lg transition border border-[#32364a]">${d}</button>`;
            });
        }

        document.getElementById('district-filter').addEventListener('input', (e) => {
            renderDistrictsGrid(gujaratDistricts.filter(d => d.toLowerCase().includes(e.target.value.toLowerCase())));
        });

        function showAlert(message, isError = true) {
            const box = document.getElementById('alert-box');
            if (!message) { box.classList.add('hidden'); return; }
            box.innerText = message;
            box.classList.replace(isError ? 'text-blue-400' : 'text-orange-400', isError ? 'text-orange-400' : 'text-blue-400');
            box.classList.remove('hidden');
            if(isError) setTimeout(() => box.classList.add('hidden'), 5000);
        }
        async function sendQuickPrompt(query) {
            const inputField = document.getElementById('ai-input');
            if (inputField) {
                inputField.value = query;
                await sendAiMessage();
            }
        }
    </script>
</body>
</html>