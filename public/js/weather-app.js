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
    initFarmerAdvisory();
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

    document.querySelectorAll('.tab-content').forEach(el => {
        el.classList.add('hidden');
    });

    document.querySelectorAll('.nav-link').forEach(el => {
        el.classList.remove(
            'bg-blue-500',
            'text-white',
            'shadow-lg',
            'shadow-blue-500/30'
        );
    });

    const targetTab =
        document.getElementById(`tab-${tabName}`);

    if (targetTab) {
        targetTab.classList.remove('hidden');
    }

    if (
        tabName !== 'map' &&
        tabName !== 'historical' &&
        tabName !== 'ai'
    ) {

        document
            .getElementById('link-dashboard')
            ?.classList.add(
                'bg-blue-500',
                'text-white',
                'shadow-lg',
                'shadow-blue-500/30'
            );

    } else {

        document
            .getElementById(`link-${tabName}`)
            ?.classList.add(
                'bg-blue-500',
                'text-white',
                'shadow-lg',
                'shadow-blue-500/30'
            );
    }

    /*
     * IMPORTANT:
     * Historical API is NOT called automatically.
     * User must select district + dates and click Analyze.
     */
}

async function fetchWeatherData(queryUrl) {
    showAlert('');

    try {
        const response = await fetch(queryUrl);
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

function updateDashboardUI(data) {
    const current = data.current ? data.current : data;

    currentActiveCity = current.name;
    liveDashboardCache[current.name] = data;

    fetchAirQuality(
    current?.coord?.lat,
    current?.coord?.lon
);

    document.getElementById('city-name').innerText = current.name;
    document.getElementById('weather-desc').innerText = current.weather[0].description;
    document.getElementById('temp').innerText = Math.round(current.main.temp) + '°';
    document.getElementById('humidity').innerText = current.main.humidity + '%';
    document.getElementById('wind').innerText = current.wind.speed + ' km/h';
    document.getElementById('pressure').innerText = (current.main.pressure ?? '--') + ' hPa';   

    renderHeatStress(
    current?.main?.temp,
    current?.main?.feels_like,
    current?.main?.humidity
);

    if (current.weather[0].icon) {
        document.getElementById('weather-icon').src =
            `https://openweathermap.org/img/wn/${current.weather[0].icon}@4x.png`;

        document.getElementById('weather-icon').alt =
            current.weather[0].description;
    }

    const feelsLikeContainer = document.getElementById('feels-like-container');

    if (current.main.feels_like) {
        document.getElementById('feels-like-temp').innerText =
            Math.round(current.main.feels_like);

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

    hourlyContainer.innerHTML = '';
    rainContainer.innerHTML = '';

    forecastList.slice(0, 6).forEach(item => {
        const date = new Date(item.dt * 1000);

        const timeStr = date.toLocaleTimeString([], {
            hour: 'numeric',
            hour12: true
        });

        const iconUrl =
            `https://openweathermap.org/img/wn/${item.weather[0].icon}.png`;

        const temp = Math.round(item.main.temp);
        const pop = Math.round((item.pop || 0) * 100);

        hourlyContainer.innerHTML += `
            <div class="flex flex-col items-center min-w-[60px] snap-center">
                <span class="text-xs text-gray-400 mb-2">${timeStr}</span>
                <img src="${iconUrl}" alt="icon" class="w-8 h-8">
                <span class="font-semibold text-white mt-2">${temp}°C</span>
            </div>
        `;

        rainContainer.innerHTML += `
            <div class="flex flex-col items-center min-w-[60px] snap-center">
                <span class="text-xs text-gray-400 mb-2">${timeStr}</span>
                <i class="fa-solid fa-cloud-rain text-blue-400 my-2"></i>
                <span class="font-semibold text-white">${pop}%</span>
            </div>
        `;
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
            if (item.main.temp_min < dailyData[dateStr].min) {
                dailyData[dateStr].min = item.main.temp_min;
            }

            if (item.main.temp_max > dailyData[dateStr].max) {
                dailyData[dateStr].max = item.main.temp_max;
            }

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

        const dayName =
            dayNames[new Date(dayObj.dt * 1000).getDay()];

        const dateFormatted =
            new Date(dayObj.dt * 1000).toLocaleDateString('en-GB', {
                day: '2-digit',
                month: 'short'
            });

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
            <div onclick="showForecastDetails('${safeData}')"
                 class="flex items-center justify-between p-3 rounded-2xl hover:bg-[#262a40] cursor-pointer transition border border-transparent hover:border-[#32364a] mb-1">

                <div class="flex flex-col w-20">
                    <span class="text-sm font-bold text-gray-200">${dayName}</span>
                    <span class="text-xs text-gray-500">${dateFormatted}</span>
                </div>

                <div class="flex items-center gap-3 flex-1">
                    <img src="https://openweathermap.org/img/wn/${dayObj.icon}.png"
                         class="w-8 h-8">

                    <span class="text-xs text-blue-100 capitalize hidden sm:block truncate">
                        ${dayObj.desc}
                    </span>
                </div>

                <div class="flex gap-3 justify-end w-20">
                    <span class="text-sm font-bold text-white">
                        ${Math.round(dayObj.max)}°
                    </span>

                    <span class="text-sm font-medium text-gray-500">
                        ${Math.round(dayObj.min)}°
                    </span>
                </div>
            </div>
        `;
    });

   renderUvIndex(data.uv);

    const sunContainer = document.getElementById('sun-tracking-container');

    if (current.sys && current.sys.sunrise && current.sys.sunset) {
        const srDate = new Date(current.sys.sunrise * 1000);
        const ssDate = new Date(current.sys.sunset * 1000);

        const srTime = srDate.toLocaleTimeString([], {
            hour: '2-digit',
            minute: '2-digit'
        });

        const ssTime = ssDate.toLocaleTimeString([], {
            hour: '2-digit',
            minute: '2-digit'
        });

        const diffMs = ssDate - srDate;

        const hrs = Math.floor(diffMs / 3600000);
        const mins = Math.floor((diffMs % 3600000) / 60000);

        sunContainer.innerHTML = `
            <div class="flex items-center justify-between border-b border-[#262a40] pb-2">
                <span class="text-sm text-gray-400">
                    <i class="fa-solid fa-sun text-yellow-500 mr-2"></i>
                    Sunrise
                </span>
                <span class="font-bold text-white">${srTime}</span>
            </div>

            <div class="flex items-center justify-between border-b border-[#262a40] pb-2">
                <span class="text-sm text-gray-400">
                    <i class="fa-solid fa-moon text-blue-300 mr-2"></i>
                    Sunset
                </span>
                <span class="font-bold text-white">${ssTime}</span>
            </div>

            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-400">
                    <i class="fa-solid fa-stopwatch text-green-400 mr-2"></i>
                    Daylight
                </span>
                <span class="font-bold text-white">${hrs}h ${mins}m</span>
            </div>
        `;
    }

    // Temperature Trend Graph
    const graphContainer =
        document.getElementById('temp-graph-container');

    const graphData = forecastList.slice(0, 6);

    if (graphContainer) {
        if (graphData.length > 0) {
            const temps = graphData.map(item => {
                const value = Number(item?.main?.temp);
                return Number.isFinite(value) ? Math.round(value) : null;
            });

            if (temps.every(t => t !== null)) {
                const minT = Math.min(...temps) - 2;
                const maxT = Math.max(...temps) + 2;
                const range = (maxT - minT) || 1;

                const width = 600;
                const height = 100;

                const step =
                    temps.length > 1
                        ? width / (temps.length - 1)
                        : width;

                const points = [];

                let svgHtml = `
                    <svg
                        viewBox="-20 0 640 120"
                        class="w-full min-w-[500px] h-full overflow-visible"
                        role="img"
                        aria-label="Temperature trend">
                `;

                temps.forEach((t, i) => {
                    const x = i * step;

                    const y =
                        height -
                        ((t - minT) / range) * (height - 30) -
                        20;

                    points.push(`${x},${y}`);

                    const rawTime = graphData[i]?.dt;

                    const timeStr = rawTime
                        ? new Date(rawTime * 1000).toLocaleTimeString([], {
                              hour: 'numeric',
                              hour12: true
                          })
                        : '--';

                    svgHtml += `
                        <text
                            x="${x}"
                            y="${y - 12}"
                            fill="white"
                            font-size="14"
                            font-weight="bold"
                            text-anchor="middle">
                            ${t}°
                        </text>

                        <circle
                            cx="${x}"
                            cy="${y}"
                            r="4"
                            fill="#3b82f6">
                        </circle>

                        <text
                            x="${x}"
                            y="${height + 15}"
                            fill="#9ca3af"
                            font-size="12"
                            text-anchor="middle">
                            ${timeStr}
                        </text>
                    `;
                });

                svgHtml += `
                    <polyline
                        points="${points.join(' ')}"
                        fill="none"
                        stroke="#3b82f6"
                        stroke-width="3"
                        stroke-linecap="round"
                        stroke-linejoin="round" />
                `;

                svgHtml += '</svg>';

                graphContainer.innerHTML = svgHtml;
            } else {
                graphContainer.innerHTML =
                    '<p class="text-sm text-gray-400">Temperature data unavailable.</p>';
            }
        } else {
            graphContainer.innerHTML =
                '<p class="text-sm text-gray-400">Temperature forecast unavailable.</p>';
        }
    }

        
    // Weather Alerts
   renderWeatherAlerts(
       current,
       forecastList,
       Number(data?.uv?.value)
    );

    const farmerCity =
    document.getElementById('farmer-city')?.value;

const farmerCrop =
    document.getElementById('farmer-crop')?.value;

if (
    farmerCity &&
    farmerCrop &&
    farmerCity.toLowerCase() ===
        String(current?.name || '').toLowerCase()
) {
    renderFarmerAdvisory(
        data,
        farmerCrop
    );
}

 }

 // ==========================================
// DYNAMIC UV INDEX
// ==========================================

function renderUvIndex(uvData) {

    const container =
        document.getElementById('uv-container');

    if (!container) return;


    // Loading / unavailable state
    if (!uvData || uvData.available === false) {

        container.innerHTML = `
            <div class="flex flex-col justify-center h-full py-2">

                <div class="flex items-center gap-2 text-gray-400">
                    <i class="fa-solid fa-circle-exclamation text-orange-400"></i>

                    <span class="text-sm font-medium">
                        UV data unavailable
                    </span>
                </div>

                <p class="text-xs text-gray-500 mt-2">
                    ${escapeHtml(
                        uvData?.error ||
                        'UV information could not be loaded.'
                    )}
                </p>

            </div>
        `;

        return;
    }


    const uv =
        Number(uvData.value);

    if (!Number.isFinite(uv)) {

        container.innerHTML = `
            <p class="text-sm text-gray-400">
                UV data unavailable
            </p>
        `;

        return;
    }


    const category =
        uvData.category || 'Unknown';

    const peakTime =
        uvData.peak_time || '--';

    const peakValue =
        uvData.peak_value !== null &&
        uvData.peak_value !== undefined
            ? Number(uvData.peak_value).toFixed(1)
            : '--';

    const recommendation =
        uvData.recommendation ||
        'Use appropriate sun protection.';


    // 0–12+ mapped to 0–360 degrees
    const gaugePercent =
        Math.min(Math.max(uv, 0), 12) / 12;

    const gaugeAngle =
        Math.round(gaugePercent * 360);

    const gaugeColor =
        uvData.color || '#3b82f6';


    container.innerHTML = `

        <div class="flex flex-col h-full">

            <!-- Header / Gauge -->
            <div class="flex items-center justify-between gap-4">

                <!-- Circular Gauge -->
                <div
                    class="relative w-28 h-28 rounded-full shrink-0 flex items-center justify-center"
                    style="
                        background:
                        conic-gradient(
                            ${gaugeColor} ${gaugeAngle}deg,
                            #262a40 ${gaugeAngle}deg 360deg
                        );
                    "
                >

                    <div
                        class="absolute inset-[8px] rounded-full bg-[#1b1f30] flex flex-col items-center justify-center"
                    >

                        <span class="text-[10px] text-gray-400 uppercase tracking-wide">
                            UV
                        </span>

                        <span
                            class="text-3xl font-extrabold text-white leading-none"
                        >
                            ${uv % 1 === 0 ? uv.toFixed(0) : uv.toFixed(1)}
                        </span>

                    </div>

                </div>


                <!-- Category -->
                <div class="flex-1 min-w-0">

                    <p
                        class="text-xs text-gray-500 uppercase tracking-wider"
                    >
                        Current Level
                    </p>

                    <h4
                        class="text-xl font-bold mt-1"
                        style="color:${gaugeColor};"
                    >
                        ${escapeHtml(category)}
                    </h4>

                    <p class="text-xs text-gray-400 mt-1">
                        Peak today:
                        <span class="text-white font-medium">
                            ${escapeHtml(peakTime)}
                        </span>
                    </p>

                    <p class="text-xs text-gray-500 mt-0.5">
                        Peak UV:
                        <span class="text-gray-300 font-medium">
                            ${peakValue}
                        </span>
                    </p>

                </div>

            </div>


            <!-- UV Scale -->
            <div class="mt-4">

                <div class="flex justify-between text-[10px] text-gray-500 mb-1">
                    <span>Low</span>
                    <span>Moderate</span>
                    <span>High</span>
                    <span>Very High</span>
                    <span>Extreme</span>
                </div>

                <div class="h-2 rounded-full overflow-hidden bg-[#262a40]">
                    <div
                        class="h-full rounded-full transition-all duration-700"
                        style="
                            width:${Math.max(
                                4,
                                Math.min(100, gaugePercent * 100)
                            )}%;
                            background:${gaugeColor};
                        "
                    ></div>
                </div>

            </div>


            <!-- Safety Recommendation -->
            <div
                class="mt-4 p-3 rounded-xl bg-[#131521] border border-[#262a40]"
            >

                <div class="flex items-start gap-2">

                    <i
                        class="fa-solid fa-shield-sun mt-0.5"
                        style="color:${gaugeColor};"
                    ></i>

                    <div>

                        <p class="text-[10px] text-gray-500 uppercase tracking-wider">
                            Safety Recommendation
                        </p>

                        <p class="text-xs text-gray-300 mt-1 leading-relaxed">
                            ${escapeHtml(recommendation)}
                        </p>

                    </div>

                </div>

            </div>

        </div>
    `;
}

function showForecastDetails(dataStr) {
    const data = JSON.parse(dataStr);

    document.getElementById('modal-date').innerText = data.date;
    document.getElementById('modal-desc').innerText = data.desc;
    document.getElementById('modal-max').innerText = data.max;
    document.getElementById('modal-min').innerText = data.min;
    document.getElementById('modal-humidity').innerText = data.humidity + '%';
    document.getElementById('modal-wind').innerText = data.wind + ' km/h';

    document.getElementById('modal-icon').src =
        `https://openweathermap.org/img/wn/${data.icon}@4x.png`;

    document.getElementById('forecast-modal').classList.remove('hidden');
}

function closeForecastModal() {
    document.getElementById('forecast-modal').classList.add('hidden');
}


// ==========================================
// GUJARAT WEATHER MAP
// ==========================================

async function initInteractiveMap() {
    const grid = document.getElementById('interactive-map-grid');

    if (!grid) return;

    grid.innerHTML = `
        <p class="text-sm text-gray-400 col-span-full text-center py-6">
            Initializing Gujarat weather nodes...
        </p>
    `;

    let html = '';

    const sampleDistricts = [
        "Ahmedabad",
        "Mahesana",
        "Rajkot",
        "Surat",
        "Vadodara",
        "Gandhinagar",
        "Kutch",
        "Jamnagar",
        "Bhavnagar",
        "Junagadh",
        "Anand",
        "Navsari"
    ];

    for (const d of sampleDistricts) {
        try {
            const res = await fetch(
                `/api/weather?city=${encodeURIComponent(d)}`
            );

            const json = await res.json();

            const cur = json.current ? json.current : json;

            if (!cur?.main || !cur?.weather?.[0]) {
                continue;
            }

            const temp = Math.round(cur.main.temp);
            const cond = cur.weather[0].description;
            const hum = cur.main.humidity;
            const wind = cur.wind?.speed ?? 0;

            let badgeColor =
                "bg-green-400/20 text-green-400 border-green-400/30";

            if (temp < 20) {
                badgeColor =
                    "bg-blue-400/20 text-blue-400 border-blue-400/30";
            }
            else if (temp <= 25) {
                badgeColor =
                    "bg-teal-400/20 text-teal-400 border-teal-400/30";
            }
            else if (temp <= 30) {
                badgeColor =
                    "bg-green-400/20 text-green-400 border-green-400/30";
            }
            else if (temp <= 35) {
                badgeColor =
                    "bg-orange-400/20 text-orange-400 border-orange-400/30";
            }
            else {
                badgeColor =
                    "bg-red-500/20 text-red-400 border-red-500/30";
            }

            html += `
                <div class="bg-[#131521] p-5 rounded-2xl border border-[#262a40] flex flex-col justify-between hover:border-blue-500/50 transition">

                    <div>
                        <div class="flex justify-between items-start mb-2">

                            <h4 class="font-bold text-white text-base">
                                ${d}
                            </h4>

                            <span class="px-2.5 py-1 rounded-lg text-xs font-bold border ${badgeColor}">
                                ${temp}°C
                            </span>
                        </div>

                        <p class="text-xs text-gray-400 capitalize mb-3">
                            ${cond}
                        </p>

                        <div class="text-xs text-gray-400 space-y-1">
                            <div>
                                Humidity:
                                <span class="text-white">${hum}%</span>
                            </div>

                            <div>
                                Wind:
                                <span class="text-white">${wind} km/h</span>
                            </div>
                        </div>
                    </div>

                    <button
                        onclick="switchTab('dashboard'); fetchWeatherData('/api/weather?city=${encodeURIComponent(d)}')"
                        class="mt-4 w-full bg-[#1b1f30] hover:bg-blue-500 text-blue-400 hover:text-white border border-[#262a40] py-2 rounded-xl text-xs font-medium transition">

                        View Details

                    </button>
                </div>
            `;

        } catch (e) {
            console.error(`Map weather error for ${d}:`, e);
        }
    }

    grid.innerHTML = html ||
        '<p class="text-sm text-gray-400 col-span-full text-center py-6">Weather nodes unavailable.</p>';
}


// ==========================================
// HISTORICAL WEATHER
// ==========================================

function populateHistoricalDropdowns() {

    const select =
        document.getElementById('hist-city');

    if (!select) return;

    select.innerHTML = `
        <option value="" selected disabled>
            Select District
        </option>
    `;

    gujaratDistricts.forEach(district => {

        const option = document.createElement('option');

        option.value = district;
        option.textContent = district;

        select.appendChild(option);
    });

    // Reset historical fields
    const histCity =
        document.getElementById('hist-city');

    const histFrom =
        document.getElementById('hist-from');

    const histTo =
        document.getElementById('hist-to');

    if (histCity) histCity.value = '';
    if (histFrom) histFrom.value = '';
    if (histTo) histTo.value = '';
}
async function fetchHistoricalData() {

    const cityElement =
        document.getElementById('hist-city');

    const fromElement =
        document.getElementById('hist-from');

    const toElement =
        document.getElementById('hist-to');

    const results =
        document.getElementById('historical-results');

    const status =
        document.getElementById('historical-status');

    const analyzeButton =
        document.getElementById('historical-analyze-btn');

    const analyzeText =
        document.getElementById('historical-analyze-text');

    const summary =
        document.getElementById('historical-summary');

    const lastUpdated =
        document.getElementById('historical-last-updated');


    if (
        !cityElement ||
        !fromElement ||
        !toElement ||
        !results ||
        !status ||
        !analyzeButton ||
        !analyzeText
    ) {
        console.error(
            'Historical weather elements are missing from the page.'
        );

        return;
    }


    const city =
        cityElement.value.trim();

    const from =
        fromElement.value;

    const to =
        toElement.value;


    // ==========================================
    // VALIDATION
    // ==========================================

    if (!city) {

        showHistoricalStatus(
            'Please select a Gujarat district.',
            'error'
        );

        results.classList.add('hidden');

        cityElement.focus();

        return;
    }


    if (!from) {

        showHistoricalStatus(
            'Please select the From Date.',
            'error'
        );

        results.classList.add('hidden');

        fromElement.focus();

        return;
    }


    if (!to) {

        showHistoricalStatus(
            'Please select the To Date.',
            'error'
        );

        results.classList.add('hidden');

        toElement.focus();

        return;
    }


    if (from > to) {

        showHistoricalStatus(
            'From Date cannot be later than To Date.',
            'error'
        );

        results.classList.add('hidden');

        fromElement.focus();

        return;
    }


    // ==========================================
    // LOADING STATE
    // ==========================================

    analyzeButton.disabled = true;

    analyzeText.innerHTML = `
        <i class="fa-solid fa-spinner fa-spin mr-2"></i>
        Analyzing...
    `;


    results.classList.add('hidden');


    showHistoricalStatus(
        `
            <div class="flex items-center gap-3">
                <i class="fa-solid fa-spinner fa-spin text-blue-400 text-lg"></i>

                <div>
                    <p class="text-white font-medium">
                        Analyzing historical weather...
                    </p>

                    <p class="text-gray-400 text-xs mt-1">
                        Processing ${escapeHtml(city)}
                        from ${escapeHtml(from)}
                        to ${escapeHtml(to)}
                    </p>
                </div>
            </div>
        `,
        'loading',
        true
    );


    try {

        const query =
            `/api/historical` +
            `?city=${encodeURIComponent(city)}` +
            `&from=${encodeURIComponent(from)}` +
            `&to=${encodeURIComponent(to)}`;


        const response =
            await fetch(query, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });


        const contentType =
            response.headers.get('content-type') || '';


        let data = {};

        if (contentType.includes('application/json')) {
            data = await response.json();
        }


        if (!response.ok) {

            throw new Error(
                data.error ||
                data.message ||
                `Historical analysis failed (${response.status}).`
            );
        }


        // ==========================================
        // UPDATE RESULT CARDS
        // ==========================================

        const avgTemp =
            document.getElementById('hist-avg');

        const maxTemp =
            document.getElementById('hist-max');

        const minTemp =
            document.getElementById('hist-min');

        const rainfall =
            document.getElementById('hist-rain');

        const humidity =
            document.getElementById('hist-humidity');


        if (avgTemp) {
            avgTemp.innerText =
                `${data.avg_temp ?? '--'}°C`;
        }


        if (maxTemp) {
            maxTemp.innerText =
                `${data.max_temp ?? '--'}°C`;
        }


        if (minTemp) {
            minTemp.innerText =
                `${data.min_temp ?? '--'}°C`;
        }


        if (rainfall) {
            rainfall.innerText =
                `${data.total_rainfall ?? '--'} mm`;
        }


        if (humidity) {
            humidity.innerText =
                `${data.avg_humidity ?? '--'}%`;
        }


        // ==========================================
        // SUMMARY
        // ==========================================

        if (summary) {

            summary.innerText =
                `${city} historical weather analysis from ${from} to ${to}.`;
        }


        if (lastUpdated) {

            lastUpdated.innerText =
                `Analyzed: ${new Date().toLocaleTimeString([], {
                    hour: '2-digit',
                    minute: '2-digit'
                })}`;
        }


        // ==========================================
        // SHOW SUCCESS
        // ==========================================

        results.classList.remove('hidden');

        showHistoricalStatus(
            `
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-circle-check text-green-400 text-lg"></i>

                    <div>
                        <p class="text-green-400 font-medium">
                            Historical analysis completed successfully.
                        </p>

                        <p class="text-gray-400 text-xs mt-1">
                            ${escapeHtml(city)}
                            • ${escapeHtml(from)}
                            → ${escapeHtml(to)}
                        </p>
                    </div>
                </div>
            `,
            'success',
            true
        );


    } catch (error) {

        console.error(
            'Historical weather error:',
            error
        );


        results.classList.add('hidden');


        showHistoricalStatus(
            `
                <div class="flex items-start gap-3">
                    <i class="fa-solid fa-circle-exclamation text-red-400 text-lg mt-0.5"></i>

                    <div>
                        <p class="text-red-400 font-medium">
                            Historical analysis failed.
                        </p>

                        <p class="text-gray-400 text-xs mt-1">
                            ${escapeHtml(
                                error.message ||
                                'Unable to load historical weather data.'
                            )}
                        </p>
                    </div>
                </div>
            `,
            'error',
            true
        );

    } finally {

        analyzeButton.disabled = false;

        analyzeText.innerHTML = `
            <i class="fa-solid fa-chart-line mr-2"></i>
            Analyze
        `;
    }
}

function showHistoricalStatus(message, type = 'info', allowHtml = false) {

    const status =
        document.getElementById('historical-status');

    if (!status) {
        console.error('historical-status element not found.');
        return;
    }

    // Remove previous status classes
    status.classList.remove(
        'hidden',
        'bg-red-500/10',
        'border-red-500/30',
        'text-red-400',
        'bg-blue-500/10',
        'border-blue-500/30',
        'text-blue-400',
        'bg-green-500/10',
        'border-green-500/30',
        'text-green-400'
    );

    // Set message
    if (allowHtml) {
        status.innerHTML = message;
    } else {
        status.textContent = message;
    }

    // Apply status type
    if (type === 'error') {

        status.classList.add(
            'bg-red-500/10',
            'border',
            'border-red-500/30',
            'text-red-400'
        );

    } else if (type === 'success') {

        status.classList.add(
            'bg-green-500/10',
            'border',
            'border-green-500/30',
            'text-green-400'
        );

    } else {

        // info / loading
        status.classList.add(
            'bg-blue-500/10',
            'border',
            'border-blue-500/30',
            'text-blue-400'
        );
    }

    status.classList.remove('hidden');
}
// ==========================================
// AI METEOROLOGIST CHATBOT
// ==========================================

async function sendAiMessage() {
    const input = document.getElementById('ai-input');

    if (!input) return;

    const prompt = input.value.trim();

    if (!prompt) return;

    const chatContainer =
        document.getElementById('chat-messages');

    if (!chatContainer) return;

    chatContainer.innerHTML += `
        <div class="flex items-start gap-3 justify-end">

            <div class="bg-blue-600 text-white p-4 rounded-2xl text-sm max-w-lg leading-relaxed">
                ${escapeHtml(prompt)}
            </div>

            <div class="w-8 h-8 rounded-full bg-gray-700 text-white flex items-center justify-center shrink-0 text-xs font-bold">
                You
            </div>

        </div>
    `;

    input.value = '';

    chatContainer.scrollTop =
        chatContainer.scrollHeight;

    const loadingId =
        'ai-load-' + Date.now();

    chatContainer.innerHTML += `
        <div id="${loadingId}" class="flex items-start gap-3">

            <div class="w-8 h-8 rounded-full bg-blue-500 text-white flex items-center justify-center shrink-0 text-xs font-bold">
                AI
            </div>

            <div class="bg-[#131521] border border-[#262a40] p-4 rounded-2xl text-sm text-gray-400 italic">
                Consulting Gujarat climate models...
            </div>

        </div>
    `;

    chatContainer.scrollTop =
        chatContainer.scrollHeight;

    try {
        const csrfToken =
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content') || '';

        if (!csrfToken) {
            throw new Error(
                'CSRF token not found in page.'
            );
        }

        const response = await fetch(
            '/api/meteorologist',
            {
                method: 'POST',

                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },

                body: JSON.stringify({
                    prompt: prompt,
                    context:
                        liveDashboardCache[currentActiveCity] || {}
                })
            }
        );

        const contentType =
            response.headers.get('content-type') || '';

        const resJson =
            contentType.includes('application/json')
                ? await response.json()
                : {};

        const loadingEl =
            document.getElementById(loadingId);

        if (loadingEl) {
            loadingEl.remove();
        }

        if (!response.ok) {
            throw new Error(
                resJson.message ||
                resJson.error ||
                `AI request failed (${response.status})`
            );
        }

        const replyText =
            resJson.reply ||
            'I am currently unable to generate a response. Please try again later.';

        chatContainer.innerHTML += `
            <div class="flex items-start gap-3">

                <div class="w-8 h-8 rounded-full bg-blue-500 text-white flex items-center justify-center shrink-0 text-xs font-bold">
                    AI
                </div>

                <div class="bg-[#131521] border border-[#262a40] p-4 rounded-2xl text-sm text-gray-200 max-w-lg leading-relaxed">
                    ${escapeHtml(replyText)}
                </div>

            </div>
        `;

        chatContainer.scrollTop =
            chatContainer.scrollHeight;

    } catch (e) {

        const loadingEl =
            document.getElementById(loadingId);

        if (loadingEl) {
            loadingEl.remove();
        }

        console.error(
            'Meteorologist AI error:',
            e
        );

        chatContainer.innerHTML += `
            <div class="flex items-start gap-3">

                <div class="w-8 h-8 rounded-full bg-blue-500 text-white flex items-center justify-center shrink-0 text-xs font-bold">
                    AI
                </div>

                <div class="bg-[#131521] border border-red-500/30 p-4 rounded-2xl text-sm text-red-400">
                    Connection error. Please try again.
                </div>

            </div>
        `;

        chatContainer.scrollTop =
            chatContainer.scrollHeight;
    }
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
// ==========================================
// FAVORITES
// ==========================================

document.getElementById('favorite-btn')?.addEventListener('click', () => {
    if (!currentActiveCity) return;

    let favs = getFavorites();

    const existingIndex = favs.findIndex(
        city => city.toLowerCase() === currentActiveCity.toLowerCase()
    );

    if (existingIndex !== -1) {
        favs.splice(existingIndex, 1);
    } else {
        favs.push(currentActiveCity);
    }

    // Remove duplicate cities
    favs = [...new Map(
        favs.map(city => [city.toLowerCase(), city])
    ).values()];

    localStorage.setItem(
        'gujarat_weather_favorites',
        JSON.stringify(favs)
    );

    updateFavoriteStarUI();
    renderFavorites();
});

function getFavorites() {
    try {
        const stored = JSON.parse(
            localStorage.getItem('gujarat_weather_favorites')
        );

        if (!Array.isArray(stored)) return [];

        return [...new Map(
            stored
                .filter(city => typeof city === 'string' && city.trim() !== '')
                .map(city => [city.trim().toLowerCase(), city.trim()])
        ).values()];
    } catch (error) {
        console.error('Favorites parse error:', error);
        return [];
    }
}

function updateFavoriteStarUI() {
    const icon = document.getElementById('favorite-icon');

    if (!icon) return;

    const isFavorite = getFavorites().some(
        city => city.toLowerCase() === currentActiveCity.toLowerCase()
    );

    if (isFavorite) {
        icon.classList.remove('fa-regular');
        icon.classList.add('fa-solid', 'text-yellow-500');
    } else {
        icon.classList.remove('fa-solid', 'text-yellow-500');
        icon.classList.add('fa-regular');
    }
}

function removeFavorite(city, event) {
    if (event) {
        event.stopPropagation();
    }

    let favs = getFavorites();

    favs = favs.filter(
        favCity => favCity.toLowerCase() !== city.toLowerCase()
    );

    localStorage.setItem(
        'gujarat_weather_favorites',
        JSON.stringify(favs)
    );

    updateFavoriteStarUI();
    renderFavorites();
}

async function renderFavorites() {
    const favs = getFavorites();

    const container =
        document.getElementById('favorites-container');

    const msg =
        document.getElementById('no-favorites-msg');

    if (!container || !msg) return;

    if (favs.length === 0) {
        container.innerHTML = '';
        msg.style.display = 'block';
        return;
    }

    msg.style.display = 'none';
    container.innerHTML = '';

    for (const city of favs) {
        try {
            const response = await fetch(
                `/api/weather?city=${encodeURIComponent(city)}`
            );

            if (!response.ok) continue;

            const data = await response.json();

            const current =
                data.current ? data.current : data;

            if (
                !current?.weather?.[0] ||
                !current?.main
            ) {
                continue;
            }

            const description =
                current.weather[0].description || 'Unknown';

            const temperature =
                Number.isFinite(Number(current.main.temp))
                    ? Math.round(current.main.temp)
                    : '--';

            const iconCode =
                current.weather[0].icon || '01d';

            const iconUrl =
                `https://openweathermap.org/img/wn/${iconCode}@2x.png`;

            const safeCity = escapeHtml(city);
            const safeDescription = escapeHtml(description);

            container.innerHTML += `
                <div
                    class="fav-card group relative cursor-pointer
                           flex items-center gap-3
                           bg-[#262a40]
                           hover:bg-[#32364a]
                           px-4 py-3
                           rounded-xl
                           transition
                           min-w-[190px]
                           sm:min-w-[210px]
                           snap-center
                           border border-[#32364a]"
                    onclick="switchTab('dashboard'); fetchWeatherData('/api/weather?city=${encodeURIComponent(city)}')"
                >

                    <!-- Weather Icon -->
                    <img
                        src="${iconUrl}"
                        alt="${safeDescription}"
                        class="w-11 h-11 shrink-0"
                    >

                    <!-- City + Condition -->
                    <div class="flex flex-col min-w-0 flex-1">

                        <span
                            class="text-white font-medium text-sm truncate pr-5"
                        >
                            <i class="fa-solid fa-star text-yellow-500 text-[10px] mr-1"></i>
                            ${safeCity}
                        </span>

                        <span
                            class="text-gray-400 text-xs capitalize truncate"
                        >
                            ${safeDescription}
                        </span>

                    </div>

                    <!-- Temperature -->
                    <span
                        class="text-lg font-bold text-white shrink-0"
                    >
                        ${temperature}°
                    </span>

                    <!-- Remove Button -->
                    <button
                        type="button"
                        title="Remove ${safeCity}"
                        aria-label="Remove ${safeCity} from favorites"
                        onclick="removeFavorite('${safeCity}', event)"
                        class="absolute top-1 right-1
                               w-6 h-6
                               rounded-full
                               bg-[#131521]/80
                               text-gray-400
                               hover:text-red-400
                               hover:bg-red-500/10
                               opacity-0
                               group-hover:opacity-100
                               transition
                               flex items-center justify-center"
                    >
                        <i class="fa-solid fa-xmark text-[11px]"></i>
                    </button>

                </div>
            `;
        } catch (error) {
            console.error(
                `Favorite weather error for ${city}:`,
                error
            );
        }
    }
}

// ==========================================
// SEARCH
// ==========================================

document.getElementById('search-btn')?.addEventListener(
    'click',
    () => {
        const input =
            document.getElementById('city');

        if (!input) return;

        const city = input.value.trim();

        if (!city) {
            showAlert('Please enter a city or district name.');
            return;
        }

        switchTab('dashboard');

        fetchWeatherData(
            `/api/weather?city=${encodeURIComponent(city)}`
        );
    }
);

document.getElementById('city')?.addEventListener(
    'keydown',
    event => {
        if (event.key === 'Enter') {
            document
                .getElementById('search-btn')
                ?.click();
        }
    }
);


// ==========================================
// GPS / CURRENT LOCATION
// ==========================================

document.getElementById('location-btn')?.addEventListener(
    'click',
    () => {

        if (!navigator.geolocation) {
            showAlert(
                'Geolocation is not supported by this browser.'
            );
            return;
        }

        showAlert(
            'Detecting your location...',
            false
        );

        navigator.geolocation.getCurrentPosition(
            position => {

                switchTab('dashboard');

                const latitude =
                    position.coords.latitude;

                const longitude =
                    position.coords.longitude;

                fetchWeatherData(
                    `/api/weather?lat=${encodeURIComponent(latitude)}&lon=${encodeURIComponent(longitude)}`
                );
            },

            error => {

                console.error(
                    'Geolocation error:',
                    error
                );

                let message =
                    'Unable to detect your location.';

                if (error.code === 1) {
                    message =
                        'Location permission was denied. Please allow location access.';
                }
                else if (error.code === 2) {
                    message =
                        'Your location could not be determined.';
                }
                else if (error.code === 3) {
                    message =
                        'Location request timed out.';
                }

                showAlert(message);
            },

            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 300000
            }
        );
    }
);


// ==========================================
// GUJARAT DISTRICT GRID
// ==========================================

function renderDistrictsGrid(districts) {
    const grid =
        document.getElementById('districts-grid');

    if (!grid) return;

    grid.innerHTML = '';

    if (!districts.length) {
        grid.innerHTML = `
            <div class="col-span-full text-center py-6 text-gray-400 text-sm">
                No Gujarat district found.
            </div>
        `;

        return;
    }

    districts.forEach(district => {

        const button =
            document.createElement('button');

        button.type = 'button';

        button.className =
            'district-btn text-left px-3 py-2 bg-[#262a40] hover:bg-blue-500 hover:text-white text-gray-300 text-sm rounded-lg transition border border-[#32364a]';

        button.textContent = district;

        button.addEventListener('click', () => {

            switchTab('dashboard');

            fetchWeatherData(
                `/api/weather?city=${encodeURIComponent(district)}`
            );
        });

        grid.appendChild(button);
    });
}


// ==========================================
// DISTRICT SEARCH / FILTER
// ==========================================

document.getElementById('district-filter')
    ?.addEventListener(
        'input',
        event => {

            const searchValue =
                event.target.value
                    .trim()
                    .toLowerCase();

            const filtered =
                gujaratDistricts.filter(
                    district =>
                        district
                            .toLowerCase()
                            .includes(searchValue)
                );

            renderDistrictsGrid(filtered);
        }
    );


// ==========================================
// COMMON ALERT MESSAGE
// ==========================================

function showAlert(message, isError = true) {

    const box =
        document.getElementById('alert-box');

    if (!box) return;

    if (!message) {
        box.classList.add('hidden');
        return;
    }

    box.innerText = message;

    box.classList.remove(
        'text-blue-400',
        'text-orange-400'
    );

    if (isError) {
        box.classList.add('text-orange-400');
    } else {
        box.classList.add('text-blue-400');
    }

    box.classList.remove('hidden');

    if (isError) {
        clearTimeout(
            window.weatherAlertTimeout
        );

        window.weatherAlertTimeout =
            setTimeout(() => {
                box.classList.add('hidden');
            }, 5000);
    }
}


// ==========================================
// AI QUICK PROMPTS
// ==========================================

async function sendQuickPrompt(query) {

    const inputField =
        document.getElementById('ai-input');

    if (!inputField) return;

    inputField.value = query;

    await sendAiMessage();
}


// ==========================================
// AI ENTER KEY SUPPORT
// ==========================================

document.getElementById('ai-input')
    ?.addEventListener(
        'keydown',
        async event => {

            if (
                event.key === 'Enter' &&
                !event.shiftKey
            ) {
                event.preventDefault();

                await sendAiMessage();
            }
        }
    );


// ==========================================
// CLOSE FORECAST MODAL ON BACKDROP CLICK
// ==========================================

document.getElementById('forecast-modal')
    ?.addEventListener(
        'click',
        event => {

            if (
                event.target.id ===
                'forecast-modal'
            ) {
                closeForecastModal();
            }
        }
    );


// ==========================================
// ESC KEY
// ==========================================

document.addEventListener(
    'keydown',
    event => {

        if (event.key === 'Escape') {

            const modal =
                document.getElementById(
                    'forecast-modal'
                );

            if (modal) {
                modal.classList.add('hidden');
            }
        }
    }
);


// ==========================================
// SAFE INITIALIZATION
// ==========================================

window.addEventListener(
    'load',
    () => {

        // Restore favorite UI after all DOM
        // elements are available.
        updateFavoriteStarUI();

        // Ensure initial tab is dashboard.
        const dashboard =
            document.getElementById('tab-dashboard');

        if (dashboard) {
            dashboard.classList.remove('hidden');
        }

        // Prevent stale cached script problems
        // by recording current JS version.
        console.log(
            'Gujarat Weather App initialized successfully.'
        );
    }
);

/* =========================================================
   AQI FRONTEND INTEGRATION
   Add the CALL inside updateDashboardUI() immediately after:

   currentActiveCity = current.name;
   liveDashboardCache[current.name] = data;

   ========================================================= */

/* =========================================================
   ADD THESE FUNCTIONS AT THE END OF weather-app.js
   ========================================================= */

async function fetchAirQuality(lat, lon) {
    const content = document.getElementById('aqi-content');

    if (!content) return;

    if (!Number.isFinite(Number(lat)) || !Number.isFinite(Number(lon))) {
        showAQIState('error', 'Location coordinates are unavailable.');
        return;
    }

    showAQIState('loading');

    try {
        const response = await fetch(
            `/api/air-quality?lat=${encodeURIComponent(lat)}&lon=${encodeURIComponent(lon)}`,
            {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }
        );

        const contentType = response.headers.get('content-type') || '';
        const data = contentType.includes('application/json')
            ? await response.json()
            : {};

        if (!response.ok) {
            throw new Error(
                data.error || data.message || `AQI request failed (${response.status})`
            );
        }

        if (!data.aqi || !data.components) {
            throw new Error('Air quality data is incomplete.');
        }

        renderAQICard(data);

        const dashboardData = liveDashboardCache[currentActiveCity];

const dashboardCurrent =
    dashboardData?.current
        ? dashboardData.current
        : dashboardData;

if (dashboardCurrent) {

    renderWeatherAlerts(
        dashboardCurrent,
        dashboardData?.forecast?.list || [],
        Number(dashboardData?.uv?.value),
        data
    );

}

    } catch (error) {
        console.error('AQI error:', error);
        showAQIState('error', error.message || 'Unable to load air quality data.');
    }
}


function renderAQICard(data) {
    const content = document.getElementById('aqi-content');
    if (!content) return;

    const aqi = Math.max(1, Math.min(5, Number(data.aqi)));
    const category = data.category || 'Unknown';
    const color = data.color || '#60a5fa';
    const health = data.health_recommendation || 'Air quality information is currently unavailable.';
    const components = data.components || {};

    const circumference = 314.16;
    const progress = aqi / 5;
    const dashOffset = circumference * (1 - progress);

    const safe = value => {
        const number = Number(value);
        return Number.isFinite(number) ? number.toFixed(1) : '--';
    };

    content.innerHTML = `
        <div class="flex flex-col items-center">
            <div class="relative w-36 h-36 flex items-center justify-center">
                <svg viewBox="0 0 120 120" class="w-full h-full -rotate-90">
                    <circle
                        cx="60"
                        cy="60"
                        r="50"
                        fill="none"
                        stroke="#262a40"
                        stroke-width="10"
                    />

                    <circle
                        cx="60"
                        cy="60"
                        r="50"
                        fill="none"
                        stroke="${color}"
                        stroke-width="10"
                        stroke-linecap="round"
                        stroke-dasharray="${circumference}"
                        stroke-dashoffset="${dashOffset}"
                        style="transition: stroke-dashoffset .5s ease, stroke .3s ease;"
                    />
                </svg>

                <div class="absolute inset-0 flex flex-col items-center justify-center">
                    <span class="text-4xl font-bold text-white leading-none">
                        ${aqi}
                    </span>
                    <span class="text-[10px] text-gray-500 mt-1">
                        AQI / 5
                    </span>
                </div>
            </div>

            <div class="text-center -mt-1">
                <div
                    class="text-lg font-bold"
                    style="color:${color};"
                >
                    ${escapeHtml(category)}
                </div>

                <div class="text-[10px] text-gray-500 mt-0.5">
                    OpenWeather AQI scale
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-2 text-xs">
            <div class="rounded-xl bg-[#131521] border border-[#262a40] p-2.5">
                <span class="block text-gray-500">PM2.5</span>
                <strong class="text-white">${safe(components.pm2_5)} <span class="text-gray-500 font-normal">µg/m³</span></strong>
            </div>

            <div class="rounded-xl bg-[#131521] border border-[#262a40] p-2.5">
                <span class="block text-gray-500">PM10</span>
                <strong class="text-white">${safe(components.pm10)} <span class="text-gray-500 font-normal">µg/m³</span></strong>
            </div>

            <div class="rounded-xl bg-[#131521] border border-[#262a40] p-2.5">
                <span class="block text-gray-500">CO</span>
                <strong class="text-white">${safe(components.co)} <span class="text-gray-500 font-normal">µg/m³</span></strong>
            </div>

            <div class="rounded-xl bg-[#131521] border border-[#262a40] p-2.5">
                <span class="block text-gray-500">NO₂</span>
                <strong class="text-white">${safe(components.no2)} <span class="text-gray-500 font-normal">µg/m³</span></strong>
            </div>

            <div class="rounded-xl bg-[#131521] border border-[#262a40] p-2.5 col-span-2">
                <span class="block text-gray-500">SO₂</span>
                <strong class="text-white">${safe(components.so2)} <span class="text-gray-500 font-normal">µg/m³</span></strong>
            </div>
        </div>

        <div class="rounded-xl border p-3 text-xs"
             style="border-color:${color}55; background:${color}10;">
            <div class="flex items-start gap-2">
                <i class="fa-solid fa-heart-pulse mt-0.5" style="color:${color};"></i>
                <p class="text-gray-300 leading-relaxed">
                    ${escapeHtml(health)}
                </p>
            </div>
        </div>
    `;
}


function showAQIState(type, message = '') {
    const content = document.getElementById('aqi-content');
    if (!content) return;

    if (type === 'loading') {
        content.innerHTML = `
            <div class="flex flex-col items-center justify-center py-8 text-center">
                <i class="fa-solid fa-spinner fa-spin text-blue-400 text-2xl mb-3"></i>
                <p class="text-sm text-gray-300">Loading air quality...</p>
                <p class="text-[11px] text-gray-500 mt-1">Fetching live AQI data</p>
            </div>
        `;
        return;
    }

    if (type === 'empty') {
        content.innerHTML = `
            <div class="flex flex-col items-center justify-center py-8 text-center">
                <i class="fa-solid fa-wind text-gray-500 text-2xl mb-3"></i>
                <p class="text-sm text-gray-300">Air quality unavailable</p>
                <p class="text-[11px] text-gray-500 mt-1">No AQI data was returned for this location.</p>
            </div>
        `;
        return;
    }

    content.innerHTML = `
        <div class="flex flex-col items-center justify-center py-8 text-center">
            <i class="fa-solid fa-triangle-exclamation text-orange-400 text-2xl mb-3"></i>
            <p class="text-sm text-gray-300">Unable to load AQI</p>
            <p class="text-[11px] text-gray-500 mt-1 max-w-[230px]">
                ${escapeHtml(message || 'Please try again after changing the location.')}
            </p>
        </div>
    `;
}

// ==========================================
// DYNAMIC WEATHER ALERTS
// ==========================================

function renderWeatherAlerts(
    current,
    forecastList = [],
    uvValue = NaN,
    aqiData = null
) {
    const alertsContainer =
        document.getElementById('alerts-container');

    const alertBadge =
        document.getElementById('sidebar-alerts-badge');

    if (!alertsContainer) return;

    const location =
        current?.name ||
        currentActiveCity ||
        'Current location';

    const alerts = [];

    function addAlert(
        icon,
        color,
        bg,
        severity,
        title,
        description,
        time = ''
    ) {
        alerts.push({
            icon,
            color,
            bg,
            severity,
            title,
            description,
            location,
            time
        });
    }

    // ==========================================
    // 1. EXTREME HEAT
    // ==========================================

    const temp = Number(current?.main?.temp);

    if (Number.isFinite(temp)) {

        if (temp >= 45) {

            addAlert(
                'fa-temperature-arrow-up',
                'text-red-400',
                'bg-red-500/10 border-red-500/30',
                'Severe',
                'Extreme Heat',
                `Dangerous heat detected (${Math.round(temp)}°C). Avoid prolonged outdoor exposure and stay hydrated.`
            );

        } else if (temp >= 42) {

            addAlert(
                'fa-temperature-high',
                'text-red-400',
                'bg-red-500/10 border-red-500/30',
                'High',
                'Extreme Heat Warning',
                `Very high temperature detected (${Math.round(temp)}°C). Limit outdoor activity and stay hydrated.`
            );

        } else if (temp >= 38) {

            addAlert(
                'fa-temperature-half',
                'text-orange-400',
                'bg-orange-500/10 border-orange-500/30',
                'Moderate',
                'High Temperature',
                `High temperature detected (${Math.round(temp)}°C). Take breaks and drink plenty of water.`
            );
        }
    }

    // ==========================================
    // 2. HEAVY RAIN / THUNDERSTORM
    // ==========================================

    let thunderstormAdded = false;
    let rainAdded = false;

    for (const item of forecastList) {

        const main =
            String(
                item?.weather?.[0]?.main || ''
            ).toLowerCase();

        const description =
            String(
                item?.weather?.[0]?.description || ''
            ).toLowerCase();

        const pop =
            Number(item?.pop || 0);

        let timeText = '';

        if (item?.dt) {

            const forecastDate =
                new Date(Number(item.dt) * 1000);

            timeText =
                `Expected around ${forecastDate.toLocaleTimeString([], {
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                })}`;
        }

        // Thunderstorm

        if (
            main === 'thunderstorm' ||
            description.includes('thunder')
        ) {

            if (!thunderstormAdded) {

                addAlert(
                    'fa-cloud-bolt',
                    'text-red-400',
                    'bg-red-500/10 border-red-500/30',
                    'Severe',
                    'Thunderstorm Alert',
                    'Thunderstorm activity is forecast. Seek shelter indoors and avoid exposed areas.',
                    timeText
                );

                thunderstormAdded = true;
            }
        }

        // Heavy Rain

        if (
            !rainAdded &&
            main !== 'thunderstorm' &&
            pop >= 0.85
        ) {

            addAlert(
                'fa-cloud-showers-heavy',
                'text-blue-400',
                'bg-blue-500/10 border-blue-500/30',
                'High',
                'Heavy Rainfall',
                `High rainfall probability detected (${Math.round(pop * 100)}%).`,
                timeText
            );

            rainAdded = true;

        } else if (
            !rainAdded &&
            main !== 'thunderstorm' &&
            pop >= 0.70
        ) {

            addAlert(
                'fa-cloud-rain',
                'text-blue-400',
                'bg-blue-500/10 border-blue-500/30',
                'Moderate',
                'Rainfall Alert',
                `Rainfall probability is ${Math.round(pop * 100)}%.`,
                timeText
            );

            rainAdded = true;
        }
    }

    // ==========================================
    // 3. STRONG WIND
    // ==========================================

    const windSpeed =
        Number(current?.wind?.speed);

    if (Number.isFinite(windSpeed)) {

        if (windSpeed >= 20) {

            addAlert(
                'fa-wind',
                'text-red-400',
                'bg-red-500/10 border-red-500/30',
                'Severe',
                'Very Strong Winds',
                `Very strong winds detected (${Math.round(windSpeed)} km/h). Secure loose objects and avoid exposed areas.`
            );

        } else if (windSpeed >= 15) {

            addAlert(
                'fa-wind',
                'text-orange-400',
                'bg-orange-500/10 border-orange-500/30',
                'High',
                'Strong Winds',
                `Strong winds detected (${Math.round(windSpeed)} km/h). Use caution outdoors.`
            );

        } else if (windSpeed >= 10) {

            addAlert(
                'fa-wind',
                'text-gray-300',
                'bg-gray-500/20 border-gray-500/40',
                'Moderate',
                'Elevated Wind Speed',
                `Elevated wind speed detected (${Math.round(windSpeed)} km/h).`
            );
        }
    }

    // ==========================================
    // 4. HIGH UV
    // ==========================================

    if (Number.isFinite(uvValue)) {

        if (uvValue >= 11) {

            addAlert(
                'fa-sun',
                'text-red-400',
                'bg-red-500/10 border-red-500/30',
                'Severe',
                'Extreme UV Index',
                `UV Index is ${uvValue.toFixed(1)}. Avoid direct sun exposure and use strong sun protection.`
            );

        } else if (uvValue >= 8) {

            addAlert(
                'fa-sun',
                'text-red-400',
                'bg-red-500/10 border-red-500/30',
                'High',
                'High UV Index',
                `UV Index is ${uvValue.toFixed(1)}. Protect your skin and eyes.`
            );

        } else if (uvValue >= 6) {

            addAlert(
                'fa-sun',
                'text-yellow-400',
                'bg-yellow-500/10 border-yellow-500/30',
                'Moderate',
                'Elevated UV Index',
                `UV Index is ${uvValue.toFixed(1)}. Sunscreen and eye protection are recommended.`
            );
        }
    }

    // ==========================================
    // 5. POOR AIR QUALITY
    // ==========================================

    if (aqiData) {

        const aqi =
            Number(aqiData?.aqi);

        const category =
            String(
                aqiData?.category || ''
            ).toLowerCase();

        if (
            aqi >= 5 ||
            category.includes('very poor')
        ) {

            addAlert(
                'fa-lungs',
                'text-red-400',
                'bg-red-500/10 border-red-500/30',
                'Severe',
                'Very Poor Air Quality',
                `Air quality is very poor (${aqiData.category || 'AQI 5'}). Avoid strenuous outdoor activity.`
            );

        } else if (
            aqi >= 4 ||
            category.includes('poor')
        ) {

            addAlert(
                'fa-lungs',
                'text-orange-400',
                'bg-orange-500/10 border-orange-500/30',
                'High',
                'Poor Air Quality',
                `Air quality is poor (${aqiData.category || 'AQI 4'}). Reduce prolonged outdoor exposure if possible.`
            );
        }
    }

    // ==========================================
    // SORT BY SEVERITY
    // ==========================================

    const severityRank = {
        Severe: 4,
        High: 3,
        Moderate: 2,
        Information: 1
    };

    alerts.sort(
        (a, b) =>
            (severityRank[b.severity] || 0) -
            (severityRank[a.severity] || 0)
    );

    // ==========================================
    // NO ALERTS
    // ==========================================

    alertsContainer.innerHTML = '';

    if (alerts.length === 0) {

        if (alertBadge) {
            alertBadge.innerText = '0';
            alertBadge.classList.add('hidden');
        }

        alertsContainer.innerHTML = `
            <div class="p-4 rounded-xl border border-green-500/30 bg-green-500/10 flex items-center gap-3">

                <i class="fa-solid fa-circle-check text-green-400 text-lg"></i>

                <span class="text-sm text-green-400 font-medium">
                    No active weather alerts
                </span>

            </div>
        `;

        return;
    }

    // ==========================================
    // SIDEBAR BADGE
    // ==========================================

    if (alertBadge) {
        alertBadge.innerText = alerts.length;
        alertBadge.classList.remove('hidden');
    }

    // ==========================================
    // RENDER ALERTS
    // ==========================================

    alerts.forEach(alert => {

        let severityClass =
            'bg-blue-500/20 text-blue-300 border-blue-500/30';

        if (alert.severity === 'Severe') {
            severityClass =
                'bg-red-500/20 text-red-300 border-red-500/30';

        } else if (alert.severity === 'High') {
            severityClass =
                'bg-orange-500/20 text-orange-300 border-orange-500/30';

        } else if (alert.severity === 'Moderate') {
            severityClass =
                'bg-yellow-500/20 text-yellow-300 border-yellow-500/30';
        }

        alertsContainer.innerHTML += `
            <div class="p-4 rounded-xl border ${alert.bg}">

                <div class="flex gap-3 items-start">

                    <div class="w-9 h-9 rounded-xl bg-[#131521]/60 flex items-center justify-center shrink-0">

                        <i class="fa-solid ${alert.icon} ${alert.color} text-lg"></i>

                    </div>

                    <div class="min-w-0 flex-1">

                        <div class="flex flex-wrap items-center gap-2">

                            <h4 class="font-bold ${alert.color} text-sm">
                                ${escapeHtml(alert.title)}
                            </h4>

                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide ${severityClass}">
                                ${escapeHtml(alert.severity)}
                            </span>

                        </div>

                        <p class="text-xs text-gray-300 mt-1 leading-relaxed">
                            ${escapeHtml(alert.description)}
                        </p>

                        <div class="flex flex-wrap gap-x-4 gap-y-1 mt-2 text-[10px] text-gray-500">

                            <span>
                                <i class="fa-solid fa-location-dot mr-1"></i>
                                ${escapeHtml(alert.location)}
                            </span>

                            ${
                                alert.time
                                    ? `
                                        <span>
                                            <i class="fa-regular fa-clock mr-1"></i>
                                            ${escapeHtml(alert.time)}
                                        </span>
                                      `
                                    : ''
                            }

                        </div>

                    </div>

                </div>

            </div>
        `;
    });
}

function openWeatherAlerts(event) {

    if (event) {
        event.preventDefault();
    }

    // Open dashboard
    switchTab('dashboard');

    // Close mobile sidebar
    toggleMobileSidebar(true);

    // Wait for dashboard to become visible
    setTimeout(() => {

        const alertsSection =
            document.getElementById('alerts-section');

        if (!alertsSection) {
            console.error(
                'Weather Alerts section not found.'
            );
            return;
        }

        alertsSection.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });

        // Small visual highlight
        alertsSection.classList.add(
            'ring-2',
            'ring-blue-500/50'
        );

        setTimeout(() => {
            alertsSection.classList.remove(
                'ring-2',
                'ring-blue-500/50'
            );
        }, 1800);

    }, 350);
}

// ==========================================
// HEAT STRESS INDEX
// ==========================================

function calculateHeatIndexCelsius(tempC, humidity) {

    const T = (Number(tempC) * 9 / 5) + 32;
    const RH = Number(humidity);

    if (
        !Number.isFinite(T) ||
        !Number.isFinite(RH)
    ) {
        return null;
    }

    // Below the usual heat-index range,
    // apparent temperature stays close to ambient temperature.
    if (T < 80) {

        const simpleHI =
            0.5 * (
                T +
                61.0 +
                ((T - 68.0) * 1.2) +
                (RH * 0.094)
            );

        const averagedHI =
            (simpleHI + T) / 2;

        return (averagedHI - 32) * 5 / 9;
    }

    let HI =
        -42.379 +
        (2.04901523 * T) +
        (10.14333127 * RH) -
        (0.22475541 * T * RH) -
        (0.00683783 * T * T) -
        (0.05481717 * RH * RH) +
        (0.00122874 * T * T * RH) +
        (0.00085282 * T * RH * RH) -
        (0.00000199 * T * T * RH * RH);

    // Low humidity adjustment
    if (
        RH < 13 &&
        T >= 80 &&
        T <= 112
    ) {

        const adjustment =
            ((13 - RH) / 4) *
            Math.sqrt(
                (17 - Math.abs(T - 95)) / 17
            );

        HI -= adjustment;
    }

    // High humidity adjustment
    else if (
        RH > 85 &&
        T >= 80 &&
        T <= 87
    ) {

        const adjustment =
            ((RH - 85) / 10) *
            ((87 - T) / 5);

        HI += adjustment;
    }

    return (HI - 32) * 5 / 9;
}


function getHeatStressRisk(heatIndexC) {

    if (heatIndexC === null) {
        return null;
    }

    if (heatIndexC < 32) {

        return {
            level: 'Low',
            color: '#22c55e',
            icon: 'fa-circle-check',
            recommendation:
                'Drink sufficient water and stay hydrated.'
        };
    }

    if (heatIndexC < 39) {

        return {
            level: 'Moderate',
            color: '#eab308',
            icon: 'fa-triangle-exclamation',
            recommendation:
                'Drink sufficient water and take regular breaks.'
        };
    }

    if (heatIndexC < 41) {

        return {
            level: 'High',
            color: '#f97316',
            icon: 'fa-temperature-high',
            recommendation:
                'Avoid prolonged outdoor activity during peak heat and take frequent breaks.'
        };
    }

    return {
        level: 'Extreme',
        color: '#ef4444',
        icon: 'fa-circle-exclamation',
        recommendation:
            'Avoid prolonged outdoor activity, stay hydrated and remain in a cool or shaded place.'
    };
}


function renderHeatStress(
    temperature,
    feelsLike,
    humidity
) {

    const container =
        document.getElementById(
            'heat-stress-container'
        );

    if (!container) {
        return;
    }


    const tempC =
        Number(temperature);

    const feelsLikeC =
        Number(feelsLike);

    const humidityValue =
        Number(humidity);


    // Missing data
    if (
        !Number.isFinite(tempC) ||
        !Number.isFinite(humidityValue)
    ) {

        container.innerHTML = `
            <div class="p-3 rounded-xl bg-[#131521] border border-[#262a40]">

                <div class="flex items-center gap-2 text-orange-400">
                    <i class="fa-solid fa-circle-exclamation"></i>

                    <span class="text-sm font-medium">
                        Heat stress data unavailable
                    </span>
                </div>

                <p class="text-xs text-gray-500 mt-1">
                    Temperature or humidity data is missing.
                </p>

            </div>
        `;

        return;
    }


    const heatIndex =
        calculateHeatIndexCelsius(
            tempC,
            humidityValue
        );


    if (heatIndex === null) {

        container.innerHTML = `
            <p class="text-sm text-gray-400">
                Heat stress calculation unavailable.
            </p>
        `;

        return;
    }


    const risk =
        getHeatStressRisk(heatIndex);


    if (!risk) {
        return;
    }


    const safeHeatIndex =
        heatIndex.toFixed(1);

    const safeTemperature =
        tempC.toFixed(1);

    const safeFeelsLike =
        Number.isFinite(feelsLikeC)
            ? feelsLikeC.toFixed(1)
            : '--';

    const safeHumidity =
        humidityValue.toFixed(0);


    // Gauge: map 20–50°C to 0–100%
    const gaugePercent =
        Math.min(
            100,
            Math.max(
                5,
                ((heatIndex - 20) / 30) * 100
            )
        );


    container.innerHTML = `

        <!-- Main Heat Index -->
        <div class="flex items-center justify-between gap-4">

            <!-- Circular indicator -->
            <div
                class="relative w-24 h-24 rounded-full shrink-0 flex items-center justify-center"
                style="
                    background:
                    conic-gradient(
                        ${risk.color} ${gaugePercent}%,
                        #262a40 ${gaugePercent}% 100%
                    );
                "
            >

                <div
                    class="absolute inset-[7px] rounded-full bg-[#1b1f30] flex flex-col items-center justify-center"
                >

                    <span class="text-[9px] text-gray-500 uppercase">
                        Heat Index
                    </span>

                    <span class="text-2xl font-extrabold text-white">
                        ${safeHeatIndex}°
                    </span>

                </div>

            </div>


            <!-- Risk -->
            <div class="flex-1">

                <p class="text-xs text-gray-500 uppercase tracking-wider">
                    Risk Level
                </p>

                <div class="flex items-center gap-2 mt-1">

                    <i
                        class="fa-solid ${risk.icon}"
                        style="color:${risk.color};"
                    ></i>

                    <span
                        class="font-bold text-lg"
                        style="color:${risk.color};"
                    >
                        ${risk.level}
                    </span>

                </div>

            </div>

        </div>


        <!-- Metrics -->
        <div class="grid grid-cols-3 gap-2 mt-4">

            <div class="bg-[#131521] border border-[#262a40] rounded-xl p-2.5 text-center">

                <p class="text-[10px] text-gray-500">
                    Temperature
                </p>

                <p class="text-sm font-bold text-white mt-1">
                    ${safeTemperature}°C
                </p>

            </div>


            <div class="bg-[#131521] border border-[#262a40] rounded-xl p-2.5 text-center">

                <p class="text-[10px] text-gray-500">
                    Feels Like
                </p>

                <p class="text-sm font-bold text-white mt-1">
                    ${safeFeelsLike}°C
                </p>

            </div>


            <div class="bg-[#131521] border border-[#262a40] rounded-xl p-2.5 text-center">

                <p class="text-[10px] text-gray-500">
                    Humidity
                </p>

                <p class="text-sm font-bold text-white mt-1">
                    ${safeHumidity}%
                </p>

            </div>

        </div>


        <!-- Recommendation -->
        <div
            class="mt-4 p-3 rounded-xl border"
            style="
                border-color:${risk.color}55;
                background:${risk.color}10;
            "
        >

            <div class="flex items-start gap-2">

                <i
                    class="fa-solid fa-shield-heart mt-0.5"
                    style="color:${risk.color};"
                ></i>

                <div>

                    <p class="text-[10px] text-gray-500 uppercase tracking-wider">
                        Safety Recommendation
                    </p>

                    <p class="text-xs text-gray-300 mt-1 leading-relaxed">
                        ${escapeHtml(risk.recommendation)}
                    </p>

                </div>

            </div>

        </div>
     `;

    }

    // ==========================================
// FARMER WEATHER ADVISORY
// ==========================================

const farmerCrops = [
    "Wheat",
    "Cotton",
    "Groundnut",
    "Bajra",
    "Cumin",
    "Castor",
    "Mustard",
    "Potato",
    "Onion"
];

const farmerCropProfiles = {

    Wheat: {
        irrigationTemp: 18,
        heatStress: 32,
        wetHumidity: 80,
        sprayWind: 15
    },

    Cotton: {
        irrigationTemp: 30,
        heatStress: 38,
        wetHumidity: 82,
        sprayWind: 15
    },

    Groundnut: {
        irrigationTemp: 30,
        heatStress: 36,
        wetHumidity: 80,
        sprayWind: 14
    },

    Bajra: {
        irrigationTemp: 32,
        heatStress: 38,
        wetHumidity: 82,
        sprayWind: 16
    },

    Cumin: {
        irrigationTemp: 24,
        heatStress: 30,
        wetHumidity: 75,
        sprayWind: 12
    },

    Castor: {
        irrigationTemp: 31,
        heatStress: 37,
        wetHumidity: 82,
        sprayWind: 15
    },

    Mustard: {
        irrigationTemp: 22,
        heatStress: 30,
        wetHumidity: 78,
        sprayWind: 14
    },

    Potato: {
        irrigationTemp: 24,
        heatStress: 30,
        wetHumidity: 80,
        sprayWind: 14
    },

    Onion: {
        irrigationTemp: 27,
        heatStress: 32,
        wetHumidity: 80,
        sprayWind: 14
    }
};


function initFarmerAdvisory() {

    const citySelect =
        document.getElementById('farmer-city');

    const cropSelect =
        document.getElementById('farmer-crop');

    if (!citySelect || !cropSelect) {
        return;
    }

    // Districts
    citySelect.innerHTML = `
        <option value="">Select District</option>
    `;

    gujaratDistricts.forEach(district => {

        const option =
            document.createElement('option');

        option.value = district;
        option.textContent = district;

        citySelect.appendChild(option);
    });

    // Crops
    cropSelect.innerHTML = `
        <option value="">Select Crop</option>
    `;

    farmerCrops.forEach(crop => {

        const option =
            document.createElement('option');

        option.value = crop;
        option.textContent = crop;

        cropSelect.appendChild(option);
    });

    // Default current city
    if (currentActiveCity) {
        citySelect.value = currentActiveCity;
    } else {
        citySelect.value = 'Mahesana';
    }

    cropSelect.value = 'Wheat';

    citySelect.addEventListener(
        'change',
        async () => {

            const city =
                citySelect.value;

            if (!city) return;

            const cached =
                liveDashboardCache[city];

            if (cached) {
                renderFarmerAdvisory(
                    cached,
                    cropSelect.value
                );
                return;
            }

            const content =
                document.getElementById(
                    'farmer-advisory-content'
                );

            if (content) {
                content.innerHTML = `
                    <div class="p-4 rounded-xl bg-[#131521] border border-[#262a40] text-gray-400">
                        <i class="fa-solid fa-spinner fa-spin text-blue-400 mr-2"></i>
                        Loading weather data for ${escapeHtml(city)}...
                    </div>
                `;
            }

            await fetchWeatherData(
                `/api/weather?city=${encodeURIComponent(city)}`
            );

            const latest =
                liveDashboardCache[city];

            if (latest) {
                renderFarmerAdvisory(
                    latest,
                    cropSelect.value
                );
            }
        }
    );

    cropSelect.addEventListener(
        'change',
        () => {

            const city =
                citySelect.value;

            const crop =
                cropSelect.value;

            if (!city || !crop) return;

            const data =
                liveDashboardCache[city];

            if (data) {
                renderFarmerAdvisory(
                    data,
                    crop
                );
            }
        }
    );

    // Initial advisory
    const initialData =
        liveDashboardCache[currentActiveCity];

    if (initialData) {
        renderFarmerAdvisory(
            initialData,
            'Wheat'
        );
    }
}


function renderFarmerAdvisory(
    data,
    crop
) {

    const content =
        document.getElementById(
            'farmer-advisory-content'
        );

    if (!content) return;

    if (!crop) {

        content.innerHTML = `
            <div class="p-4 rounded-xl bg-[#131521] border border-[#262a40] text-gray-500">
                Please select a crop.
            </div>
        `;

        return;
    }

    const current =
        data?.current
            ? data.current
            : data;

    const profile =
        farmerCropProfiles[crop];

    if (
        !current ||
        !profile
    ) {

        content.innerHTML = `
            <div class="p-4 rounded-xl bg-[#131521] border border-red-500/20 text-red-400">
                Weather data is unavailable for this advisory.
            </div>
        `;

        return;
    }

    const temperature =
        Number(current?.main?.temp);

    const humidity =
        Number(current?.main?.humidity);

    const wind =
        Number(current?.wind?.speed);

    const forecastList =
        data?.forecast?.list || [];

    const rainProbability =
        forecastList.length
            ? Math.max(
                ...forecastList
                    .slice(0, 6)
                    .map(item =>
                        Number(item?.pop || 0)
                    )
              )
            : 0;

    const weatherMain =
        String(
            current?.weather?.[0]?.main || ''
        ).toLowerCase();

    const isRain =
        weatherMain.includes('rain') ||
        weatherMain.includes('thunder');

    // ------------------------------------------
    // STATUS CALCULATION
    // ------------------------------------------

    let riskScore = 0;

    if (
        rainProbability >= 0.70 ||
        isRain
    ) {
        riskScore += 2;
    }

    if (
        Number.isFinite(temperature) &&
        temperature >= profile.heatStress
    ) {
        riskScore += 2;
    }

    if (
        Number.isFinite(humidity) &&
        humidity >= profile.wetHumidity
    ) {
        riskScore += 1;
    }

    if (
        Number.isFinite(wind) &&
        wind >= profile.sprayWind
    ) {
        riskScore += 1;
    }

    let status =
        'Good';

    let statusColor =
        'text-green-400';

    let statusBg =
        'bg-green-500/10 border-green-500/30';

    if (riskScore >= 4) {

        status = 'Risk';

        statusColor =
            'text-red-400';

        statusBg =
            'bg-red-500/10 border-red-500/30';

    } else if (riskScore >= 2) {

        status = 'Caution';

        statusColor =
            'text-yellow-400';

        statusBg =
            'bg-yellow-500/10 border-yellow-500/30';
    }

    // ------------------------------------------
    // IRRIGATION
    // ------------------------------------------

    let irrigation =
        'Normal irrigation planning is reasonable.';

    if (
        rainProbability >= 0.70 ||
        isRain
    ) {

        irrigation =
            'Rain is likely. Consider delaying irrigation to avoid unnecessary watering.';

    } else if (
        Number.isFinite(temperature) &&
        temperature >= profile.irrigationTemp
    ) {

        irrigation =
            'Warm conditions may increase water demand. Check soil moisture before irrigation.';

    }

    // ------------------------------------------
    // RAIN RISK
    // ------------------------------------------

    let rainRisk =
        'Low';

    let rainColor =
        'text-green-400';

    if (rainProbability >= 0.85) {

        rainRisk =
            'High';

        rainColor =
            'text-red-400';

    } else if (
        rainProbability >= 0.70
    ) {

        rainRisk =
            'Moderate';

        rainColor =
            'text-yellow-400';
    }

    // ------------------------------------------
    // TEMPERATURE STRESS
    // ------------------------------------------

    let temperatureStress =
        'Low';

    let tempColor =
        'text-green-400';

    if (
        Number.isFinite(temperature) &&
        temperature >= profile.heatStress
    ) {

        temperatureStress =
            'High';

        tempColor =
            'text-red-400';

    } else if (
        Number.isFinite(temperature) &&
        temperature >= profile.heatStress - 3
    ) {

        temperatureStress =
            'Moderate';

        tempColor =
            'text-yellow-400';
    }

    // ------------------------------------------
    // CROP PROTECTION
    // ------------------------------------------

    let protection =
        'Continue normal field monitoring.';

    if (
        rainProbability >= 0.70
    ) {

        protection =
            'Monitor fields for waterlogging, fungal pressure and excess moisture after rain.';

    } else if (
        Number.isFinite(temperature) &&
        temperature >= profile.heatStress
    ) {

        protection =
            'Protect the crop from heat stress and monitor for signs of moisture loss.';

    } else if (
        Number.isFinite(humidity) &&
        humidity >= profile.wetHumidity
    ) {

        protection =
            'High humidity may increase disease pressure. Inspect crop regularly.';

    }

    // ------------------------------------------
    // SPRAYING SUITABILITY
    // ------------------------------------------

    let spraying =
        'Generally suitable based on current weather.';

    let sprayColor =
        'text-green-400';

    if (
        isRain ||
        rainProbability >= 0.70
    ) {

        spraying =
            'Not suitable now because rain is likely.';

        sprayColor =
            'text-red-400';

    } else if (
        Number.isFinite(wind) &&
        wind >= profile.sprayWind
    ) {

        spraying =
            'Use caution: wind is relatively strong for spraying.';

        sprayColor =
            'text-yellow-400';

    } else if (
        Number.isFinite(temperature) &&
        temperature >= profile.heatStress
    ) {

        spraying =
            'Use caution during high heat. Prefer cooler conditions if practical.';

        sprayColor =
            'text-yellow-400';
    }

    // ------------------------------------------
    // RENDER
    // ------------------------------------------

    content.innerHTML = `

        <!-- Status -->
        <div class="p-4 rounded-xl border ${statusBg} mb-4">

            <div class="flex items-center justify-between gap-3">

                <div>

                    <p class="text-[10px] text-gray-500 uppercase tracking-wider">
                        Advisory Status
                    </p>

                    <h4 class="text-xl font-bold ${statusColor} mt-1">
                        ${status}
                    </h4>

                </div>

                <i class="fa-solid fa-seedling ${statusColor} text-2xl"></i>

            </div>

            <p class="text-xs text-gray-400 mt-2">
                ${escapeHtml(current?.name || currentActiveCity)} •
                ${escapeHtml(crop)}
            </p>

        </div>

        <!-- Live Conditions -->
        <div class="grid grid-cols-3 gap-2 mb-4">

            <div class="bg-[#131521] border border-[#262a40] rounded-xl p-2.5 text-center">

                <p class="text-[10px] text-gray-500">
                    Temp
                </p>

                <p class="text-sm font-bold text-white mt-1">
                    ${
                        Number.isFinite(temperature)
                            ? Math.round(temperature) + '°C'
                            : '--'
                    }
                </p>

            </div>

            <div class="bg-[#131521] border border-[#262a40] rounded-xl p-2.5 text-center">

                <p class="text-[10px] text-gray-500">
                    Humidity
                </p>

                <p class="text-sm font-bold text-white mt-1">
                    ${
                        Number.isFinite(humidity)
                            ? Math.round(humidity) + '%'
                            : '--'
                    }
                </p>

            </div>

            <div class="bg-[#131521] border border-[#262a40] rounded-xl p-2.5 text-center">

                <p class="text-[10px] text-gray-500">
                    Rain Risk
                </p>

                <p class="text-sm font-bold ${rainColor} mt-1">
                    ${rainRisk}
                </p>

            </div>

        </div>

        <!-- Advisory Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

            <div class="bg-[#131521] border border-[#262a40] rounded-xl p-3">
                <div class="flex items-center gap-2 mb-1">
                    <i class="fa-solid fa-droplet text-blue-400 text-xs"></i>
                    <span class="text-xs font-semibold text-white">
                        Irrigation
                    </span>
                </div>

                <p class="text-[11px] text-gray-400 leading-relaxed">
                    ${escapeHtml(irrigation)}
                </p>
            </div>

            <div class="bg-[#131521] border border-[#262a40] rounded-xl p-3">
                <div class="flex items-center gap-2 mb-1">
                    <i class="fa-solid fa-temperature-high ${tempColor} text-xs"></i>
                    <span class="text-xs font-semibold text-white">
                        Temperature Stress
                    </span>
                </div>

                <p class="text-[11px] ${tempColor}">
                    ${temperatureStress}
                </p>
            </div>

            <div class="bg-[#131521] border border-[#262a40] rounded-xl p-3">
                <div class="flex items-center gap-2 mb-1">
                    <i class="fa-solid fa-shield-halved text-purple-400 text-xs"></i>
                    <span class="text-xs font-semibold text-white">
                        Crop Protection
                    </span>
                </div>

                <p class="text-[11px] text-gray-400 leading-relaxed">
                    ${escapeHtml(protection)}
                </p>
            </div>

            <div class="bg-[#131521] border border-[#262a40] rounded-xl p-3">
                <div class="flex items-center gap-2 mb-1">
                    <i class="fa-solid fa-spray-can-sparkles ${sprayColor} text-xs"></i>
                    <span class="text-xs font-semibold text-white">
                        Spraying
                    </span>
                </div>

                <p class="text-[11px] ${sprayColor} leading-relaxed">
                    ${escapeHtml(spraying)}
                </p>
            </div>

        </div>
    `;
}