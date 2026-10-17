@extends('layouts.app')

@section('title', 'Arah Kiblat Presisi - SiniKajian')

@section('content')
<div class="max-w-md mx-auto px-4 pt-4 pb-12 flex flex-col items-center">
  
  <!-- Header Text -->
  <div class="text-center mb-6">
    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 text-emerald-800 text-xs font-semibold mb-2">
      <span class="w-2 h-2 rounded-full bg-emerald-600 animate-pulse"></span>
      Sensor Orientasi & Astronomis
    </div>
    <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Kompas Arah Kiblat</h1>
    <p class="text-xs text-slate-500 mt-1 max-w-xs mx-auto">Arahkan bagian atas ponsel ke simbol Ka'bah hingga kompas berubah hijau.</p>
  </div>

  <!-- Compass Dial Card -->
  <div class="w-full bg-white rounded-3xl p-6 shadow-sm border border-slate-200/80 flex flex-col items-center relative overflow-hidden">
    
    <!-- Azimuth Status Indicator -->
    <div id="qiblaBadge" class="mb-5 px-4 py-1.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700 transition-all">
      Mencari Arah Kiblat...
    </div>

    <!-- Compass Dial Container -->
    <div class="relative w-64 h-64 flex items-center justify-center my-2">
      
      <!-- Outer Dial (Fixed Ring with Degree Marks) -->
      <div class="absolute inset-0 rounded-full border-4 border-slate-100 shadow-inner flex items-center justify-center">
        <!-- Degree Mark Ticks -->
        <div class="absolute top-2 text-[10px] font-bold text-red-500">U</div>
        <div class="absolute right-2 text-[10px] font-bold text-slate-400">T</div>
        <div class="absolute bottom-2 text-[10px] font-bold text-slate-400">S</div>
        <div class="absolute left-2 text-[10px] font-bold text-slate-400">B</div>
      </div>

      <!-- Rotating Compass Disc -->
      <div id="compassDisc" class="absolute inset-4 rounded-full bg-gradient-to-br from-slate-50 to-slate-100/50 border border-slate-200 flex items-center justify-center transition-transform duration-200 ease-out shadow-sm">
        
        <!-- Ka'bah Target Marker -->
        <div id="qiblaTargetPin" class="absolute top-2 flex flex-col items-center transform -translate-y-1">
          <div class="w-7 h-7 rounded-lg bg-slate-900 border-2 border-amber-400 flex items-center justify-center shadow-md">
            <span class="text-amber-400 text-[10px] font-bold">🕋</span>
          </div>
          <div class="w-1 h-3 bg-amber-400 rounded-full mt-0.5"></div>
        </div>

        <!-- Center Needle Pivot -->
        <div class="w-12 h-12 rounded-full bg-white border border-slate-200 shadow-md flex items-center justify-center z-10">
          <div class="w-4 h-4 rounded-full bg-emerald-800"></div>
        </div>

        <!-- North Red Needle -->
        <div class="absolute top-6 bottom-1/2 w-1 bg-gradient-to-t from-transparent to-red-500 rounded-full"></div>
      </div>
    </div>

    <!-- Degrees Info Grid -->
    <div class="w-full grid grid-cols-2 gap-3 mt-6 pt-5 border-t border-slate-100 text-center">
      <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
        <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider block">Sudut Kiblat</span>
        <span id="qiblaAngleText" class="text-lg font-black text-slate-900 mt-0.5 block">--°</span>
      </div>
      <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
        <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider block">Arah Perangkat</span>
        <span id="headingAngleText" class="text-lg font-black text-slate-900 mt-0.5 block">--°</span>
      </div>
    </div>

    <!-- Distance to Makkah -->
    <div class="mt-4 flex items-center gap-1.5 text-xs text-slate-500 font-medium">
      <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
      Jarak ke Ka'bah: <span id="distKaabaText" class="font-bold text-slate-800 font-mono">-- km</span>
    </div>
  </div>

  <!-- Sensor Calibration & Action Card -->
  <div class="w-full mt-4 p-4 rounded-2xl bg-white border border-slate-200/80 shadow-sm flex items-center justify-between gap-3">
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-800 flex items-center justify-center shrink-0">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
      </div>
      <div>
        <p class="text-xs font-bold text-slate-800">Kalibrasi Kompas</p>
        <p class="text-[11px] text-slate-500">Putar ponsel membentuk angka 8 bila sensor tidak stabil.</p>
      </div>
    </div>
    <button id="btnRequestSensor" onclick="requestSensorAccess()" class="px-3.5 py-2 rounded-xl bg-emerald-800 text-white text-xs font-bold hover:bg-emerald-900 active:scale-95 transition-all shrink-0">
      Mulai Sensor
    </button>
  </div>
</div>

<script>
  let qiblaAzimuth = 295.2; // default Jakarta
  let currentHeading = 0;
  const KAABA_LAT = 21.4225;
  const KAABA_LON = 39.8262;

  document.addEventListener('DOMContentLoaded', () => {
    initQiblaCoords();
  });

  function initQiblaCoords() {
    const saved = JSON.parse(localStorage.getItem('sk_user_loc') || 'null');
    const lat = saved ? saved.lat : -6.2088;
    const lon = saved ? saved.lon : 106.8456;

    qiblaAzimuth = calculateQibla(lat, lon);
    document.getElementById('qiblaAngleText').textContent = Math.round(qiblaAzimuth) + '°';

    const dist = calculateHaversine(lat, lon, KAABA_LAT, KAABA_LON);
    document.getElementById('distKaabaText').textContent = Math.round(dist).toLocaleString('id-ID') + ' km';

    updateQiblaDisc(0);
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
            document.getElementById('btnRequestSensor').textContent = 'Sensor Aktif';
            document.getElementById('btnRequestSensor').classList.add('bg-slate-200', 'text-slate-700', 'cursor-default');
          } else {
            alert('Izin sensor orientasi ditolak.');
          }
        })
        .catch(err => {
          console.error(err);
          window.addEventListener('deviceorientation', handleOrientation, true);
        });
    } else if ('ondeviceorientationabsolute' in window) {
      window.addEventListener('deviceorientationabsolute', handleOrientation, true);
      document.getElementById('btnRequestSensor').textContent = 'Sensor Aktif';
    } else if ('ondeviceorientation' in window) {
      window.addEventListener('deviceorientation', handleOrientation, true);
      document.getElementById('btnRequestSensor').textContent = 'Sensor Aktif';
    } else {
      alert('Sensor kompas tidak terdeteksi pada peramban ini. Menampilkan estimasi azimuth.');
    }
  }

  function handleOrientation(event) {
    let compass = event.webkitCompassHeading || Math.abs(event.alpha - 360);
    if (!compass && compass !== 0) return;

    currentHeading = Math.round(compass);
    document.getElementById('headingAngleText').textContent = currentHeading + '°';
    updateQiblaDisc(currentHeading);
  }

  function updateQiblaDisc(heading) {
    const disc = document.getElementById('compassDisc');
    const badge = document.getElementById('qiblaBadge');
    
    // Rotate disc opposite to heading
    disc.style.transform = `rotate(${-heading}deg)`;

    // Position target pin at qibla angle relative to disc
    const pin = document.getElementById('qiblaTargetPin');
    pin.style.transform = `rotate(${qiblaAzimuth}deg) translateY(-85px) rotate(${-qiblaAzimuth}deg)`;

    const diff = Math.abs((heading - qiblaAzimuth + 540) % 360 - 180);
    if (diff <= 4) {
      badge.textContent = 'Tepat Menghadap Kiblat!';
      badge.className = 'mb-5 px-4 py-1.5 rounded-full text-xs font-bold bg-emerald-600 text-white shadow-md animate-bounce';
      if ('vibrate' in navigator) {
        navigator.vibrate(50);
      }
    } else if (diff <= 15) {
      badge.textContent = 'Mendekati Arah Kiblat';
      badge.className = 'mb-5 px-4 py-1.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800';
    } else {
      badge.textContent = 'Sesuaikan Arah Ponsel';
      badge.className = 'mb-5 px-4 py-1.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700';
    }
  }
</script>
@endsection
