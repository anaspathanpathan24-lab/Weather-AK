<aside id="mobile-sidebar" class="w-72 bg-[#1b1f30] text-gray-400 flex-col h-full border-r border-[#262a40] shrink-0 fixed inset-y-0 left-0 z-50 -translate-x-full transition-transform duration-300 ease-in-out flex lg:static lg:translate-x-0 lg:flex">
    <nav class="flex-1 overflow-y-auto py-6 px-4 space-y-2 scrollbar-hide">
        <a href="#" onclick="switchTab('dashboard'); toggleMobileSidebar(true)" class="nav-link flex items-center gap-3 px-4 py-3 bg-blue-500 text-white rounded-xl transition shadow-lg shadow-blue-500/30" id="link-dashboard">
            <i class="fa-solid fa-house w-5 text-center"></i><span class="font-medium">Home</span>
        </a>
        <a href="#" onclick="switchTab('map'); toggleMobileSidebar(true)" class="nav-link flex items-center gap-3 px-4 py-3 hover:bg-[#262a40] hover:text-white rounded-xl transition" id="link-map">
            <i class="fa-solid fa-map w-5 text-center"></i><span>Gujarat Weather Map</span>
        </a>
        <a href="#" onclick="switchTab('historical'); toggleMobileSidebar(true)" class="nav-link flex items-center gap-3 px-4 py-3 hover:bg-[#262a40] hover:text-white rounded-xl transition" id="link-historical">
            <i class="fa-solid fa-chart-line w-5 text-center"></i><span>Historical Weather</span>
            <span class="ml-auto bg-[#32364a] text-white text-[10px] px-2 py-0.5 rounded-full">New</span>
        </a>
        <a
    href="#alerts-section"
    onclick="openWeatherAlerts(event)"
    class="nav-link flex items-center gap-3 px-4 py-3 hover:bg-[#262a40] hover:text-white rounded-xl transition"
    id="sidebar-alerts-btn"
>
    <i class="fa-solid fa-triangle-exclamation w-5 text-center"></i>

    <span>Weather alerts</span>

    <span
        id="sidebar-alerts-badge"
        class="ml-auto bg-blue-500 text-white text-[10px] px-2 py-0.5 rounded-full hidden"
    >
        0
    </span>
        <a href="#"
   id="link-travel"
   onclick="switchTab('travel'); toggleMobileSidebar(true); return false;"
   class="nav-link flex items-center gap-3 px-4 py-3 rounded-xl text-gray-400 hover:bg-[#262a40] hover:text-white transition">

    <i class="fa-solid fa-route w-5 text-center"></i>

    <span>Travel Weather</span>

</a>

<a href="#"
   onclick="switchTab('weather-analytics'); toggleMobileSidebar(true); return false;"
   class="nav-link flex items-center gap-3 px-4 py-3 rounded-xl text-gray-400 hover:bg-[#262a40] hover:text-white transition">

    <i class="fa-solid fa-chart-line w-5 text-center"></i>

    <span>Weather Analytics</span>

</a>

<a href="#"
   id="link-district-comparison"
   onclick="switchTab('district-comparison'); toggleMobileSidebar(true); return false;"
   class="nav-link flex items-center gap-3 px-4 py-3 rounded-xl text-gray-400 hover:bg-[#262a40] hover:text-white transition">

    <i class="fa-solid fa-code-compare w-5 text-center"></i>

    <span>District Comparison</span>

</a>

</a>
        <a href="#" onclick="switchTab('ai'); toggleMobileSidebar(true)" class="nav-link flex items-center gap-3 px-4 py-3 hover:bg-[#262a40] hover:text-white rounded-xl transition" id="link-ai">
            <i class="fa-solid fa-robot w-5 text-center"></i><span>Ask meteorologist</span>
        </a>
        <a href="#" class="flex items-center gap-3 px-4 py-3 hover:bg-[#262a40] hover:text-white rounded-xl transition">
            <i class="fa-solid fa-circle-info w-5 text-center"></i><span>About us</span>
        </a>
    </nav>
    <div class="p-5 space-y-6 border-t border-[#262a40]">
        <button onclick="switchTab('ai'); toggleMobileSidebar(true)" class="w-full bg-[#1b2f4f] hover:bg-blue-600 text-blue-400 hover:text-white border border-blue-900/50 py-3 rounded-xl flex items-center justify-center gap-2 font-medium transition">
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