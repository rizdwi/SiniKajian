@extends('layouts.app')

@section('title', 'Majelis Terdekat - SiniKajian')

@section('content')
<div class="space-y-3 pb-20">
  <!-- Top Header Area (Figma Screen 3: Majelis Terdekat) -->
  <header class="flex items-center justify-between pt-1">
    <div>
      <h1 class="text-xl font-extrabold text-[#1D4D4F] tracking-tight">Majelis Terdekat</h1>
      <p class="text-xs text-[#607072] mt-0.5">Temukan ruang untuk bertumbuh di sekitarmu.</p>
    </div>
    <button onclick="recenterMap()" title="Lokasi GPS Saya" class="w-10 h-10 rounded-full bg-white border border-[#E5ECEB] text-[#1D4D4F] flex items-center justify-center shadow-xs touch-btn hover:bg-[#E6F4F1] transition">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
    </button>
  </header>

  <!-- Search & Radius Bar -->
  <div class="flex items-center gap-2">
    <div class="relative flex-1">
      <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#607072]">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
      </span>
      <input 
        type="text" 
        id="mapSearchInput" 
        placeholder="Cari masjid, daerah, atau ustadz" 
        class="w-full pl-10 pr-4 py-2.5 bg-white border border-[#E5ECEB] rounded-2xl text-xs sm:text-sm text-[#1C2224] placeholder-[#607072] focus:outline-none focus:ring-2 focus:ring-[#1D4D4F] shadow-xs"
      >
    </div>
    
    <!-- Segmented Control Switcher: Peta vs Daftar (Figma Screen 3) -->
    <div class="inline-flex bg-[#E6F4F1] p-1 rounded-2xl border border-[#D1EAE5] shrink-0 text-xs font-bold">
      <button id="viewToggleMap" onclick="switchView('map')" class="px-3 py-1.5 rounded-xl bg-[#1D4D4F] text-white shadow-xs transition">
        Peta
      </button>
      <button id="viewToggleList" onclick="switchView('list')" class="px-3 py-1.5 rounded-xl text-[#1D4D4F] hover:bg-white/60 transition">
        Daftar
      </button>
    </div>
  </div>

  <!-- Map View Container -->
  <div id="mapViewWrapper" class="relative rounded-3xl overflow-hidden border border-[#E5ECEB] shadow-sm bg-slate-100 h-[62vh]">
    <div id="fullMap" class="w-full h-full z-0"></div>

    <!-- Radius Pill Indicator on Map -->
    <div class="absolute top-3 left-3 z-[1000] bg-white/90 backdrop-blur-md px-3 py-1 rounded-full border border-[#E5ECEB] shadow-xs text-[11px] font-bold text-[#1D4D4F] flex items-center gap-1.5">
      <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
      <span id="radiusLabel">Radius: 15 km</span>
    </div>

    <!-- Floating Bottom Drawer on Map (Selected Venue) -->
    <div id="mapDrawer" class="hidden absolute bottom-3 inset-x-3 z-[1000] bg-white rounded-2xl shadow-xl border border-[#E5ECEB] p-3.5 transition-all animate-slideUp">
      <div class="flex items-start gap-3">
        <div id="drawerFlyerThumb" class="hidden w-14 h-14 rounded-2xl overflow-hidden shrink-0 bg-slate-100 border border-[#E5ECEB]">
          <img id="drawerFlyerImg" src="" alt="Flyer" class="w-full h-full object-cover">
        </div>
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-1.5 mb-1 flex-wrap">
            <span id="drawerCat" class="px-2.5 py-0.5 rounded-full text-[9px] font-bold bg-[#E6F4F1] text-[#1D4D4F]"></span>
            <span id="drawerDay" class="text-[10px] text-[#607072]"></span>
            <span id="drawerDist" class="text-[10px] font-extrabold text-[#1D4D4F] ml-auto bg-[#E6F4F1] px-2 py-0.5 rounded-full"></span>
          </div>
          <h3 id="drawerTitle" class="text-xs sm:text-sm font-bold text-[#1C2224] truncate"></h3>
          <p id="drawerUstadz" class="text-[11px] text-[#607072] truncate mt-0.5"></p>
          <p id="drawerMasjid" class="text-[10px] text-[#607072] truncate mt-0.5"></p>
        </div>
        <button onclick="closeMapDrawer()" class="p-1 rounded-lg text-[#607072] hover:text-[#1C2224]">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
      </div>
      
      <div class="mt-3 pt-2.5 border-t border-[#E5ECEB] flex items-center gap-2">
        <a id="drawerGmapsBtn" href="#" target="_blank" rel="noopener" class="flex-1 py-2 px-3 rounded-full bg-[#1D4D4F] text-white text-xs font-bold text-center flex items-center justify-center gap-1.5 hover:bg-[#153A3B] transition">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
          Petunjuk Arah ↗
        </a>
        <a id="drawerDetailBtn" href="#" class="py-2 px-3 rounded-full bg-[#EDF5F3] text-[#1D4D4F] border border-[#D1ECE7] text-xs font-bold hover:bg-[#DDEEEA] transition text-center flex items-center justify-center">
          Detail ↗
        </a>
      </div>
    </div>
  </div>

  <!-- List View Container (Figma Screen 3: Di sekitarmu) -->
  <div id="listViewWrapper" class="hidden space-y-2.5">
    <div class="flex items-center justify-between px-1">
      <h2 class="text-xs font-bold uppercase tracking-wider text-[#607072]">Di sekitarmu</h2>
      <span id="listCountLabel" class="text-xs font-bold text-[#1D4D4F]"></span>
    </div>
    <div id="nearMosqueCards" class="space-y-2.5"></div>
  </div>
</div>

<script>
  let mapInstance = null;
  let userMarker = null;
  let mapMarkers = [];
  const rawEvents = @json($events);
  let currentActiveView = 'map';

  document.addEventListener('DOMContentLoaded', () => {
    initMap();
    renderListView(rawEvents);
    document.getElementById('mapSearchInput').addEventListener('input', (e) => {
      const q = e.target.value.toLowerCase();
      filterMapMarkers(q);
      filterListView(q);
    });
  });

  function switchView(view) {
    currentActiveView = view;
    const mapWrapper = document.getElementById('mapViewWrapper');
    const listWrapper = document.getElementById('listViewWrapper');
    const btnMap = document.getElementById('viewToggleMap');
    const btnList = document.getElementById('viewToggleList');

    if (view === 'map') {
      mapWrapper.classList.remove('hidden');
      listWrapper.classList.add('hidden');
      btnMap.className = 'px-3 py-1.5 rounded-xl bg-[#1D4D4F] text-white shadow-xs transition';
      btnList.className = 'px-3 py-1.5 rounded-xl text-[#1D4D4F] hover:bg-white/60 transition';
      if (mapInstance) mapInstance.invalidateSize();
    } else {
      mapWrapper.classList.add('hidden');
      listWrapper.classList.remove('hidden');
      btnList.className = 'px-3 py-1.5 rounded-xl bg-[#1D4D4F] text-white shadow-xs transition';
      btnMap.className = 'px-3 py-1.5 rounded-xl text-[#1D4D4F] hover:bg-white/60 transition';
    }
  }

  function initMap() {
    if (!window.google || !window.google.maps) {
      setTimeout(initMap, 500);
      return;
    }

    let centerLat = -6.8851;
    let centerLon = 107.6136;

    if (window.SiniKajianGeo) {
      const coords = window.SiniKajianGeo.getCoords();
      centerLat = coords.lat;
      centerLon = coords.lon;
    } else {
      const savedLoc = JSON.parse(localStorage.getItem('sk_user_coords') || localStorage.getItem('sk_user_loc') || 'null');
      if (savedLoc) {
        centerLat = savedLoc.lat;
        centerLon = savedLoc.lon;
      }
    }

    mapInstance = new google.maps.Map(document.getElementById('fullMap'), {
      center: { lat: centerLat, lng: centerLon },
      zoom: 12,
      zoomControl: true,
      mapTypeControl: true,
      streetViewControl: false,
      fullscreenControl: false
    });

    mapMarkers = [];

    setUserLocationMarker(centerLat, centerLon);
    renderMapMarkers(rawEvents);
  }

  // Dengarkan pembaruan GPS otomatis dari background
  window.addEventListener('sk:location-updated', (e) => {
    if (e.detail && mapInstance) {
      setUserLocationMarker(e.detail.lat, e.detail.lon);
      renderListView(rawEvents);
    }
  });

  function renderMapMarkers(events) {
    mapMarkers.forEach(m => m.setMap(null));
    mapMarkers = [];

    const pinIcon = {
      url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32"><circle cx="16" cy="16" r="14" fill="#1D4D4F" stroke="#fff" stroke-width="2"/><text x="16" y="21" text-anchor="middle" font-size="14">🕌</text></svg>'),
      scaledSize: new google.maps.Size(32, 32)
    };

    events.forEach(item => {
      if (!item.lat || !item.lon) return;

      const marker = new google.maps.Marker({
        position: { lat: parseFloat(item.lat), lng: parseFloat(item.lon) },
        map: mapInstance,
        icon: pinIcon,
        title: item.title
      });
      marker.addListener('click', () => {
        showMapDrawer(item);
      });
      mapMarkers.push(marker);
    });
  }

  function renderListView(events) {
    const container = document.getElementById('nearMosqueCards');
    const countLabel = document.getElementById('listCountLabel');
    countLabel.textContent = `${events.length} Majelis`;

    const savedLoc = JSON.parse(localStorage.getItem('sk_user_loc') || 'null');
    let processed = events.map(e => {
      let d = 0;
      if (savedLoc && e.lat && e.lon) {
        d = haversineDistance(savedLoc.lat, savedLoc.lon, e.lat, e.lon);
      }
      return { ...e, distance: d };
    });

    if (savedLoc) {
      processed.sort((a, b) => a.distance - b.distance);
    }

    container.innerHTML = '';
    processed.forEach(item => {
      const distStr = item.distance > 0 ? (item.distance < 1 ? Math.round(item.distance * 1000) + ' m' : item.distance.toFixed(1) + ' km') : 'Terdekat';
      const flyer = item.poster_url 
        ? `<div class="w-14 h-14 rounded-2xl overflow-hidden shrink-0 bg-slate-100 border border-[#E5ECEB]">
             <img src="${item.poster_url}" alt="Foto" class="w-full h-full object-cover">
           </div>`
        : `<div class="w-14 h-14 rounded-2xl overflow-hidden shrink-0 bg-[#E6F4F1] text-[#1D4D4F] flex items-center justify-center font-bold text-xs border border-[#E5ECEB]">
             <span>MASJID</span>
           </div>`;

      const gmapsUrl = item.lat && item.lon 
        ? `https://www.google.com/maps/dir/?api=1&destination=${item.lat},${item.lon}`
        : `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(item.masjid + ' ' + (item.address || ''))}`;

      const card = document.createElement('div');
      card.className = 'bg-white rounded-2xl p-3.5 border border-[#E5ECEB] shadow-xs flex items-center gap-3 hover:border-[#1D4D4F]/40 transition cursor-pointer';
      card.onclick = () => {
        window.location.href = '/kajian/' + item.id;
      };
      card.innerHTML = `
        ${flyer}
        <div class="flex-1 min-w-0">
          <div class="flex items-center justify-between gap-1 mb-0.5">
            <span class="text-[9px] font-bold text-[#1D4D4F] bg-[#E6F4F1] px-2 py-0.5 rounded-full uppercase">${item.category || 'Sunnah'}</span>
            <span class="text-[10px] font-extrabold text-[#1D4D4F] bg-[#E6F4F1] px-2 py-0.5 rounded-full">${distStr}</span>
          </div>
          <h3 class="text-xs sm:text-sm font-bold text-[#1C2224] truncate">${item.masjid}</h3>
          <p class="text-[11px] text-[#607072] truncate mt-0.5">${item.address || 'Bandung'}</p>
          <p class="text-[10px] text-[#1D4D4F] font-semibold mt-1 flex items-center gap-1">
            <svg class="w-3 h-3 text-[#1D4D4F]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span>${item.day}, ${item.time}</span>
          </p>
        </div>
        <div class="self-center">
          <a href="${gmapsUrl}" target="_blank" rel="noopener" onclick="event.stopPropagation();" class="w-8 h-8 rounded-full bg-[#F8FAF9] hover:bg-[#E6F4F1] border border-[#E5ECEB] text-[#1D4D4F] flex items-center justify-center transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
          </a>
        </div>
      `;
      container.appendChild(card);
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

  function filterListView(keyword) {
    if (!keyword) {
      renderListView(rawEvents);
      return;
    }
    const filtered = rawEvents.filter(e => 
      (e.title && e.title.toLowerCase().includes(keyword)) ||
      (e.ustadz && e.ustadz.toLowerCase().includes(keyword)) ||
      (e.masjid && e.masjid.toLowerCase().includes(keyword)) ||
      (e.category && e.category.toLowerCase().includes(keyword))
    );
    renderListView(filtered);
  }

  function showMapDrawer(item) {
    const drawer = document.getElementById('mapDrawer');
    document.getElementById('drawerTitle').textContent = item.title;
    document.getElementById('drawerUstadz').textContent = item.ustadz;
    document.getElementById('drawerMasjid').textContent = item.masjid + (item.address ? ', ' + item.address : '');
    document.getElementById('drawerCat').textContent = item.category || 'Kajian Sunnah';
    document.getElementById('drawerDay').textContent = (item.day || '') + ' • ' + (item.time || '');

    const drawerFlyerThumb = document.getElementById('drawerFlyerThumb');
    const drawerFlyerImg = document.getElementById('drawerFlyerImg');
    if (item.poster_url) {
      drawerFlyerImg.src = item.poster_url;
      drawerFlyerThumb.classList.remove('hidden');
    } else {
      drawerFlyerThumb.classList.add('hidden');
    }

    const drawerDetailBtn = document.getElementById('drawerDetailBtn');
    if (drawerDetailBtn) {
      drawerDetailBtn.href = '/kajian/' + item.id;
    }

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
    const pos = { lat: parseFloat(lat), lng: parseFloat(lon) };
    if (userMarker) {
      userMarker.setPosition(pos);
    } else {
      userMarker = new google.maps.Marker({
        position: pos,
        map: mapInstance,
        icon: {
          path: google.maps.SymbolPath.CIRCLE,
          scale: 8,
          fillColor: '#1D4D4F',
          fillOpacity: 1,
          strokeColor: '#ffffff',
          strokeWeight: 2
        },
        title: 'Lokasi Saya'
      });
    }
    mapInstance.setCenter(pos);
  }

  function recenterMap() {
    if (window.SiniKajianGeo) {
      window.SiniKajianGeo.requestLiveGps();
      const coords = window.SiniKajianGeo.getCoords();
      setUserLocationMarker(coords.lat, coords.lon);
      mapInstance.flyTo([coords.lat, coords.lon], 14);
    } else if ('geolocation' in navigator) {
      navigator.geolocation.getCurrentPosition((pos) => {
        const lat = pos.coords.latitude;
        const lon = pos.coords.longitude;
        setUserLocationMarker(lat, lon);
        mapInstance.flyTo([lat, lon], 14);
      }, (err) => {
        alert('Gagal mendeteksi lokasi GPS: ' + err.message);
      }, { enableHighAccuracy: true });
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
