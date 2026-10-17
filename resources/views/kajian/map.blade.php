@extends('layouts.app')

@section('title', 'Peta Kajian Sunnah Terdekat - SiniKajian')

@section('content')
<div class="relative h-[calc(100vh-4.5rem)] w-full overflow-hidden flex flex-col">
  <!-- Top Bar Controls -->
  <div class="absolute top-3 inset-x-3 z-[1000] flex items-center gap-2">
    <div class="flex-1 bg-white/95 backdrop-blur-md rounded-2xl shadow-lg border border-slate-200/80 px-3.5 py-2.5 flex items-center gap-2.5">
      <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
      <input type="text" id="mapSearchInput" placeholder="Cari ustadz, masjid, atau tema..." class="w-full text-xs font-medium text-slate-800 placeholder-slate-400 bg-transparent focus:outline-none">
    </div>
    <button onclick="recenterMap()" title="Lokasi Saya" class="w-11 h-11 bg-white/95 backdrop-blur-md rounded-2xl shadow-lg border border-slate-200/80 flex items-center justify-center text-emerald-800 hover:bg-emerald-50 active:scale-95 transition-all">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
    </button>
  </div>

  <!-- Leaflet Map Container -->
  <div id="fullMap" class="w-full h-full z-0"></div>

  <!-- Bottom Drawer for Selected Kajian -->
  <div id="mapDrawer" class="hidden absolute bottom-4 inset-x-3 z-[1000] bg-white rounded-2xl shadow-2xl border border-slate-200/90 p-4 transition-all animate-slideUp">
    <div class="flex items-start justify-between gap-3">
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2 mb-1">
          <span id="drawerCat" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-800"></span>
          <span id="drawerDay" class="text-[11px] font-semibold text-slate-500"></span>
          <span id="drawerDist" class="text-[11px] font-bold text-emerald-700 ml-auto"></span>
        </div>
        <h3 id="drawerTitle" class="text-sm font-bold text-slate-900 truncate"></h3>
        <p id="drawerUstadz" class="text-xs font-medium text-slate-600 truncate mt-0.5"></p>
        <p id="drawerMasjid" class="text-[11px] text-slate-500 truncate mt-0.5"></p>
      </div>
      <button onclick="closeMapDrawer()" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
      </button>
    </div>
    
    <div class="mt-3 pt-3 border-t border-slate-100 flex items-center gap-2">
      <a id="drawerGmapsBtn" href="#" target="_blank" rel="noopener" class="flex-1 py-2 px-3 rounded-xl bg-emerald-800 text-white text-xs font-bold text-center flex items-center justify-center gap-1.5 hover:bg-emerald-900 transition-colors">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>
        Buka Google Maps
      </a>
      <button id="drawerDetailBtn" class="py-2 px-3 rounded-xl bg-slate-100 text-slate-700 text-xs font-bold hover:bg-slate-200 transition-colors">
        Detail
      </button>
    </div>
  </div>
</div>

<script>
  let mapInstance = null;
  let userMarker = null;
  let markersLayer = null;
  const rawEvents = @json($events);

  document.addEventListener('DOMContentLoaded', () => {
    initMap();
    document.getElementById('mapSearchInput').addEventListener('input', (e) => {
      filterMapMarkers(e.target.value.toLowerCase());
    });
  });

  function initMap() {
    const savedLoc = JSON.parse(localStorage.getItem('sk_user_loc') || 'null');
    const centerLat = savedLoc ? savedLoc.lat : -6.2088;
    const centerLon = savedLoc ? savedLoc.lon : 106.8456;

    mapInstance = L.map('fullMap', {
      zoomControl: false,
      attributionControl: false
    }).setView([centerLat, centerLon], 12);

    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
      maxZoom: 19
    }).addTo(mapInstance);

    markersLayer = L.layerGroup().addTo(mapInstance);

    if (savedLoc) {
      setUserLocationMarker(savedLoc.lat, savedLoc.lon);
    }

    renderMapMarkers(rawEvents);
  }

  function renderMapMarkers(events) {
    markersLayer.clearLayers();
    
    events.forEach(item => {
      if (!item.lat || !item.lon) return;

      const customIcon = L.divIcon({
        className: 'custom-map-pin',
        html: `
          <div class="w-8 h-8 rounded-full bg-emerald-800 text-white flex items-center justify-center shadow-lg border-2 border-white cursor-pointer transform hover:scale-110 transition-transform">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
            </svg>
          </div>
        `,
        iconSize: [32, 32],
        iconAnchor: [16, 32]
      });

      const marker = L.marker([item.lat, item.lon], { icon: customIcon });
      marker.on('click', () => {
        showMapDrawer(item);
      });
      markersLayer.addLayer(marker);
    });
  }

  function filterMapMarkers(keyword) {
    if (!keyword) {
      renderMapMarkers(rawEvents);
      return;
    }
    const filtered = rawEvents.filter(e => 
      (e.title && e.title.toLowerCase().includes(keyword)) ||
      (e.ustadz && e.ustadz.toLowerCase().includes(keyword)) ||
      (e.masjid && e.masjid.toLowerCase().includes(keyword)) ||
      (e.category && e.category.toLowerCase().includes(keyword))
    );
    renderMapMarkers(filtered);
  }

  function showMapDrawer(item) {
    const drawer = document.getElementById('mapDrawer');
    document.getElementById('drawerTitle').textContent = item.title;
    document.getElementById('drawerUstadz').textContent = item.ustadz;
    document.getElementById('drawerMasjid').textContent = item.masjid + (item.address ? ', ' + item.address : '');
    document.getElementById('drawerCat').textContent = item.category || 'Kajian';
    document.getElementById('drawerDay').textContent = (item.day || '') + ' ' + (item.time || '');

    const savedLoc = JSON.parse(localStorage.getItem('sk_user_loc') || 'null');
    if (savedLoc && item.lat && item.lon) {
      const d = haversineDistance(savedLoc.lat, savedLoc.lon, item.lat, item.lon);
      document.getElementById('drawerDist').textContent = d.toFixed(1) + ' km';
    } else {
      document.getElementById('drawerDist').textContent = '';
    }

    const gmapsUrl = item.lat && item.lon 
      ? `https://www.google.com/maps/dir/?api=1&destination=${item.lat},${item.lon}`
      : `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(item.masjid + ' ' + (item.address || ''))}`;
    document.getElementById('drawerGmapsBtn').href = gmapsUrl;

    drawer.classList.remove('hidden');
    mapInstance.panTo([item.lat, item.lon]);
  }

  function closeMapDrawer() {
    document.getElementById('mapDrawer').classList.add('hidden');
  }

  function setUserLocationMarker(lat, lon) {
    if (userMarker) {
      userMarker.setLatLng([lat, lon]);
    } else {
      const userDotIcon = L.divIcon({
        className: 'user-loc-dot',
        html: `
          <div class="relative flex items-center justify-center w-6 h-6">
            <div class="absolute w-6 h-6 rounded-full bg-blue-500/30 animate-ping"></div>
            <div class="w-3.5 h-3.5 rounded-full bg-blue-600 border-2 border-white shadow-md"></div>
          </div>
        `,
        iconSize: [24, 24],
        iconAnchor: [12, 12]
      });
      userMarker = L.marker([lat, lon], { icon: userDotIcon }).addTo(mapInstance);
    }
  }

  function recenterMap() {
    if ('geolocation' in navigator) {
      navigator.geolocation.getCurrentPosition((pos) => {
        const lat = pos.coords.latitude;
        const lon = pos.coords.longitude;
        localStorage.setItem('sk_user_loc', JSON.stringify({ lat, lon, name: 'Lokasi Anda' }));
        setUserLocationMarker(lat, lon);
        mapInstance.flyTo([lat, lon], 13);
      }, (err) => {
        alert('Gagal mendeteksi lokasi GPS: ' + err.message);
      });
    } else {
      alert('Geolokasi tidak didukung peramban.');
    }
  }

  function haversineDistance(lat1, lon1, lat2, lon2) {
    const R = 6371;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
              Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
  }
</script>
@endsection
