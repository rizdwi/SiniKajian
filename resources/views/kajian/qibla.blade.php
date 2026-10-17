@extends('layouts.app')

@section('title', 'Arah Kiblat - SiniKajian')

@section('content')
<div class="max-w-md mx-auto space-y-4 pb-20 pt-1">
  
  <!-- Header Text (Figma Screen 4) -->
  <header>
    <h1 class="text-xl font-extrabold text-[#1D4D4F] tracking-tight">Arah Kiblat</h1>
    <p class="text-xs text-[#607072] mt-0.5">Temukan arah, hadirkan ketenangan.</p>
  </header>

  <!-- Lokasi Saat Ini Card (Figma Screen 4) -->
  <div class="bg-white rounded-2xl p-3.5 border border-[#E5ECEB] shadow-xs flex items-center justify-between gap-3">
    <div class="flex items-center gap-3 min-w-0">
      <div class="w-9 h-9 rounded-full bg-[#E6F4F1] text-[#1D4D4F] flex items-center justify-center shrink-0">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
      </div>
      <div class="min-w-0">
        <p class="text-[10px] font-extrabold uppercase tracking-wider text-[#607072]">LOKASI SAAT INI</p>
        <p id="qiblaLocationName" class="text-xs font-bold text-[#1C2224] truncate mt-0.5">Dago, Bandung</p>
        <p id="qiblaCoordinates" class="text-[10px] text-[#607072] font-mono truncate">-6.8732° S, 107.6163° E</p>
      </div>
    </div>
    <button onclick="promptChangeLocation()" class="text-xs font-bold text-[#1D4D4F] hover:underline px-2 py-1 shrink-0">
      Ubah
    </button>
  </div>

  <!-- Compass Dial Card (Figma Screen 4) -->
  <div class="w-full bg-white rounded-3xl p-6 shadow-sm border border-[#E5ECEB] flex flex-col items-center relative overflow-hidden">
    
    <!-- Azimuth Status Indicator Badge -->
    <div id="qiblaBadge" class="mb-4 px-4 py-1.5 rounded-full text-xs font-bold bg-[#E6F4F1] text-[#1D4D4F] transition-all">
      Mencari Arah Ka'bah...
    </div>

    <!-- Compass Dial Container -->
    <div class="relative w-64 h-64 flex items-center justify-center my-3">
      
      <!-- Outer Dial (Fixed Ring with Degree Marks) -->
      <div class="absolute inset-0 rounded-full border-2 border-[#E5ECEB] flex items-center justify-center">
        <!-- Degree Mark Ticks -->
        <div class="absolute top-2 text-[10px] font-extrabold text-[#1D4D4F]">U</div>
        <div class="absolute right-2.5 text-[10px] font-bold text-[#607072]">T</div>
        <div class="absolute bottom-2 text-[10px] font-bold text-[#607072]">S</div>
        <div class="absolute left-2.5 text-[10px] font-bold text-[#607072]">B</div>

        <!-- Cardinal degree hints -->
        <span class="absolute top-6 text-[8px] text-[#607072]/60">0°</span>
        <span class="absolute right-6 text-[8px] text-[#607072]/60">90°</span>
        <span class="absolute bottom-6 text-[8px] text-[#607072]/60">180°</span>
        <span class="absolute left-6 text-[8px] text-[#607072]/60">270°</span>
      </div>

      <!-- Rotating Compass Disc -->
      <div id="compassDisc" class="absolute inset-4 rounded-full bg-gradient-to-br from-[#F8FAF9] to-[#E6F4F1]/30 border border-[#E5ECEB] flex items-center justify-center transition-transform duration-200 ease-out">
        
        <!-- Ka'bah Target Marker with Green Orientation Cone -->
        <div id="qiblaTargetPin" class="absolute top-2 flex flex-col items-center transform -translate-y-1">
          <div class="w-8 h-8 rounded-xl bg-slate-900 border-2 border-[#FCD34D] flex items-center justify-center shadow-md">
            <span class="text-amber-300 text-xs font-bold">🕋</span>
          </div>
          <div class="w-1.5 h-4 bg-emerald-500 rounded-full mt-0.5"></div>
        </div>

        <!-- Center Hub with Large Degree Readout (Figma Screen 4) -->
        <div class="w-28 h-28 rounded-full bg-white border border-[#E5ECEB] shadow-sm flex flex-col items-center justify-center z-10 text-center p-2">
          <span id="qiblaAngleText" class="text-xl font-black text-[#1D4D4F] tracking-tight leading-none">295,2°</span>
          <span class="text-[8px] font-extrabold uppercase tracking-wider text-[#607072] mt-1">ARAH KA'BAH</span>
          <span id="headingAngleText" class="text-[9px] text-emerald-700 font-semibold mt-0.5">Arah: 0°</span>
        </div>

        <!-- North Line Pointer -->
        <div class="absolute top-4 bottom-1/2 w-0.5 bg-gradient-to-t from-transparent to-[#1D4D4F] rounded-full"></div>
      </div>
    </div>

    <!-- Direction Description & Ka'bah Distance -->
    <div class="w-full text-center mt-3 pt-3 border-t border-[#E5ECEB]">
      <p class="text-xs font-bold text-[#1C2224]">Dari utara • menuju barat laut</p>
      <p class="text-[11px] text-[#607072] mt-0.5 flex items-center justify-center gap-1">
        <span>Jarak ke Ka'bah (Mekkah):</span>
        <strong id="distKaabaText" class="text-[#1D4D4F] font-mono">-- km</strong>
      </p>
    </div>
  </div>

  <!-- Device Status / Sensor Card (Figma Screen 4) -->
  <div class="bg-white rounded-2xl p-4 border border-[#E5ECEB] shadow-xs flex items-start gap-3">
    <div class="w-10 h-10 rounded-full bg-[#E6F4F1] text-[#1D4D4F] flex items-center justify-center shrink-0 mt-0.5">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
    </div>
    <div class="flex-1 min-w-0">
      <h3 class="text-xs font-bold text-[#1C2224]">Kompas siap digunakan</h3>
      <p class="text-[11px] text-[#607072] mt-0.5 leading-relaxed">
        Pegang ponsel sejajar dengan permukaan datar untuk hasil orientasi yang paling presisi.
      </p>
      <div class="mt-2.5 flex items-center gap-2">
        <button id="btnRequestSensor" onclick="requestSensorAccess()" class="px-3.5 py-1.5 rounded-full bg-[#1D4D4F] text-white text-[11px] font-bold hover:bg-[#153A3B] transition shadow-xs">
          Aktifkan Sensor Gerak
        </button>
        <button onclick="calibrateGuidance()" class="text-[11px] font-semibold text-[#1D4D4F] hover:underline px-2 py-1">
          Kalibrasi kompas
        </button>
      </div>
    </div>
  </div>
</div>

<script>
  let qiblaAzimuth = 295.2; // default
  let currentHeading = 0;
  const KAABA_LAT = 21.4225;
  const KAABA_LON = 39.8262;

  document.addEventListener('DOMContentLoaded', () => {
    initQiblaCoords();
  });

  function initQiblaCoords() {
    const saved = JSON.parse(localStorage.getItem('sk_user_loc') || 'null');
    const lat = saved ? saved.lat : -6.8732;
    const lon = saved ? saved.lon : 107.6163;
    const name = saved ? saved.name : 'Dago, Bandung';

    document.getElementById('qiblaLocationName').textContent = name;
    document.getElementById('qiblaCoordinates').textContent = `${lat.toFixed(4)}° S, ${lon.toFixed(4)}° E`;

    qiblaAzimuth = calculateQibla(lat, lon);
    document.getElementById('qiblaAngleText').textContent = qiblaAzimuth.toFixed(1).replace('.', ',') + '°';

    const dist = calculateHaversine(lat, lon, KAABA_LAT, KAABA_LON);
    document.getElementById('distKaabaText').textContent = Math.round(dist).toLocaleString('id-ID') + ' km';

    updateQiblaDisc(0);
  }

  function promptChangeLocation() {
    const city = prompt('Masukkan nama kota atau wilayah Anda (contoh: Bandung, Jakarta, Surabaya):', 'Bandung');
    if (city) {
      document.getElementById('qiblaLocationName').textContent = city;
      // Approximate fallback
      if (city.toLowerCase().includes('jakarta')) {
        localStorage.setItem('sk_user_loc', JSON.stringify({ lat: -6.2088, lon: 106.8456, name: 'Jakarta' }));
      } else if (city.toLowerCase().includes('surabaya')) {
        localStorage.setItem('sk_user_loc', JSON.stringify({ lat: -7.2575, lon: 112.7521, name: 'Surabaya' }));
      } else {
        localStorage.setItem('sk_user_loc', JSON.stringify({ lat: -6.8732, lon: 107.6163, name: city }));
      }
      initQiblaCoords();
    }
  }

  function calibrateGuidance() {
    alert('Putar ponsel perlahan membentuk pola angka 8 (delapan) di udara untuk mengkalibrasi sensor kompas internal.');
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

  function requestSensorAccess() {
    if (typeof DeviceOrientationEvent !== 'undefined' && typeof DeviceOrientationEvent.requestPermission === 'function') {
      DeviceOrientationEvent.requestPermission()
        .then(response => {
          if (response === 'granted') {
            window.addEventListener('deviceorientation', handleOrientation, true);
            document.getElementById('btnRequestSensor').textContent = 'Sensor Aktif ✓';
            document.getElementById('btnRequestSensor').className = 'px-3.5 py-1.5 rounded-full bg-emerald-600 text-white text-[11px] font-bold cursor-default shadow-xs';
          } else {
            alert('Izin sensor orientasi ditolak oleh peramban.');
          }
        })
        .catch(err => {
          window.addEventListener('deviceorientation', handleOrientation, true);
        });
    } else if ('ondeviceorientationabsolute' in window) {
      window.addEventListener('deviceorientationabsolute', handleOrientation, true);
      document.getElementById('btnRequestSensor').textContent = 'Sensor Aktif ✓';
      document.getElementById('btnRequestSensor').className = 'px-3.5 py-1.5 rounded-full bg-emerald-600 text-white text-[11px] font-bold cursor-default shadow-xs';
    } else if ('ondeviceorientation' in window) {
      window.addEventListener('deviceorientation', handleOrientation, true);
      document.getElementById('btnRequestSensor').textContent = 'Sensor Aktif ✓';
      document.getElementById('btnRequestSensor').className = 'px-3.5 py-1.5 rounded-full bg-emerald-600 text-white text-[11px] font-bold cursor-default shadow-xs';
    } else {
      alert('Sensor kompas tidak terdeteksi pada peramban ini. Kompas menampilkan sudut kalkulasi astronomis.');
    }
  }

  function handleOrientation(event) {
    let compass = event.webkitCompassHeading || Math.abs(event.alpha - 360);
    if (!compass && compass !== 0) return;

    currentHeading = Math.round(compass);
    document.getElementById('headingAngleText').textContent = 'Arah: ' + currentHeading + '°';
    updateQiblaDisc(currentHeading);
  }

  function updateQiblaDisc(heading) {
    const disc = document.getElementById('compassDisc');
    const badge = document.getElementById('qiblaBadge');
    
    disc.style.transform = `rotate(${-heading}deg)`;

    const pin = document.getElementById('qiblaTargetPin');
    pin.style.transform = `rotate(${qiblaAzimuth}deg) translateY(-85px) rotate(${-qiblaAzimuth}deg)`;

    const diff = Math.abs((heading - qiblaAzimuth + 540) % 360 - 180);
    if (diff <= 4) {
      badge.textContent = 'Tepat Menghadap Ka\'bah! ✓';
      badge.className = 'mb-4 px-4 py-1.5 rounded-full text-xs font-extrabold bg-[#1D4D4F] text-white shadow-md animate-bounce';
      if ('vibrate' in navigator) {
        navigator.vibrate(50);
      }
    } else if (diff <= 15) {
      badge.textContent = 'Mendekati Arah Ka\'bah';
      badge.className = 'mb-4 px-4 py-1.5 rounded-full text-xs font-bold bg-[#E6F4F1] text-[#1D4D4F]';
    } else {
      badge.textContent = 'Sesuaikan Arah Ponsel';
      badge.className = 'mb-4 px-4 py-1.5 rounded-full text-xs font-medium bg-[#F8FAF9] text-[#607072] border border-[#E5ECEB]';
    }
  }
</script>
@endsection
