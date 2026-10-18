@extends('layouts.app')

@section('title', 'Arah Kiblat - Majelisku')

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

  <!-- 2. Location Capsule Card (Figma Screen 4: Soft Mint Bar - Auto Updating) -->
  <div class="rounded-2xl bg-[#E8F5F3] border border-[#D1ECE7] p-3 flex items-center justify-between gap-3 shadow-2xs">
    <div class="flex items-center gap-2.5 min-w-0">
      <div id="qiblaGpsPinIcon" class="w-8 h-8 rounded-full bg-[#1D4D4F] text-white flex items-center justify-center shrink-0 shadow-2xs transition-all">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
        </svg>
      </div>
      <div class="min-w-0">
        <div class="flex items-center gap-1.5">
          <h3 id="qiblaLocationName" class="text-xs font-bold text-[#1C2224] truncate">Mendeteksi Lokasi...</h3>
          <span id="gpsLiveBadge" class="hidden w-2 h-2 rounded-full bg-emerald-500 animate-ping" title="GPS Aktif"></span>
        </div>
        <p id="qiblaCoordinates" class="text-[10px] text-[#607072] font-mono truncate">Menghitung koordinat...</p>
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

  <!-- 4. Compass Dial Card (Figma Screen 4 - Auto Live Tracking) -->
  <div class="w-full bg-white rounded-3xl p-5 shadow-xs border border-[#E5ECEB] flex flex-col items-center relative overflow-hidden">
    
    <!-- Compass SVG Visualization with Auto-Gyro & Touch Rotation -->
    <div id="compassTouchContainer" class="relative w-64 h-64 sm:w-72 sm:h-72 flex items-center justify-center my-1 select-none cursor-grab active:cursor-grabbing" style="touch-action: pan-y;">
      <svg id="compassSvg" class="w-full h-full transition-transform duration-100 ease-out" viewBox="0 0 300 300">
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
          
          <!-- Qibla Target Direction Wedge / Sector Cone -->
          <g id="qiblaTargetGroup" transform="rotate(295.2 150 150)">
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
      <div id="statusIconWrap" class="w-7 h-7 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-2xs">
        ✓
      </div>
      <div>
        <h4 id="statusTitle" class="text-xs font-bold text-[#1C2224]">Kompas aktif & mengikuti arah otomatis</h4>
      </div>
    </div>

    <!-- Description Text -->
    <p class="text-[11px] text-[#607072] leading-relaxed">
      GPS dan kompas langsung aktif mengikuti posisi Anda. Letakkan ponsel mendatar untuk arah Ka'bah yang paling presisi.
    </p>

    <!-- Full-Width Pill Action Button: Kalibrasi kompas -->
    <button onclick="calibrateSensor()" id="btnCalibrate" class="w-full py-2.5 rounded-full bg-[#E6F4F2] hover:bg-[#d4ede8] active:scale-98 text-[#1D4D4F] text-xs font-bold flex items-center justify-center gap-2 transition shadow-2xs touch-btn">
      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
      </svg>
      <span>Kalibrasi Ulang Sensor</span>
    </button>
  </div>

</div>

<script>
  let currentLat = -6.8851;
  let currentLon = 107.6136;
  let qiblaAzimuth = 295.2;
  let currentHeading = 0;
  let isDragging = false;
  let dragStartAngle = 0;

  document.addEventListener('DOMContentLoaded', () => {
    // 1. Langsung baca koordinat otomatis dari Geo Engine begitu halaman dibuka
    syncFromGeoEngine();

    // 2. Langsung mulai dengarkan sensor kompas otomatis tanpa perlu klik tombol
    autoStartCompass();

    // 3. Tambahkan touch / drag listener agar bisa diputar halus secara interaktif
    setupInteractiveDrag();
  });

  // Dengarkan pembaruan GPS otomatis dari background
  window.addEventListener('sk:location-updated', (e) => {
    if (e.detail) {
      applyLocationData(e.detail);
    }
  });

  function syncFromGeoEngine() {
    if (window.SiniKajianGeo) {
      const data = window.SiniKajianGeo.getCoords();
      applyLocationData(data);
    } else {
      // Fallback dari cache atau default
      let cached = null;
      try {
        cached = JSON.parse(localStorage.getItem('sk_geo_state') || 'null');
      } catch(e) {}
      applyLocationData({
        lat: cached?.lat || currentLat,
        lon: cached?.lon || currentLon,
        name: cached?.name || 'Lokasi Saya',
        qiblaAzimuth: 295.2,
        kaabaDistance: 7924,
        isGps: false
      });
    }
  }

  function applyLocationData(data) {
    currentLat = data.lat;
    currentLon = data.lon;
    qiblaAzimuth = data.qiblaAzimuth || 295.2;

    document.getElementById('qiblaLocationName').textContent = data.name || 'Lokasi Terdeteksi';
    
    const latStr = `${Math.abs(currentLat).toFixed(4)}° ${currentLat < 0 ? 'LS' : 'LU'}`;
    const lonStr = `${Math.abs(currentLon).toFixed(4)}° ${currentLon < 0 ? 'BB' : 'BT'}`;
    document.getElementById('qiblaCoordinates').textContent = `${latStr}, ${lonStr}`;

    const dist = data.kaabaDistance || 7924;
    document.getElementById('distKaabaText').textContent = dist.toLocaleString('id-ID') + ' km';

    document.getElementById('qiblaAngleText').textContent = qiblaAzimuth.toFixed(1).replace('.', ',') + '°';

    // Putar target wedge & icon Ka'bah ke sudut Azimuth akurat
    const targetGroup = document.getElementById('qiblaTargetGroup');
    if (targetGroup) {
      targetGroup.setAttribute('transform', `rotate(${qiblaAzimuth} 150 150)`);
    }

    if (data.isGps) {
      const liveBadge = document.getElementById('gpsLiveBadge');
      if (liveBadge) liveBadge.classList.remove('hidden');
    }

    updateCompassDisplay(currentHeading);
  }

  // === SENSOR COMPASS LOGIC (robust untuk Android & iOS) ===
  let hasReceivedSensorData = false;
  let sensorActive = false;

  function autoStartCompass() {
    // Reset status
    hasReceivedSensorData = false;
    sensorActive = false;

    // Coba semua metode yang tersedia
    tryStartDeviceOrientation();
    tryStartGenericSensor();

    // Jika setelah 3 detik tidak ada data, tampilkan tombol aktivasi manual
    setTimeout(() => {
      if (!hasReceivedSensorData) {
        showActivateButton();
        updateSensorStatus('menunggu', 'Ketuk tombol di atas untuk aktifkan kompas');
      }
    }, 3000);
  }

  function tryStartDeviceOrientation() {
    if (typeof DeviceOrientationEvent === 'undefined') return;

    // iOS 13+: butuh requestPermission via user gesture
    if (typeof DeviceOrientationEvent.requestPermission === 'function') {
      showActivateButton();
      return;
    }

    // Android/Desktop: langsung pasang listener
    // deviceorientationabsolute = kompas absolut (prioritas)
    window.addEventListener('deviceorientationabsolute', onSensorData, true);
    // deviceorientation = fallback
    window.addEventListener('deviceorientation', onSensorData, true);
  }

  function tryStartGenericSensor() {
    // Generic Sensor API (Chrome Android 67+) - lebih reliable
    try {
      if (typeof AbsoluteOrientationSensor !== 'undefined') {
        const sensor = new AbsoluteOrientationSensor({ frequency: 10 });
        sensor.addEventListener('reading', () => {
          // Quaternion ke heading
          const q = sensor.quaternion;
          if (q) {
            // Hitung yaw dari quaternion
            const yaw = Math.atan2(
              2 * (q[3] * q[2] + q[0] * q[1]),
              1 - 2 * (q[1] * q[1] + q[2] * q[2])
            );
            let heading = (-yaw * 180 / Math.PI + 360) % 360;
            onCompassHeading(heading);
          }
        });
        sensor.addEventListener('error', () => {});
        sensor.start();
      }
    } catch (e) {
      // Generic Sensor tidak tersedia, abaikan
    }
  }

  function onSensorData(event) {
    let compass = null;

    // iOS Safari
    if (event.webkitCompassHeading !== undefined && event.webkitCompassHeading !== null) {
      compass = event.webkitCompassHeading;
    }
    // Android: absolute orientation, alpha = heading langsung
    else if (event.absolute === true && event.alpha !== null && event.alpha !== undefined) {
      compass = event.alpha;
    }
    // Non-absolute: tidak reliable, skip kecuali belum ada data sama sekali
    else if (!hasReceivedSensorData && event.alpha !== null && event.alpha !== undefined) {
      // Jangan pakai, terlalu tidak akurat
      return;
    }

    if (compass === null || compass === undefined || isNaN(compass)) return;
    onCompassHeading(compass);
  }

  function onCompassHeading(heading) {
    hasReceivedSensorData = true;
    sensorActive = true;
    hideActivateButton();

    // Normalisasi 0-360
    heading = ((heading % 360) + 360) % 360;
    currentHeading = Math.round(heading);
    updateCompassDisplay(currentHeading);
    updateSensorStatus('aktif', 'Kompas aktif & mengikuti arah');
  }

  function updateSensorStatus(state, msg) {
    const wrap = document.getElementById('statusIconWrap');
    const title = document.getElementById('statusTitle');
    if (wrap && title) {
      if (state === 'aktif') {
        wrap.textContent = '✓';
        wrap.className = 'w-7 h-7 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-2xs';
      } else if (state === 'menunggu') {
        wrap.textContent = '!';
        wrap.className = 'w-7 h-7 rounded-full bg-amber-500 text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-2xs';
      }
      title.textContent = msg;
    }
  }

  function showActivateButton() {
    const pill = document.getElementById('sensorGuidancePill');
    if (pill && !document.getElementById('btnActivateCompass')) {
      const btn = document.createElement('button');
      btn.id = 'btnActivateCompass';
      btn.className = 'mt-2 px-6 py-2.5 rounded-full bg-[#1D4D4F] text-white text-xs font-bold shadow-md active:scale-95 transition';
      btn.textContent = '📱 Ketuk untuk Aktifkan Kompas';
      btn.onclick = activateCompassManually;
      pill.parentElement.appendChild(btn);
    }
  }

  function hideActivateButton() {
    const btn = document.getElementById('btnActivateCompass');
    if (btn) btn.remove();
  }

  function activateCompassManually() {
    // Dipanggil dari user tap - gesture sudah ada, sensor boleh jalan
    hideActivateButton();
    updateSensorStatus('menunggu', 'Mengaktifkan sensor...');

    if (typeof DeviceOrientationEvent !== 'undefined' && typeof DeviceOrientationEvent.requestPermission === 'function') {
      DeviceOrientationEvent.requestPermission()
        .then(response => {
          if (response === 'granted') {
            window.addEventListener('deviceorientation', onSensorData, true);
            tryStartGenericSensor();
          } else {
            updateSensorStatus('menunggu', 'Izin sensor ditolak');
          }
        })
        .catch(() => {
          window.addEventListener('deviceorientationabsolute', onSensorData, true);
          window.addEventListener('deviceorientation', onSensorData, true);
          tryStartGenericSensor();
        });
    } else {
      window.addEventListener('deviceorientationabsolute', onSensorData, true);
      window.addEventListener('deviceorientation', onSensorData, true);
      tryStartGenericSensor();
    }

    // Cek lagi setelah 3 detik
    setTimeout(() => {
      if (!hasReceivedSensorData) {
        updateSensorStatus('menunggu', 'Sensor tidak merespons. Coba: 1) Aktifkan "Motion sensors" di pengaturan Chrome > Site settings, 2) Pastikan HP punya kompas');
      }
    }, 3000);
  }

  function showSensorError(msg) {
    const statusTitle = document.getElementById('statusTitle');
    if (statusTitle) statusTitle.textContent = msg;
  }

  function calibrateSensor() {
    // Reset dan coba aktifkan ulang semua sensor
    hasReceivedSensorData = false;
    sensorActive = false;
    activateCompassManually();
  }

  function showSensorReadyStatus(msg) {
    const statusWrap = document.getElementById('statusIconWrap');
    const statusTitle = document.getElementById('statusTitle');
    if (statusWrap && statusTitle) {
      statusWrap.textContent = '✓';
      statusWrap.className = 'w-7 h-7 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-2xs';
      statusTitle.textContent = msg || 'Kompas aktif & mengikuti arah';
    }
  }

  // Legacy: dipertahankan untuk kompatibilitas, delegasi ke onSensorData
  function handleOrientation(event) {
    onSensorData(event);
  }

  function updateCompassDisplay(heading) {
    const dial = document.getElementById('rotatingDialGroup');
    const guidancePill = document.getElementById('sensorGuidancePill');
    const guidanceText = document.getElementById('guidanceText');
    if (!dial) return;

    // Putar dial piringan kompas berlawanan dengan arah heading HP
    dial.setAttribute('transform', `rotate(${-heading} 150 150)`);

    // Selisih sudut antara heading ponsel dengan sudut Kiblat
    const diff = Math.abs((heading - qiblaAzimuth + 540) % 360 - 180);

    if (diff <= 5) {
      // Tepat selaras mengarah ke Ka'bah!
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

  function setupInteractiveDrag() {
    const container = document.getElementById('compassTouchContainer');
    if (!container) return;

    function getAngle(e) {
      const rect = container.getBoundingClientRect();
      const cx = rect.left + rect.width / 2;
      const cy = rect.top + rect.height / 2;
      const clientX = e.touches ? e.touches[0].clientX : e.clientX;
      const clientY = e.touches ? e.touches[0].clientY : e.clientY;
      const rad = Math.atan2(clientY - cy, clientX - cx);
      return (rad * 180 / Math.PI + 90 + 360) % 360;
    }

    container.addEventListener('mousedown', (e) => {
      isDragging = true;
      dragStartAngle = getAngle(e) - currentHeading;
    });

    window.addEventListener('mousemove', (e) => {
      if (!isDragging) return;
      currentHeading = Math.round((getAngle(e) - dragStartAngle + 360) % 360);
      updateCompassDisplay(currentHeading);
    });

    window.addEventListener('mouseup', () => { isDragging = false; });

    container.addEventListener('touchstart', (e) => {
      isDragging = true;
      dragStartAngle = getAngle(e) - currentHeading;
    }, { passive: true });

    window.addEventListener('touchmove', (e) => {
      if (!isDragging) return;
      currentHeading = Math.round((getAngle(e) - dragStartAngle + 360) % 360);
      updateCompassDisplay(currentHeading);
    }, { passive: true });

    window.addEventListener('touchend', () => { isDragging = false; });
  }

  function promptChangeLocation() {
    const currentName = document.getElementById('qiblaLocationName').textContent || 'Bandung';
    const city = prompt('Masukkan nama kota atau daerah Anda (contoh: Bandung, Jakarta, Surabaya, Yogyakarta):', currentName);
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

    if (window.SiniKajianGeo) {
      window.SiniKajianGeo.setManualCity(city, newLat, newLon);
    } else {
      applyLocationData({
        lat: newLat,
        lon: newLon,
        name: city,
        isGps: false
      });
    }
  }
</script>
@endsection
