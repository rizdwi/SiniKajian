@extends('layouts.app')

@section('title', 'Arah Kiblat - SiniKajian')

@section('content')
<div class="max-w-md mx-auto space-y-3.5 pb-24 pt-1">
  
  <!-- 1. Header (Figma Screen 4) -->
  <header class="flex items-center justify-between">
    <div>
      <h1 class="text-xl font-extrabold text-[#1D4D4F] tracking-tight">Arah Kiblat</h1>
      <p class="text-xs text-[#607072] mt-0.5">Temukan arah, hadirkan ketenangan.</p>
    </div>
    
    <!-- Refresh / Reset Sensor Icon Button -->
    <button onclick="calibrateSensor()" title="Reset dan kalibrasi kompas" class="w-9 h-9 rounded-full border border-[#E5ECEB] bg-white text-[#1D4D4F] flex items-center justify-center hover:bg-[#F8FAF9] active:scale-95 transition shadow-2xs">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
      </svg>
    </button>
  </header>

  <!-- 2. Location Capsule Card (Figma Screen 4: Soft Mint Bar) -->
  <div class="rounded-2xl bg-[#E8F5F3] border border-[#D1ECE7] p-3 flex items-center justify-between gap-3 shadow-2xs">
    <div class="flex items-center gap-2.5 min-w-0">
      <div class="w-8 h-8 rounded-full bg-[#1D4D4F] text-white flex items-center justify-center shrink-0 shadow-2xs">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
        </svg>
      </div>
      <div class="min-w-0">
        <h3 id="qiblaLocationName" class="text-xs font-bold text-[#1C2224] truncate">Dago, Bandung</h3>
        <p id="qiblaCoordinates" class="text-[10px] text-[#607072] font-mono truncate">6.8851° LS, 107.6136° BT</p>
      </div>
    </div>
    <button onclick="promptChangeLocation()" class="text-xs font-bold text-[#1D4D4F] hover:underline px-2 py-1 shrink-0">
      Ubah
    </button>
  </div>

  <!-- 3. Guidance Floating Pill (Figma Screen 4: "Arahkan ponsel ke barat laut") -->
  <div class="flex justify-center">
    <div id="sensorGuidancePill" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-white border border-[#E5ECEB] shadow-2xs text-[11px] font-semibold text-[#1D4D4F] transition-all">
      <svg class="w-3.5 h-3.5 text-[#1D4D4F]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
      </svg>
      <span id="guidanceText">Arahkan ponsel ke barat laut</span>
    </div>
  </div>

  <!-- 4. Compass Dial Card (Figma Screen 4) -->
  <div class="w-full bg-white rounded-3xl p-5 shadow-xs border border-[#E5ECEB] flex flex-col items-center relative overflow-hidden">
    
    <!-- Compass SVG Visualization -->
    <div class="relative w-64 h-64 sm:w-72 sm:h-72 flex items-center justify-center my-1 select-none">
      <svg id="compassSvg" class="w-full h-full transition-transform duration-200 ease-out" viewBox="0 0 300 300">
        <defs>
          <!-- Mint green transparent gradient for the Qibla Wedge/Cone -->
          <radialGradient id="qiblaWedgeGrad" cx="50%" cy="50%" r="50%">
            <stop offset="0%" stop-color="#E6F4F1" stop-opacity="0.1"/>
            <stop offset="60%" stop-color="#A4D3CC" stop-opacity="0.35"/>
            <stop offset="100%" stop-color="#5FABA2" stop-opacity="0.55"/>
          </radialGradient>

          <!-- Shadow filter for Ka'bah Badge -->
          <filter id="badgeShadow" x="-20%" y="-20%" width="140%" height="140%">
            <feDropShadow dx="0" dy="2" stdDeviation="3" flood-color="#000000" flood-opacity="0.18"/>
          </filter>
        </defs>

        <!-- Outer Ring with Dial Marks -->
        <circle cx="150" cy="150" r="138" fill="none" stroke="#E5ECEB" stroke-width="1.5" />
        <circle cx="150" cy="150" r="126" fill="none" stroke="#F0F4F3" stroke-width="1" />

        <!-- Degree Tick Marks around 360 -->
        <g stroke="#A8B7B6" stroke-linecap="round">
          <!-- 36 main ticks generated around the perimeter -->
          <line x1="150" y1="14" x2="150" y2="24" stroke-width="2" stroke="#1D4D4F"/>
          <line x1="150" y1="276" x2="150" y2="286" stroke-width="2" stroke="#A8B7B6"/>
          <line x1="14" y1="150" x2="24" y2="150" stroke-width="2" stroke="#A8B7B6"/>
          <line x1="276" y1="150" x2="286" y2="150" stroke-width="2" stroke="#A8B7B6"/>

          <!-- 30 deg ticks -->
          <line x1="215" y1="37" x2="210" y2="45" stroke-width="1.5"/>
          <line x1="263" y1="85" x2="255" y2="90" stroke-width="1.5"/>
          <line x1="263" y1="215" x2="255" y2="210" stroke-width="1.5"/>
          <line x1="215" y1="263" x2="210" y2="255" stroke-width="1.5"/>
          <line x1="85" y1="263" x2="90" y2="255" stroke-width="1.5"/>
          <line x1="37" y1="215" x2="45" y2="210" stroke-width="1.5"/>
          <line x1="37" y1="85" x2="45" y2="90" stroke-width="1.5"/>
          <line x1="85" y1="37" x2="90" y2="45" stroke-width="1.5"/>

          <!-- Intermediate small ticks -->
          <circle cx="150" cy="150" r="132" stroke="#D1EAE5" stroke-dasharray="2 10.47" stroke-width="1.2" fill="none" />
        </g>

        <!-- Cardinal Letters -->
        <text x="150" y="32" text-anchor="middle" font-size="12" font-weight="800" fill="#1D4D4F">U</text>
        <text x="270" y="154" text-anchor="middle" font-size="11" font-weight="700" fill="#607072">T</text>
        <text x="150" y="274" text-anchor="middle" font-size="11" font-weight="700" fill="#607072">S</text>
        <text x="30" y="154" text-anchor="middle" font-size="11" font-weight="700" fill="#607072">B</text>

        <!-- Rotating Dial Layer (Contains Qibla Target Cone, Ka'bah Badge, and Needle) -->
        <g id="rotatingDialGroup" transform="rotate(0 150 150)">
          
          <!-- Qibla Target Direction Wedge / Sector Cone (~295.2 deg, ~20 deg spread) -->
          <!-- Sweeps from 285° to 305° centered at (150, 150) -->
          <g transform="rotate(295.2 150 150)">
            <path d="M 150 150 L 126 28 A 124 124 0 0 1 174 28 Z" fill="url(#qiblaWedgeGrad)" />
            
            <!-- Ka'bah Icon Badge at Azimuth Rim -->
            <g transform="translate(150, 26) translate(-14, -14)" filter="url(#badgeShadow)">
              <!-- Black Cube -->
              <rect x="0" y="0" width="28" height="28" rx="7" fill="#151A19" stroke="#E5ECEB" stroke-width="1"/>
              <!-- Kiswah Gold Band -->
              <line x1="2" y1="8" x2="26" y2="8" stroke="#FCD34D" stroke-width="2.5" stroke-linecap="round"/>
              <line x1="10" y1="14" x2="18" y2="14" stroke="#FCD34D" stroke-width="1.5" stroke-linecap="round"/>
              <!-- Door of Ka'bah -->
              <rect x="11" y="17" width="6" height="8" rx="1" fill="#FCD34D"/>
            </g>
          </g>

          <!-- Faceted Diamond Compass Needle (Figma Screen 4) -->
          <!-- Top Half: Pointing North/Target -->
          <!-- Left Facet: Darker Spruce Teal -->
          <polygon points="150,150 139,150 150,55" fill="#1D4D4F" />
          <!-- Right Facet: Lighter Teal -->
          <polygon points="150,150 161,150 150,55" fill="#2E7A7D" />

          <!-- Bottom Half: South Needle (Soft Light Gray) -->
          <polygon points="150,150 139,150 150,225" fill="#D1DCDA" />
          <polygon points="150,150 161,150 150,225" fill="#E5ECEB" />

          <!-- Center Pivot Hub -->
          <circle cx="150" cy="150" r="11" fill="#FFFFFF" stroke="#1D4D4F" stroke-width="2.5" />
          <circle cx="150" cy="150" r="4.5" fill="#1D4D4F" />
        </g>
      </svg>
    </div>

    <!-- 5. Degree Readout & Direction (Figma Screen 4) -->
    <div class="w-full text-center mt-2 pt-2 border-t border-[#E5ECEB]/80">
      <span class="text-[10px] font-extrabold tracking-widest text-[#607072] uppercase block">ARAH KIBLAT</span>
      
      <!-- Big Bold Degree Display (e.g. 295,2°) -->
      <div class="my-0.5">
        <span id="qiblaAngleText" class="text-4xl sm:text-5xl font-black text-[#1D4D4F] tracking-tight">295,2°</span>
      </div>

      <p id="qiblaDescriptionText" class="text-xs text-[#607072] font-medium">Dari utara • menuju barat laut</p>
      
      <p class="text-[11px] text-[#607072] mt-1 font-mono">
        Jarak ke Ka'bah: <span id="distKaabaText" class="text-[#1D4D4F] font-bold">7.924 km</span>
      </p>
    </div>
  </div>

  <!-- 6. Sensor Ready & Calibration Card (Figma Screen 4: Bottom Card) -->
  <div class="bg-white rounded-2xl p-4 border border-[#E5ECEB] shadow-xs space-y-3">
    <!-- Top Row: Icon and Title -->
    <div class="flex items-center gap-2.5">
      <div id="statusIconWrap" class="w-7 h-7 rounded-full bg-[#E6F4F1] text-[#1D4D4F] flex items-center justify-center font-bold text-xs shrink-0 border border-[#D1EAE5]">
        ✓
      </div>
      <div>
        <h4 id="statusTitle" class="text-xs font-bold text-[#1C2224]">Kompas siap digunakan</h4>
      </div>
    </div>

    <!-- Description Text -->
    <p class="text-[11px] text-[#607072] leading-relaxed">
      Letakkan ponsel mendatar dan jauhkan dari benda logam. Ikuti arah yang mengarah ke Ka'bah.
    </p>

    <!-- Full-Width Pill Action Button: Kalibrasi kompas -->
    <button onclick="calibrateSensor()" id="btnCalibrate" class="w-full py-2.5 rounded-full bg-[#E6F4F2] hover:bg-[#d4ede8] active:scale-98 text-[#1D4D4F] text-xs font-bold flex items-center justify-center gap-2 transition shadow-2xs touch-btn">
      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
      </svg>
      <span>Kalibrasi kompas</span>
    </button>
  </div>

</div>

<script>
  // Ka'bah Geographical Coordinates (Mecca, Saudi Arabia)
  const KAABA_LAT = 21.4225;
  const KAABA_LON = 39.8262;

  let currentLat = -6.8851;
  let currentLon = 107.6136;
  let qiblaAzimuth = 295.2;
  let currentHeading = 0;
  let hasSensorPermission = false;

  document.addEventListener('DOMContentLoaded', () => {
    initLocationAndQibla();
    autoStartCompass();
  });

  function initLocationAndQibla() {
    try {
      const saved = JSON.parse(localStorage.getItem('sk_user_loc') || 'null');
      if (saved && saved.lat && saved.lon) {
        currentLat = Number(saved.lat);
        currentLon = Number(saved.lon);
        document.getElementById('qiblaLocationName').textContent = saved.name || 'Lokasi Tersimpan';
      } else {
        document.getElementById('qiblaLocationName').textContent = 'Dago, Bandung';
      }
    } catch(e) {
      console.warn('LocalStorage error:', e);
    }

    // Format coordinates label: e.g. 6.8851° LS, 107.6136° BT
    const latStr = `${Math.abs(currentLat).toFixed(4)}° ${currentLat < 0 ? 'LS' : 'LU'}`;
    const lonStr = `${Math.abs(currentLon).toFixed(4)}° ${currentLon < 0 ? 'BB' : 'BT'}`;
    document.getElementById('qiblaCoordinates').textContent = `${latStr}, ${lonStr}`;

    // Calculate exact astronomical Qibla Azimuth
    qiblaAzimuth = calculateQibla(currentLat, currentLon);
    document.getElementById('qiblaAngleText').textContent = qiblaAzimuth.toFixed(1).replace('.', ',') + '°';

    // Calculate distance
    const dist = calculateHaversine(currentLat, currentLon, KAABA_LAT, KAABA_LON);
    document.getElementById('distKaabaText').textContent = Math.round(dist).toLocaleString('id-ID') + ' km';

    updateCompassDisplay(0);
  }

  function calculateQibla(lat, lon) {
    const phiK = KAABA_LAT * Math.PI / 180;
    const lambdaK = KAABA_LON * Math.PI / 180;
    const phi = lat * Math.PI / 180;
    const lambda = lon * Math.PI / 180;

    const numerator = Math.sin(lambdaK - lambda);
    const denominator = Math.cos(phi) * Math.tan(phiK) - Math.sin(phi) * Math.cos(lambdaK - lambda);

    let q = Math.atan2(numerator, denominator) * 180 / Math.PI;
    return (q + 360) % 360;
  }

  function calculateHaversine(lat1, lon1, lat2, lon2) {
    const R = 6371;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
              Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
  }

  function autoStartCompass() {
    if (typeof DeviceOrientationEvent !== 'undefined') {
      if (typeof DeviceOrientationEvent.requestPermission === 'function') {
        // iOS requires user interaction, button will handle it
      } else {
        window.addEventListener('deviceorientationabsolute', handleOrientation, true);
        window.addEventListener('deviceorientation', handleOrientation, true);
      }
    }
  }

  function calibrateSensor() {
    if (typeof DeviceOrientationEvent !== 'undefined' && typeof DeviceOrientationEvent.requestPermission === 'function') {
      DeviceOrientationEvent.requestPermission()
        .then(response => {
          if (response === 'granted') {
            hasSensorPermission = true;
            window.addEventListener('deviceorientation', handleOrientation, true);
            showSensorReadyStatus();
          } else {
            alert('Izin sensor orientasi kompas diperlukan untuk melacak arah secara real-time.');
          }
        })
        .catch(err => {
          window.addEventListener('deviceorientation', handleOrientation, true);
          showSensorReadyStatus();
        });
    } else {
      // Android / desktop fallback
      window.addEventListener('deviceorientationabsolute', handleOrientation, true);
      window.addEventListener('deviceorientation', handleOrientation, true);
      showSensorReadyStatus();
      alert('Putar ponsel perlahan membentuk pola angka 8 (delapan) di udara untuk mengkalibrasi sensor kompas internal.');
    }
  }

  function showSensorReadyStatus() {
    const statusWrap = document.getElementById('statusIconWrap');
    const statusTitle = document.getElementById('statusTitle');
    if (statusWrap && statusTitle) {
      statusWrap.textContent = '✓';
      statusWrap.className = 'w-7 h-7 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-2xs';
      statusTitle.textContent = 'Sensor Kompas Aktif & Akurat';
    }
  }

  function handleOrientation(event) {
    let compass = null;
    
    // iOS Safari
    if (typeof event.webkitCompassHeading !== 'undefined') {
      compass = event.webkitCompassHeading;
    } 
    // Android Chrome absolute orientation
    else if (event.absolute === true && event.alpha !== null) {
      compass = (360 - event.alpha) % 360;
    } 
    // Generic fallback
    else if (event.alpha !== null) {
      compass = (360 - event.alpha) % 360;
    }

    if (compass === null || isNaN(compass)) return;

    currentHeading = Math.round(compass);
    updateCompassDisplay(currentHeading);
  }

  function updateCompassDisplay(heading) {
    const dial = document.getElementById('rotatingDialGroup');
    const guidancePill = document.getElementById('sensorGuidancePill');
    const guidanceText = document.getElementById('guidanceText');
    if (!dial) return;

    // Rotate compass dial opposite to phone heading
    dial.setAttribute('transform', `rotate(${-heading} 150 150)`);

    // Difference between phone heading and qibla azimuth
    const diff = Math.abs((heading - qiblaAzimuth + 540) % 360 - 180);

    if (diff <= 5) {
      // Exactly pointing to Ka'bah!
      if (guidancePill) {
        guidancePill.className = 'inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full bg-[#1D4D4F] text-white shadow-md text-[11px] font-bold animate-pulse';
        guidanceText.textContent = 'Tepat Menghadap Ka\'bah! ✓';
      }
      if ('vibrate' in navigator && diff <= 3) {
        navigator.vibrate(40);
      }
    } else if (diff <= 20) {
      if (guidancePill) {
        guidancePill.className = 'inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-[#E6F4F1] border border-[#D1ECE7] text-[#1D4D4F] text-[11px] font-bold';
        guidanceText.textContent = 'Mendekati arah Ka\'bah';
      }
    } else {
      if (guidancePill) {
        guidancePill.className = 'inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-white border border-[#E5ECEB] shadow-2xs text-[11px] font-semibold text-[#1D4D4F]';
        guidanceText.textContent = 'Arahkan ponsel ke barat laut';
      }
    }
  }

  function promptChangeLocation() {
    const city = prompt('Masukkan nama kota atau daerah Anda (contoh: Bandung, Jakarta, Surabaya, Yogyakarta):', 'Bandung');
    if (!city) return;

    const lower = city.toLowerCase();
    let newLat = -6.8851;
    let newLon = 107.6136;

    if (lower.includes('jakarta')) {
      newLat = -6.2088; newLon = 106.8456;
    } else if (lower.includes('surabaya')) {
      newLat = -7.2575; newLon = 112.7521;
    } else if (lower.includes('yogyakarta') || lower.includes('jogja')) {
      newLat = -7.7956; newLon = 110.3695;
    } else if (lower.includes('semarang')) {
      newLat = -6.9667; newLon = 110.4167;
    } else if (lower.includes('medan')) {
      newLat = 3.5952; newLon = 98.6722;
    } else if (lower.includes('makassar')) {
      newLat = -5.1477; newLon = 119.4327;
    }

    try {
      localStorage.setItem('sk_user_loc', JSON.stringify({
        name: city,
        lat: newLat,
        lon: newLon
      }));
    } catch(e) {}

    initLocationAndQibla();
  }
</script>
@endsection