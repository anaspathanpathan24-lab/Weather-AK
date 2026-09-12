<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Gujarat Weather Intelligence Platform - AK</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
        /* Prevent combined UV + Heat Stress content from overflowing its grid item. */
        #uv-container, #heat-stress-container { min-width: 0; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            <div class="w-full max-w-7xl mx-auto flex flex-col lg:flex-row gap-6 mb-8 items-start">
<!-- Weather Card -->
<div class="w-full lg:w-[400px] shrink-0 lg:sticky lg:top-6 lg:self-start">

    <div class="bg-[#1b1f30] p-8 rounded-3xl shadow-xl w-full border border-[#262a40]">

        <!-- Search -->
        <div class="flex space-x-2 mb-6">

            <input
                type="text"
                id="city"
                placeholder="Search city (e.g., Mahesana)..."
                class="w-full px-4 py-3 bg-[#131521] text-white border border-[#262a40] rounded-xl focus:outline-none focus:border-blue-500 transition"
            >

            <button
                id="search-btn"
                type="button"
                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-3 rounded-xl font-medium transition-colors shadow-lg"
            >
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>

            <button
                id="location-btn"
                type="button"
                title="Use My Location"
                class="bg-[#262a40] hover:bg-[#32364a] text-blue-400 px-4 py-3 rounded-xl font-medium transition-colors shadow-lg"
            >
                <i class="fa-solid fa-location-crosshairs"></i>
            </button>

        </div>


        <!-- Current Weather -->
        <div class="text-center mt-6">

            <!-- City + Favorite -->
            <div class="flex items-center justify-center gap-3">

                <h2
                    class="text-2xl font-bold text-white"
                    id="city-name"
                >
                    City Name
                </h2>

                <button
                    id="favorite-btn"
                    type="button"
                    class="text-gray-500 hover:text-yellow-400 transition text-xl hidden"
                >
                    <i
                        class="fa-regular fa-star"
                        id="favorite-icon"
                    ></i>
                </button>

            </div>


            <!-- Description -->
            <p
                class="text-blue-400 font-medium capitalize mt-1"
                id="weather-desc"
            >
                Clear Sky
            </p>


            <!-- Temperature -->
            <div class="my-6 flex flex-col items-center">

                <img
                    id="weather-icon"
                    src="https://openweathermap.org/img/wn/02d@4x.png"
                    alt="weather icon"
                    class="w-28 h-28 sm:w-32 sm:h-32 -mb-2 drop-shadow-lg"
                >

                <span
                    class="text-6xl sm:text-7xl font-bold text-white tracking-tighter"
                    id="temp"
                >
                    25°
                </span>

                <p
                    class="text-gray-400 mt-2 text-sm font-medium hidden"
                    id="feels-like-container"
                >
                    Feels like
                    <span id="feels-like-temp"></span>°C
                </p>

            </div>


            <!-- Weather Metrics -->
            <div
                class="grid grid-cols-3 gap-2 sm:gap-4 text-gray-400 mt-6 border-t border-[#262a40] pt-6 px-1 sm:px-4"
            >

                <!-- Humidity -->
                <div class="flex flex-col items-center">

                    <i class="fa-solid fa-droplet text-blue-500 mb-2 text-xl"></i>

                    <p class="text-xs sm:text-sm font-medium">
                        Humidity
                    </p>

                    <p
                        class="font-bold text-white mt-1 text-sm sm:text-base"
                        id="humidity"
                    >
                        60%
                    </p>

                </div>


                <!-- Wind -->
                <div class="flex flex-col items-center">

                    <i class="fa-solid fa-wind text-gray-400 mb-2 text-xl"></i>

                    <p class="text-xs sm:text-sm font-medium">
                        Wind
                    </p>

                    <p
                        class="font-bold text-white mt-1 text-sm sm:text-base"
                        id="wind"
                    >
                        5 km/h
                    </p>

                </div>


                <!-- Pressure -->
                <div class="flex flex-col items-center">

                    <i class="fa-solid fa-gauge text-gray-400 mb-2 text-xl"></i>

                    <p class="text-xs sm:text-sm font-medium">
                        Pressure
                    </p>

                    <p
                        class="font-bold text-white mt-1 text-sm sm:text-base"
                        id="pressure"
                    >
                        1013 hPa
                    </p>

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
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
                        <div class="bg-[#1b1f30] p-6 rounded-3xl border border-[#262a40] shadow-xl">
                            <h3 class="text-gray-400 font-semibold mb-5 text-sm uppercase tracking-wider"><i class="fa-regular fa-calendar-days mr-2"></i> 7-Day Forecast</h3>
                            <div id="daily-container" class="flex flex-col space-y-1"></div>
                        </div>
                        <div class="flex flex-col gap-6">
                            <div class="bg-[#1b1f30] p-6 rounded-3xl border border-[#262a40] shadow-xl h-fit self-start">
                                <h3 class="text-gray-400 font-semibold mb-5 text-sm uppercase tracking-wider"><i class="fa-solid fa-umbrella mr-2"></i> Rain Probability</h3>
                                <div id="rain-container" class="flex overflow-x-auto scrollbar-hide gap-6 pb-2 snap-x"></div>
                            </div>
                            <!-- UV Index + Heat Stress -->
<div class="bg-[#1b1f30] p-6 rounded-3xl border border-[#262a40] shadow-xl h-fit self-start">

    <!-- UV Index -->
    <div>
        <h3 class="text-gray-400 font-semibold mb-5 text-sm uppercase tracking-wider">
            <i class="fa-regular fa-sun mr-2"></i> UV Index
        </h3>

        <div id="uv-container" class="flex flex-col justify-center pb-4 h-auto">
            <p class="text-sm text-gray-400">
                Loading UV data...
            </p>
        </div>
    </div>

    <!-- Divider -->
    <div class="border-t border-[#262a40] my-5"></div>

    <!-- Heat Stress -->
    <div>
        <h3 class="text-gray-400 font-semibold mb-4 text-sm uppercase tracking-wider">
            <i class="fa-solid fa-temperature-high mr-2"></i> Heat Stress Index
        </h3>

        <div id="heat-stress-container">

            <div class="flex items-center gap-2 text-gray-400">
                <i class="fa-solid fa-spinner fa-spin text-blue-400"></i>
                <span class="text-sm">
                    Calculating heat stress...
                </span>
            </div>

        </div>
    </div>

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

                    <!-- 4. Sun & Moon Tracking + Air Quality + Weather Alerts -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

                        <!-- Sun & Moon Card -->
                        <div class="bg-[#1b1f30] p-6 rounded-3xl border border-[#262a40] shadow-xl lg:col-span-1 h-fit self-start">
                            <h3 class="text-gray-400 font-semibold mb-5 text-sm uppercase tracking-wider"><i class="fa-solid fa-moon text-blue-300 mr-2"></i> Sun & Moon Tracking</h3>
                            <div class="flex flex-col gap-3 text-sm" id="sun-tracking-container">
                                <!-- Dynamically populated -->
                            </div>
                        </div>

                        <!-- Air Quality Index Card -->
                        <div id="aqi-section" class="bg-[#1b1f30] p-6 rounded-3xl border border-[#262a40] shadow-xl lg:col-span-1 h-fit self-start">
                            <h3 class="text-gray-400 font-semibold mb-4 text-sm uppercase tracking-wider">
                                <i class="fa-solid fa-lungs text-blue-400 mr-2"></i> Air Quality Index
                            </h3>

                            <div id="aqi-content" class="flex flex-col gap-4">
                                <!-- AQI dynamically populated -->
                                <div id="aqi-loading" class="flex items-center justify-center gap-2 py-6 text-gray-400 text-sm">
                                    <i class="fa-solid fa-spinner fa-spin text-blue-400"></i>
                                    Loading air quality...
                                </div>
                            </div>
                        </div>

                        <!-- Weather Alerts Card -->
                        <div id="alerts-section" class="bg-[#1b1f30] p-6 rounded-3xl border border-[#262a40] shadow-xl lg:col-span-1 h-fit self-start">
                            <h3 class="text-gray-400 font-semibold mb-5 text-sm uppercase tracking-wider"><i class="fa-solid fa-bell mr-2"></i> Weather Alerts</h3>
                            <div id="alerts-container" class="flex flex-col gap-3"></div>
                        </div>
                    </div>

                </div>

            </div>
        <!-- Farmer Weather Advisory -->
<div
    id="farmer-advisory-section"
    class="bg-[#1b1f30] p-6 rounded-3xl border border-[#262a40] shadow-xl self-start"
>
    <h3 class="text-gray-400 font-semibold mb-5 text-sm uppercase tracking-wider">
        <i class="fa-solid fa-seedling text-green-400 mr-2"></i>
        Farmer Weather Advisory
    </h3>

    <!-- Selectors -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-5">

        <!-- District -->
        <div>
            <label class="block text-[11px] text-gray-500 mb-1">
                District / City
            </label>

            <select
                id="farmer-city"
                class="w-full bg-[#131521] text-white border border-[#262a40] px-3 py-2.5 rounded-xl text-sm focus:outline-none focus:border-blue-500"
            >
                <option value="">Select District</option>
            </select>
        </div>

        <!-- Crop -->
        <div>
            <label class="block text-[11px] text-gray-500 mb-1">
                Crop
            </label>

            <select
                id="farmer-crop"
                class="w-full bg-[#131521] text-white border border-[#262a40] px-3 py-2.5 rounded-xl text-sm focus:outline-none focus:border-blue-500"
            >
                <option value="">Select Crop</option>
            </select>
        </div>

    </div>

    <!-- Advisory Status -->
    <div
        id="farmer-advisory-status"
        class="hidden mb-4"
    ></div>

    <!-- Advisory Content -->
    <div
        id="farmer-advisory-content"
        class="text-sm text-gray-300"
    >
        <div class="p-4 rounded-xl bg-[#131521] border border-[#262a40] text-gray-500">
            Select a district and crop to view weather-based guidance.
        </div>
    </div>

    <!-- Disclaimer -->
    <div class="mt-4 p-3 rounded-xl bg-[#131521] border border-[#262a40]">
        <p class="text-[10px] text-gray-500 leading-relaxed">
            <i class="fa-solid fa-circle-info text-blue-400 mr-1"></i>
            Weather-based guidance only. This is not professionally verified
            agricultural advice and should be used with local farming expertise.
        </p>
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

        <h2 class="text-2xl font-bold text-white mb-2">
            Historical Weather Analytics
        </h2>

        <p class="text-sm text-gray-400 mb-6">
            Select a Gujarat district and date range to analyze historical weather data.
        </p>

        <!-- Filters -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 bg-[#131521] p-6 rounded-2xl border border-[#262a40]">

            <!-- District -->
            <div>
                <label
                    for="hist-city"
                    class="block text-xs text-gray-400 mb-2"
                >
                    Select District
                </label>

                <select
                    id="hist-city"
                    name="hist-city"
                    autocomplete="off"
                    class="w-full bg-[#1b1f30] text-white border border-[#262a40] px-4 py-2.5 rounded-xl text-sm focus:outline-none focus:border-blue-500"
                >
                    <option value="" selected disabled>
                        Select District
                    </option>
                </select>
            </div>

            <!-- From Date -->
            <div>
                <label
                    for="hist-from"
                    class="block text-xs text-gray-400 mb-2"
                >
                    From Date
                </label>

                <input
                    type="date"
                    id="hist-from"
                    name="hist-from"
                    autocomplete="off"
                    class="w-full bg-[#1b1f30] text-white border border-[#262a40] px-4 py-2.5 rounded-xl text-sm focus:outline-none focus:border-blue-500"
                >
            </div>

            <!-- To Date -->
            <div>
                <label
                    for="hist-to"
                    class="block text-xs text-gray-400 mb-2"
                >
                    To Date
                </label>

                <input
                    type="date"
                    id="hist-to"
                    name="hist-to"
                    autocomplete="off"
                    class="w-full bg-[#1b1f30] text-white border border-[#262a40] px-4 py-2.5 rounded-xl text-sm focus:outline-none focus:border-blue-500"
                >
            </div>

            <!-- Analyze -->
            <div class="flex items-end">

                <button
                    id="historical-analyze-btn"
                    type="button"
                    onclick="fetchHistoricalData()"
                    class="w-full bg-blue-500 hover:bg-blue-600 disabled:bg-blue-500/50 disabled:cursor-not-allowed text-white font-medium py-2.5 rounded-xl transition shadow-lg text-sm"
                >
                    <i class="fa-solid fa-chart-line mr-2"></i>
                    <span id="historical-analyze-text">
                        Analyze
                    </span>
                </button>

            </div>

        </div>

        <!-- Validation / Error / Loading Message -->
        <div
            id="historical-status"
            class="hidden mb-6 p-4 rounded-xl text-sm border"
        ></div>

        <!-- Results -->
        <div
            id="historical-results"
            class="hidden flex flex-col gap-6"
        >

            <!-- Summary Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">

                <div class="bg-[#131521] p-4 rounded-2xl border border-[#262a40]">
                    <span class="text-xs text-gray-400">
                        Avg Temp
                    </span>

                    <h4
                        class="text-2xl font-bold text-white mt-1"
                        id="hist-avg"
                    >
                        --
                    </h4>
                </div>

                <div class="bg-[#131521] p-4 rounded-2xl border border-[#262a40]">

                    <span class="text-xs text-gray-400">
                        Max / Min
                    </span>

                    <h4 class="text-2xl font-bold text-white mt-1">

                        <span id="hist-max">
                            --
                        </span>

                        /

                        <span id="hist-min">
                            --
                        </span>

                    </h4>

                </div>

                <div class="bg-[#131521] p-4 rounded-2xl border border-[#262a40]">

                    <span class="text-xs text-gray-400">
                        Rainfall
                    </span>

                    <h4
                        class="text-2xl font-bold text-white mt-1"
                        id="hist-rain"
                    >
                        --
                    </h4>

                </div>

                <div class="bg-[#131521] p-4 rounded-2xl border border-[#262a40]">

                    <span class="text-xs text-gray-400">
                        Humidity
                    </span>

                    <h4
                        class="text-2xl font-bold text-white mt-1"
                        id="hist-humidity"
                    >
                        --
                    </h4>

                </div>

            </div>

            <!-- Analysis Information -->
            <div
                class="bg-[#131521] p-5 rounded-2xl border border-[#262a40]"
            >

                <div class="flex flex-wrap items-center justify-between gap-3">

                    <div>

                        <h3 class="text-white font-semibold">
                            Analysis Summary
                        </h3>

                        <p
                            id="historical-summary"
                            class="text-sm text-gray-400 mt-1"
                        >
                            --
                        </p>

                    </div>

                    <div
                        id="historical-last-updated"
                        class="text-xs text-gray-500"
                    >
                        --
                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

<!-- TAB: TRAVEL WEATHER -->
<div id="tab-travel" class="tab-content hidden">

    <div class="w-full max-w-7xl mx-auto">

        <div class="bg-[#1b1f30] p-6 md:p-8 rounded-3xl border border-[#262a40] shadow-xl">

            <!-- Header -->
            <div class="mb-6">

                <h2 class="text-2xl font-bold text-white">
                    <i class="fa-solid fa-route text-blue-400 mr-2"></i>
                    Travel Weather
                </h2>

                <p class="text-sm text-gray-400 mt-1">
                    Check route conditions and weather risk across your Gujarat journey.
                </p>

            </div>

            <!-- Inputs -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <!-- Starting Location -->
                <div>

                    <label
                        for="travel-start"
                        class="block text-xs text-gray-400 mb-2"
                    >
                        Starting Location
                    </label>

                    <input
                        type="text"
                        id="travel-start"
                        placeholder="e.g. Ahmedabad"
                        autocomplete="off"
                        class="w-full px-4 py-3 bg-[#131521] text-white border border-[#262a40] rounded-xl focus:outline-none focus:border-blue-500"
                    >

                </div>

                <!-- Destination -->
                <div>

                    <label
                        for="travel-destination"
                        class="block text-xs text-gray-400 mb-2"
                    >
                        Destination
                    </label>

                    <input
                        type="text"
                        id="travel-destination"
                        placeholder="e.g. Rajkot"
                        autocomplete="off"
                        class="w-full px-4 py-3 bg-[#131521] text-white border border-[#262a40] rounded-xl focus:outline-none focus:border-blue-500"
                    >

                </div>

            </div>

            <!-- Analyze Button -->
            <button
                id="travel-analyze-btn"
                type="button"
                onclick="analyzeTravelWeather()"
                class="w-full md:w-auto mt-4 bg-blue-500 hover:bg-blue-600 text-white font-medium px-6 py-3 rounded-xl transition shadow-lg"
            >
                <i class="fa-solid fa-route mr-2"></i>
                Check Travel Weather
            </button>

            <!-- Status / Error -->
            <div
                id="travel-status"
                class="hidden mt-5 p-4 rounded-xl border text-sm"
            ></div>

            <!-- Results -->
            <div
                id="travel-results"
                class="hidden mt-6 space-y-5"
            >

                <!-- Travel Status -->
                <div
                    id="travel-risk-card"
                    class="p-5 rounded-2xl border"
                >

                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">

                        <div>

                            <p class="text-xs text-gray-500 uppercase tracking-wider">
                                Travel Status
                            </p>

                            <h3
                                id="travel-risk-title"
                                class="text-2xl font-bold mt-1"
                            >
                                --
                            </h3>

                            <p
                                id="travel-risk-description"
                                class="text-sm text-gray-400 mt-2"
                            >
                                --
                            </p>

                        </div>

                        <div
                            id="travel-risk-icon"
                            class="w-14 h-14 rounded-2xl flex items-center justify-center"
                        >
                            <i class="fa-solid fa-car text-2xl"></i>
                        </div>

                    </div>

                </div>

                <!-- Route Summary -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">

                    <div class="bg-[#131521] border border-[#262a40] rounded-2xl p-4">
                        <p class="text-xs text-gray-500">Distance</p>

                        <p
                            id="travel-distance"
                            class="text-lg font-bold text-white mt-1"
                        >
                            --
                        </p>
                    </div>

                    <div class="bg-[#131521] border border-[#262a40] rounded-2xl p-4">
                        <p class="text-xs text-gray-500">Est. Travel Time</p>

                        <p
                            id="travel-duration"
                            class="text-lg font-bold text-white mt-1"
                        >
                            --
                        </p>
                    </div>

                    <div class="bg-[#131521] border border-[#262a40] rounded-2xl p-4">
                        <p class="text-xs text-gray-500">Rain Risk</p>

                        <p
                            id="travel-rain"
                            class="text-lg font-bold mt-1"
                        >
                            --
                        </p>
                    </div>

                    <div class="bg-[#131521] border border-[#262a40] rounded-2xl p-4">
                        <p class="text-xs text-gray-500">Wind</p>

                        <p
                            id="travel-wind"
                            class="text-lg font-bold text-white mt-1"
                        >
                            --
                        </p>
                    </div>

                </div>

                <!-- Weather Conditions -->
                <div>

                    <h3 class="text-gray-400 font-semibold text-sm uppercase tracking-wider mb-4">
                        <i class="fa-solid fa-cloud-sun text-blue-400 mr-2"></i>
                        Weather Along Route
                    </h3>

                    <div
                        id="travel-route-weather"
                        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4"
                    ></div>

                </div>

                <!-- Departure Recommendation -->
                <div
                    class="bg-[#131521] border border-[#262a40] rounded-2xl p-5"
                >

                    <div class="flex items-start gap-3">

                        <div class="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-clock text-blue-400"></i>
                        </div>

                        <div>

                            <p class="text-xs text-gray-500 uppercase tracking-wider">
                                Recommended Departure
                            </p>

                            <h4
                                id="travel-departure"
                                class="text-xl font-bold text-white mt-1"
                            >
                                --
                            </h4>

                            <p
                                id="travel-departure-reason"
                                class="text-xs text-gray-400 mt-1"
                            >
                                --
                            </p>

                        </div>

                    </div>

                </div>

                <!-- Attribution -->
                <p class="text-[10px] text-gray-500">
                    Route data from OpenStreetMap-based OSRM routing.
                    Weather values are based on available live/forecast API data;
                    conditions may change during travel.
                </p>

            </div>

        </div>

    </div>

</div>

<!-- TAB: WEATHER ANALYTICS -->
<div id="tab-weather-analytics" class="tab-content hidden">

    <div class="w-full max-w-7xl mx-auto">

        <div class="bg-[#1b1f30] p-6 md:p-8 rounded-3xl border border-[#262a40] shadow-xl">

            <!-- Header -->
            <div class="mb-6">

                <h2 class="text-2xl font-bold text-white">
                    <i class="fa-solid fa-chart-line text-blue-400 mr-2"></i>
                    Weather Analytics
                </h2>

                <p class="text-sm text-gray-400 mt-1">
                    Analyze weather trends and compare periods using available weather data.
                </p>

            </div>

            <!-- Filters -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 bg-[#131521] p-5 rounded-2xl border border-[#262a40]">

                <!-- Location -->
                <div>

                    <label
                        for="analytics-location"
                        class="block text-xs text-gray-400 mb-2"
                    >
                        Location
                    </label>

                    <select
                        id="analytics-location"
                        class="w-full bg-[#1b1f30] text-white border border-[#262a40] px-4 py-3 rounded-xl text-sm focus:outline-none focus:border-blue-500"
                    >
                        <option value="" selected disabled>
                            Select Gujarat Location
                        </option>
                    </select>

                </div>

                <!-- Period -->
                <div>

                    <label
                        for="analytics-period"
                        class="block text-xs text-gray-400 mb-2"
                    >
                        Date Range
                    </label>

                    <select
                        id="analytics-period"
                        class="w-full bg-[#1b1f30] text-white border border-[#262a40] px-4 py-3 rounded-xl text-sm focus:outline-none focus:border-blue-500"
                    >
                        <option value="7">Last 7 Days</option>
                        <option value="30" selected>Last 30 Days</option>
                        <option value="90">Last 3 Months</option>
                        <option value="365">Last 1 Year</option>
                    </select>

                </div>

                <!-- Analyze -->
                <div class="flex items-end">

                    <button
                        id="analytics-load-btn"
                        type="button"
                        onclick="loadWeatherAnalytics()"
                        class="w-full bg-blue-500 hover:bg-blue-600 disabled:bg-blue-500/50 disabled:cursor-not-allowed text-white font-medium py-3 rounded-xl transition shadow-lg text-sm"
                    >
                        <i class="fa-solid fa-chart-simple mr-2"></i>
                        Analyze Weather
                    </button>

                </div>

            </div>

            <!-- Status -->
            <div
                id="analytics-status"
                class="hidden mb-6 p-4 rounded-xl text-sm border"
            ></div>

            <!-- Results -->
            <div
                id="analytics-results"
                class="hidden space-y-6"
            >

                <!-- Summary Cards -->
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">

                    <div class="bg-[#131521] border border-[#262a40] rounded-2xl p-4">
                        <p class="text-xs text-gray-500">
                            Avg Temperature
                        </p>
                        <p id="analytics-avg-temp"
                           class="text-lg font-bold text-white mt-1">
                            --
                        </p>
                        <p id="analytics-avg-temp-change"
                           class="text-[11px] mt-1 text-gray-500">
                            --
                        </p>
                    </div>

                    <div class="bg-[#131521] border border-[#262a40] rounded-2xl p-4">
                        <p class="text-xs text-gray-500">
                            Maximum
                        </p>
                        <p id="analytics-max-temp"
                           class="text-lg font-bold text-white mt-1">
                            --
                        </p>
                        <p id="analytics-max-temp-change"
                           class="text-[11px] mt-1 text-gray-500">
                            --
                        </p>
                    </div>

                    <div class="bg-[#131521] border border-[#262a40] rounded-2xl p-4">
                        <p class="text-xs text-gray-500">
                            Minimum
                        </p>
                        <p id="analytics-min-temp"
                           class="text-lg font-bold text-white mt-1">
                            --
                        </p>
                        <p id="analytics-min-temp-change"
                           class="text-[11px] mt-1 text-gray-500">
                            --
                        </p>
                    </div>

                    <div class="bg-[#131521] border border-[#262a40] rounded-2xl p-4">
                        <p class="text-xs text-gray-500">
                            Rainfall
                        </p>
                        <p id="analytics-rainfall"
                           class="text-lg font-bold text-white mt-1">
                            --
                        </p>
                        <p id="analytics-rainfall-change"
                           class="text-[11px] mt-1 text-gray-500">
                            --
                        </p>
                    </div>

                    <div class="bg-[#131521] border border-[#262a40] rounded-2xl p-4">
                        <p class="text-xs text-gray-500">
                            Humidity
                        </p>
                        <p id="analytics-humidity"
                           class="text-lg font-bold text-white mt-1">
                            --
                        </p>
                        <p id="analytics-humidity-change"
                           class="text-[11px] mt-1 text-gray-500">
                            --
                        </p>
                    </div>

                    <div class="bg-[#131521] border border-[#262a40] rounded-2xl p-4">
                        <p class="text-xs text-gray-500">
                            Wind Speed
                        </p>
                        <p id="analytics-wind"
                           class="text-lg font-bold text-white mt-1">
                            --
                        </p>
                        <p id="analytics-wind-change"
                           class="text-[11px] mt-1 text-gray-500">
                            --
                        </p>
                    </div>

                </div>

                <!-- Temperature Chart -->
                <div class="bg-[#131521] border border-[#262a40] rounded-2xl p-5">

                    <div class="mb-4">

                        <h3 class="text-white font-semibold">
                            <i class="fa-solid fa-temperature-half text-blue-400 mr-2"></i>
                            Temperature Trend
                        </h3>

                        <p class="text-xs text-gray-500 mt-1">
                            Average, maximum and minimum temperature
                        </p>

                    </div>

                    <div class="relative h-[300px]">
                        <canvas id="analytics-temperature-chart"></canvas>
                    </div>

                </div>

                <!-- Rain / Humidity Chart -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                    <div class="bg-[#131521] border border-[#262a40] rounded-2xl p-5">

                        <h3 class="text-white font-semibold mb-1">
                            <i class="fa-solid fa-cloud-rain text-blue-400 mr-2"></i>
                            Rainfall
                        </h3>

                        <p class="text-xs text-gray-500 mb-4">
                            Daily rainfall trend
                        </p>

                        <div class="relative h-[280px]">
                            <canvas id="analytics-rain-chart"></canvas>
                        </div>

                    </div>

                    <div class="bg-[#131521] border border-[#262a40] rounded-2xl p-5">

                        <h3 class="text-white font-semibold mb-1">
                            <i class="fa-solid fa-droplet text-blue-400 mr-2"></i>
                            Humidity & Wind
                        </h3>

                        <p class="text-xs text-gray-500 mb-4">
                            Daily average conditions
                        </p>

                        <div class="relative h-[280px]">
                            <canvas id="analytics-humidity-wind-chart"></canvas>
                        </div>

                    </div>

                </div>

                <!-- Comparison -->
                <div
                    class="bg-[#131521] border border-[#262a40] rounded-2xl p-5"
                >

                    <h3 class="text-white font-semibold mb-4">
                        <i class="fa-solid fa-code-compare text-blue-400 mr-2"></i>
                        Previous Period Comparison
                    </h3>

                    <div
                        id="analytics-comparison"
                        class="text-sm text-gray-400"
                    >
                        Comparison data will appear when previous-period data is available.
                    </div>

                </div>

                <!-- Missing data note -->
                <div
                    id="analytics-data-note"
                    class="hidden text-xs text-gray-500"
                ></div>

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
    <script src="{{ asset('js/weather-app.js') }}?v={{ filemtime(public_path('js/weather-app.js')) }}"></script>

    <script>
        // Keep Historical Analysis filters empty on a fresh page load.
        // This prevents the browser from restoring previous form values.
        window.addEventListener('pageshow', function () {
            const histCity = document.getElementById('hist-city');
            const histFrom = document.getElementById('hist-from');
            const histTo = document.getElementById('hist-to');

            if (histCity) histCity.selectedIndex = 0;
            if (histFrom) histFrom.value = '';
            if (histTo) histTo.value = '';
        });
    </script>
</body>
</html>