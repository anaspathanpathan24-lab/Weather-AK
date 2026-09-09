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

    <!-- Mobile overlay backdrop -->
    <div id="mobile-sidebar-overlay" onclick="toggleMobileSidebar(true)" class="fixed inset-0 bg-black/60 z-40 hidden lg:hidden"></div>

    <!-- Include Sidebar Partial -->
    @include('partials.sidebar')

    <!-- Main Content Container -->
    <main class="flex-1 overflow-y-auto p-4 md:p-8 relative scrollbar-hide">

        <div class="w-full max-w-7xl mx-auto mb-4 lg:hidden">
            <button onclick="toggleMobileSidebar()" class="flex items-center gap-2 bg-[#1b1f30] border border-[#262a40] text-gray-200 px-4 py-2 rounded-xl">
                <i class="fa-solid fa-bars"></i> <span>Menu</span>
            </button>
        </div>

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

                            <div class="my-6 flex flex-col items-center">
                                <img id="weather-icon" src="https://openweathermap.org/img/wn/02d@4x.png" alt="weather icon" class="w-28 h-28 sm:w-32 sm:h-32 -mb-2 drop-shadow-lg">
                                <span class="text-6xl sm:text-7xl font-bold text-white tracking-tighter" id="temp">25°</span>
                                <p class="text-gray-400 mt-2 text-sm font-medium hidden" id="feels-like-container">Feels like <span id="feels-like-temp"></span>°C</p>
                            </div>

                            <div class="grid grid-cols-3 gap-2 sm:gap-4 text-gray-400 mt-6 border-t border-[#262a40] pt-6 px-1 sm:px-4">
                                <div class="flex flex-col items-center">
                                    <i class="fa-solid fa-droplet text-blue-500 mb-2 text-xl"></i>
                                    <p class="text-xs sm:text-sm font-medium">Humidity</p>
                                    <p class="font-bold text-white mt-1 text-sm sm:text-base" id="humidity">60%</p>
                                </div>
                                <div class="flex flex-col items-center">
                                    <i class="fa-solid fa-wind text-gray-400 mb-2 text-xl"></i>
                                    <p class="text-xs sm:text-sm font-medium">Wind</p>
                                    <p class="font-bold text-white mt-1 text-sm sm:text-base" id="wind">5 km/h</p>
                                </div>
                                <div class="flex flex-col items-center">
                                    <i class="fa-solid fa-gauge text-gray-400 mb-2 text-xl"></i>
                                    <p class="text-xs sm:text-sm font-medium">Pressure</p>
                                    <p class="font-bold text-white mt-1 text-sm sm:text-base" id="pressure">1013 hPa</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Extended Metrics Grid -->
                <div id="extended-features" class="flex-1 flex flex-col gap-6 hidden">
                    
                    <!-- 1. Hourly Forecast -->
                    <div class="bg-[#1b1f30] p-6 rounded-3xl border border-[#262a40] shadow-xl w-full">
                        <h3 class="text-gray-400 font-semibold mb-5 text-sm uppercase tracking-wider"><i class="fa-regular fa-clock mr-2"></i> Hourly Forecast</h3>
                        <div id="hourly-container" class="flex overflow-x-auto scrollbar-hide gap-6 pb-2 snap-x"></div>
                    </div>

                    <!-- 2. 7-Day Forecast & Rain/UV Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-[#1b1f30] p-6 rounded-3xl border border-[#262a40] shadow-xl">
                            <h3 class="text-gray-400 font-semibold mb-5 text-sm uppercase tracking-wider"><i class="fa-regular fa-calendar-days mr-2"></i> 7-Day Forecast</h3>
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

                    <!-- 3. Temperature Trend -->
                    <div class="bg-[#1b1f30] p-6 rounded-3xl border border-[#262a40] shadow-xl w-full">
                        <h3 class="text-gray-400 font-semibold mb-5 text-sm uppercase tracking-wider"><i class="fa-solid fa-chart-area mr-2"></i> Temperature Trend</h3>
                        <div id="temp-graph-container" class="w-full h-32 relative overflow-x-auto scrollbar-hide flex items-center">
                            <p class="text-sm text-gray-400">Loading graph...</p>
                        </div>
                    </div>

                    <!-- 4. Sun & Moon Tracking AND Weather Alerts -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Sun & Moon Card (1 Column width) -->
                        <div class="bg-[#1b1f30] p-6 rounded-3xl border border-[#262a40] shadow-xl lg:col-span-1">
                            <h3 class="text-gray-400 font-semibold mb-5 text-sm uppercase tracking-wider"><i class="fa-solid fa-moon text-blue-300 mr-2"></i> Sun & Moon Tracking</h3>
                            <div class="flex flex-col gap-3 text-sm" id="sun-tracking-container">
                                <!-- Dynamically populated -->
                            </div>
                        </div>
                        
                        <!-- Weather Alerts Card (2 Columns width) -->
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

        <!-- TAB 2: MAP VIEW -->
        <div id="tab-map" class="tab-content hidden">
            <div class="w-full max-w-7xl mx-auto bg-[#1b1f30] p-8 rounded-3xl border border-[#262a40] shadow-xl">
                <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                    <div>
                        <h2 class="text-2xl font-bold text-white">Gujarat Weather Map & District Grid</h2>
                        <p class="text-sm text-gray-400 mt-1">Interactive spatial overview across Gujarat districts</p>
                    </div>
                </div>
                <div id="interactive-map-grid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4"></div>
            </div>
        </div>

        <!-- TAB 3: HISTORICAL VIEW -->
        <div id="tab-historical" class="tab-content hidden">
            <div class="w-full max-w-7xl mx-auto bg-[#1b1f30] p-8 rounded-3xl border border-[#262a40] shadow-xl">
                <h2 class="text-2xl font-bold text-white mb-2">Historical Weather Analytics</h2>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 bg-[#131521] p-6 rounded-2xl border border-[#262a40]">
                    <div>
                        <label class="block text-xs text-gray-400 mb-2">Select District</label>
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
                        <button onclick="fetchHistoricalData()" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-medium py-2.5 rounded-xl transition shadow-lg text-sm">Analyze</button>
                    </div>
                </div>
                <div id="historical-results" class="hidden flex flex-col gap-6">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-[#131521] p-4 rounded-2xl border border-[#262a40]"><span class="text-xs text-gray-400">Avg Temp</span><h4 class="text-2xl font-bold text-white mt-1" id="hist-avg">--</h4></div>
                        <div class="bg-[#131521] p-4 rounded-2xl border border-[#262a40]"><span class="text-xs text-gray-400">Max / Min</span><h4 class="text-2xl font-bold text-white mt-1"><span id="hist-max">--</span> / <span id="hist-min">--</span></h4></div>
                        <div class="bg-[#131521] p-4 rounded-2xl border border-[#262a40]"><span class="text-xs text-gray-400">Rainfall</span><h4 class="text-2xl font-bold text-white mt-1" id="hist-rain">--</h4></div>
                        <div class="bg-[#131521] p-4 rounded-2xl border border-[#262a40]"><span class="text-xs text-gray-400">Humidity</span><h4 class="text-2xl font-bold text-white mt-1" id="hist-humidity">--</h4></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 4: AI ASSISTANT VIEW -->
        <div id="tab-ai" class="tab-content hidden">
            <div class="w-full max-w-4xl mx-auto bg-[#1b1f30] p-6 md:p-8 rounded-3xl border border-[#262a40] shadow-xl flex flex-col h-[650px]">
                <div class="flex items-center gap-3 pb-4 border-b border-[#262a40]">
                    <div class="w-10 h-10 rounded-full bg-blue-500/20 text-blue-400 flex items-center justify-center font-bold text-lg"><i class="fa-solid fa-robot"></i></div>
                    <div>
                        <h2 class="text-lg font-bold text-white">Ask Gujarat Meteorologist AI</h2>
                        <p class="text-xs text-blue-400">Multilingual climate intelligence</p>
                    </div>
                </div>
                <div id="chat-messages" class="flex-1 overflow-y-auto py-4 space-y-4 pr-2 scrollbar-hide">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full bg-blue-500 text-white flex items-center justify-center shrink-0 text-xs font-bold">AI</div>
                        <div class="bg-[#131521] border border-[#262a40] p-4 rounded-2xl text-sm text-gray-200">Namaste! Ask me about weather conditions across Gujarat.</div>
                    </div>
                </div>
                <div class="pt-3 border-t border-[#262a40] flex gap-2">
                    <input type="text" id="ai-input" placeholder="Ask about Gujarat weather..." class="flex-1 px-4 py-3 bg-[#131521] text-white border border-[#262a40] rounded-xl text-sm" onkeypress="if(event.key === 'Enter') sendAiMessage()">
                    <button onclick="sendAiMessage()" class="bg-blue-500 hover:bg-blue-600 text-white px-5 py-3 rounded-xl"><i class="fa-solid fa-paper-plane"></i></button>
                </div>
            </div>
        </div>

    </main>

    <!-- Forecast Details Modal -->
    <div id="forecast-modal" class="fixed inset-0 bg-black/60 z-[100] hidden flex items-center justify-center p-4">
        <div class="bg-[#1b1f30] rounded-3xl border border-[#262a40] shadow-2xl p-6 w-full max-w-sm relative">
            <div class="flex justify-between items-center mb-4 border-b border-[#262a40] pb-3">
                <h3 class="text-lg font-bold text-white" id="modal-date">Date</h3>
                <button onclick="closeForecastModal()" class="text-gray-400 hover:text-white"><i class="fa-solid fa-xmark text-xl"></i></button>
            </div>
            <div class="flex items-center gap-3 mb-6">
                <img id="modal-icon" src="" class="w-16 h-16">
                <div>
                    <p class="text-xl font-bold text-blue-400 capitalize" id="modal-desc">Condition</p>
                    <p class="text-sm text-gray-400 mt-1">High: <span id="modal-max" class="text-white"></span>° | Low: <span id="modal-min" class="text-white"></span>°</p>
                </div>
            </div>
        </div>
    </div>

    <!-- External JavaScript Link -->
    <script src="{{ asset('js/weather-app.js') }}"></script>
</body>
</html>