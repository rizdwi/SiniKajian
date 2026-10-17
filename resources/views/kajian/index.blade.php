@extends('layouts.app')

@section('title', 'SiniKajian | Jadwal Kajian Sunnah & Waktu Sholat')

@section('content')
<div class="max-w-md mx-auto px-4 pt-4 pb-8 space-y-5">
  
  <!-- Baris Lokasi Pengguna & Penunjuk Presisi -->
  <header class="flex items-center justify-between">
    <div class="flex items-center space-x-2">
      <div class="w-8 h-8 rounded-full bg-emerald-800 text-white flex items-center justify-center shadow-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
        </svg>
      </div>
      <div>
        <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Lokasi Pencarian</p>
        <button type="button" onclick="openLocationModal()" class="flex items-center space-x-1 text-sm font-bold text-slate-900 hover:text-emerald-800">
          <span id="currentLocationName">Jakarta Selatan (Default)</span>
          <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
          </svg>
        </button>
      </div>
    </div>

    <!-- Tombol GPS & Tambah Kajian -->
    <div class="flex items-center space-x-1.5">
      <button type="button" onclick="useGpsLocation()" title="Gunakan Lokasi GPS Saya" class="p-2 bg-white rounded-xl border border-slate-200 text-slate-700 hover:text-emerald-800 hover:border-emerald-700 shadow-sm touch-btn" aria-label="Gunakan GPS">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
      </button>

      <button type="button" onclick="openSubmitModal()" class="flex items-center space-x-1 bg-emerald-800 hover:bg-emerald-900 text-white text-xs font-semibold py-2 px-3 rounded-xl shadow-sm touch-btn" aria-label="Ajukan Kajian">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
        <span>Kirim Info</span>
      </button>
    </div>
  </header>

  <!-- Waktu Sholat Astronomis Surya Presisi -->
  <section class="bg-gradient-to-br from-emerald-900 to-emerald-950 text-white p-4 rounded-2xl shadow-sm border border-emerald-800/80">
    <div class="flex items-center justify-between mb-3 border-b border-emerald-800/60 pb-2">
      <div class="flex items-center space-x-2">
        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
        <span class="text-xs font-semibold text-emerald-200 tracking-wide">WAKTU SHOLAT HARI INI</span>
      </div>
      <div id="nextPrayerCountdown" class="text-xs font-medium text-amber-300">
        Menghitung jadwal...
      </div>
    </div>

    <div class="grid grid-cols-6 gap-1 text-center" id="prayerTimesGrid">
      <div class="bg-emerald-800/50 p-1.5 rounded-lg border border-emerald-700/50" id="prayer-subuh">
        <p class="text-[10px] text-emerald-300">Subuh</p>
        <p class="text-xs font-bold text-white font-mono mt-0.5" id="time-subuh">--:--</p>
      </div>
      <div class="bg-emerald-800/30 p-1.5 rounded-lg border border-emerald-700/30" id="prayer-terbit">
        <p class="text-[10px] text-emerald-400">Terbit</p>
        <p class="text-xs font-medium text-emerald-200 font-mono mt-0.5" id="time-terbit">--:--</p>
      </div>
      <div class="bg-emerald-800/50 p-1.5 rounded-lg border border-emerald-700/50" id="prayer-dzuhur">
        <p class="text-[10px] text-emerald-300">Dzuhur</p>
        <p class="text-xs font-bold text-white font-mono mt-0.5" id="time-dzuhur">--:--</p>
      </div>
      <div class="bg-emerald-800/50 p-1.5 rounded-lg border border-emerald-700/50" id="prayer-ashar">
        <p class="text-[10px] text-emerald-300">Ashar</p>
        <p class="text-xs font-bold text-white font-mono mt-0.5" id="time-ashar">--:--</p>
      </div>
      <div class="bg-emerald-800/50 p-1.5 rounded-lg border border-emerald-700/50" id="prayer-maghrib">
        <p class="text-[10px] text-emerald-300">Maghrib</p>
        <p class="text-xs font-bold text-white font-mono mt-0.5" id="time-maghrib">--:--</p>
      </div>
      <div class="bg-emerald-800/50 p-1.5 rounded-lg border border-emerald-700/50" id="prayer-isya">
        <p class="text-[10px] text-emerald-300">Isya</p>
        <p class="text-xs font-bold text-white font-mono mt-0.5" id="time-isya">--:--</p>
      </div>
    </div>
  </section>

  <!-- Kolom Pencarian & Aksi Filter -->
  <section class="space-y-3">
    <div class="flex items-center space-x-2">
      <div class="relative flex-1">
        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
          </svg>
        </span>
        <input 
          type="text" 
          id="searchInput"
          placeholder="Cari ustadz, kitab, atau masjid..." 
          class="w-full pl-10 pr-9 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:border-emerald-700 shadow-sm"
          oninput="onSearchFilterChange()"
        >
        <button 
          id="clearSearchBtn"
          onclick="clearSearchInput()"
          class="hidden absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600"
          aria-label="Bersihkan pencarian"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>

      <button 
        type="button" 
        onclick="openFilterModal()" 
        class="p-2.5 bg-white border border-slate-200 rounded-xl text-slate-700 hover:text-emerald-800 hover:border-emerald-700 shadow-sm touch-btn flex items-center justify-center"
        aria-label="Buka Filter"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
        </svg>
      </button>
    </div>

    <!-- Quick Chips: Kategori Kajian -->
    <div class="flex items-center space-x-2 overflow-x-auto no-scrollbar py-1 text-xs">
      <button onclick="setCategoryFilter('Semua')" class="category-chip whitespace-nowrap px-3.5 py-1.5 rounded-full font-semibold transition-colors bg-emerald-800 text-white shadow-sm" data-cat="Semua">
        Semua
      </button>
      @foreach($categories as $cat)
      <button onclick="setCategoryFilter('{{ $cat }}')" class="category-chip whitespace-nowrap px-3.5 py-1.5 rounded-full font-medium transition-colors bg-white text-slate-700 border border-slate-200 hover:border-emerald-700 hover:text-emerald-800" data-cat="{{ $cat }}">
        {{ $cat }}
      </button>
      @endforeach
    </div>

    <!-- Quick Chips: Radius Jarak & Hari -->
    <div class="flex items-center justify-between text-xs text-slate-500 pt-0.5">
      <div class="flex items-center space-x-1.5 overflow-x-auto no-scrollbar">
        <span class="text-[11px] font-medium text-slate-400">Radius:</span>
        <button onclick="setRadiusFilter(5)" class="radius-chip px-2.5 py-1 rounded-lg border border-slate-200 bg-white font-medium hover:border-emerald-700" data-radius="5">5 km</button>
        <button onclick="setRadiusFilter(15)" class="radius-chip px-2.5 py-1 rounded-lg border border-slate-200 bg-white font-medium hover:border-emerald-700" data-radius="15">15 km</button>
        <button onclick="setRadiusFilter(25)" class="radius-chip px-2.5 py-1 rounded-lg border border-emerald-700 bg-emerald-50 text-emerald-800 font-bold" data-radius="25">25 km</button>
        <button onclick="setRadiusFilter(999)" class="radius-chip px-2.5 py-1 rounded-lg border border-slate-200 bg-white font-medium hover:border-emerald-700" data-radius="999">Semua</button>
      </div>

      <div class="flex items-center space-x-1">
        <span id="filteredCountText" class="font-bold text-slate-800">{{ $kajians->count() }}</span>
        <span>Kajian</span>
      </div>
    </div>
  </section>

  <!-- Hero Spotlight: Majelis Pilihan Terdekat -->
  <section id="heroSpotlightContainer" class="hidden">
    <div class="bg-gradient-to-r from-emerald-800 to-emerald-900 rounded-2xl p-4 text-white shadow-md relative overflow-hidden border border-emerald-700">
      <div class="flex items-start justify-between">
        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-400 text-slate-900 uppercase tracking-wider">
          Majelis Terdekat
        </span>
        <span id="spotlightDistance" class="text-xs font-semibold text-emerald-200">
          -- km
        </span>
      </div>

      <h2 id="spotlightTitle" class="text-base font-bold mt-2 leading-snug line-clamp-2">
        Memuat majelis terdekat...
      </h2>
      <p id="spotlightSpeaker" class="text-xs text-emerald-100 font-medium mt-1">
        -
      </p>

      <div class="flex items-center space-x-3 mt-3 text-xs text-emerald-200">
        <div class="flex items-center space-x-1">
          <svg class="w-3.5 h-3.5 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
          </svg>
          <span id="spotlightMasjid" class="truncate max-w-[140px]">-</span>
        </div>
        <div class="flex items-center space-x-1">
          <svg class="w-3.5 h-3.5 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          <span id="spotlightTime">-</span>
        </div>
      </div>

      <div class="mt-4 pt-3 border-t border-emerald-700/60 flex items-center justify-between">
        <button id="spotlightDetailBtn" class="text-xs font-semibold text-white underline hover:text-amber-300">
          Buka Detail
        </button>
        <button id="spotlightRouteBtn" class="bg-white text-emerald-900 hover:bg-emerald-50 text-xs font-bold py-1.5 px-3 rounded-lg shadow-sm touch-btn flex items-center space-x-1">
          <svg class="w-3.5 h-3.5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
          </svg>
          <span>Panduan Rute</span>
        </button>
      </div>
    </div>
  </section>

  <!-- Feed Daftar Kajian -->
  <section class="space-y-3" id="kajianCardsList">
    <!-- Di-render oleh script JavaScript secara dinamis dengan kalkulasi Haversine -->
  </section>

  <!-- Empty State (R-27 compliant) -->
  <div id="emptyStateContainer" class="hidden text-center py-12 px-4 bg-white rounded-2xl border border-slate-200">
    <div class="w-12 h-12 mx-auto rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-3">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
      </svg>
    </div>
    <h3 class="text-base font-bold text-slate-800">Tidak ada kajian yang sesuai</h3>
    <p class="text-xs text-slate-500 max-w-xs mx-auto mt-1">
      Coba perbesar radius jarak pencarian atau ubah filter kategori dan hari Anda.
    </p>
    <button onclick="resetAllFilters()" class="mt-4 inline-flex items-center space-x-1.5 bg-emerald-800 hover:bg-emerald-900 text-white text-xs font-semibold py-2 px-4 rounded-xl shadow-sm touch-btn">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
      </svg>
      <span>Reset Semua Filter</span>
    </button>
  </div>

</div>

<!-- Modal Bottom Sheet: Detail Kajian -->
<div id="detailModal" class="hidden fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/60 backdrop-blur-sm p-0 sm:p-4 transition-opacity">
  <div class="bg-white w-full max-w-lg rounded-t-3xl sm:rounded-2xl max-h-[90vh] overflow-y-auto no-scrollbar shadow-2xl p-5 space-y-4">
    <div class="w-12 h-1.5 bg-slate-200 rounded-full mx-auto sm:hidden mb-2"></div>
    
    <div class="flex items-start justify-between border-b border-slate-100 pb-3">
      <div>
        <span id="modalCategory" class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-800">Kategori</span>
        <h3 id="modalTitle" class="text-base font-bold text-slate-900 mt-1.5 leading-snug">Judul Kajian</h3>
        <p id="modalSpeaker" class="text-xs font-medium text-slate-600 mt-0.5">Ustadz</p>
      </div>
      <button onclick="closeDetailModal()" class="text-slate-400 hover:text-slate-600 p-1" aria-label="Tutup Detail">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
      </button>
    </div>

    <!-- Informasi Pelaksanaan -->
    <div class="space-y-2 text-xs">
      <div class="flex items-center space-x-2 text-slate-700">
        <svg class="w-4 h-4 text-emerald-800 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
        </svg>
        <span class="font-bold text-slate-900" id="modalMasjid">Masjid</span>
      </div>

      <div class="flex items-start space-x-2 text-slate-600">
        <svg class="w-4 h-4 text-slate-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
        </svg>
        <span id="modalAddress">Alamat masjid lengkap...</span>
      </div>

      <div class="flex items-center space-x-4 pt-1">
        <div class="flex items-center space-x-1.5 text-slate-700">
          <svg class="w-4 h-4 text-emerald-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
          </svg>
          <span class="font-semibold" id="modalDay">Ahad</span>
        </div>
        <div class="flex items-center space-x-1.5 text-slate-700">
          <svg class="w-4 h-4 text-emerald-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          <span id="modalTime">09:00 WIB</span>
        </div>
        <div class="flex items-center space-x-1 text-slate-700">
          <span class="px-2 py-0.5 rounded bg-slate-100 font-medium" id="modalAudience">Umum</span>
        </div>
      </div>
    </div>

    <!-- Deskripsi Kajian -->
    <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200/80">
      <h4 class="text-xs font-bold text-slate-800 mb-1">Deskripsi & Pokok Bahasan</h4>
      <p id="modalDescription" class="text-xs text-slate-600 leading-relaxed">
        Deskripsi materi kajian sunnah.
      </p>
    </div>

    <!-- Tombol Aksi -->
    <div class="grid grid-cols-2 gap-2 pt-2">
      <button id="modalRouteBtn" class="flex items-center justify-center space-x-1.5 bg-emerald-800 hover:bg-emerald-900 text-white text-xs font-bold py-2.5 px-4 rounded-xl shadow-sm touch-btn">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
        </svg>
        <span>Petunjuk Arah</span>
      </button>

      <button id="modalShareBtn" onclick="shareCurrentKajian()" class="flex items-center justify-center space-x-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold py-2.5 px-4 rounded-xl touch-btn">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
        </svg>
        <span>Bagikan Info</span>
      </button>
    </div>
  </div>
</div>

<!-- Modal: Filter Kajian -->
<div id="filterModal" class="hidden fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/60 backdrop-blur-sm p-0 sm:p-4">
  <div class="bg-white w-full max-w-md rounded-t-3xl sm:rounded-2xl p-5 space-y-4">
    <div class="flex items-center justify-between border-b border-slate-100 pb-2">
      <h3 class="text-sm font-bold text-slate-900">Filter Jadwal Kajian</h3>
      <button onclick="closeFilterModal()" class="text-slate-400 hover:text-slate-600 p-1" aria-label="Tutup Filter">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
      </button>
    </div>

    <!-- Pilihan Hari -->
    <div class="space-y-1.5">
      <label class="text-xs font-bold text-slate-700">Hari Pelaksanaan</label>
      <select id="filterDaySelect" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-xs focus:ring-2 focus:ring-emerald-700">
        <option value="Semua">Semua Hari</option>
        <option value="Ahad">Ahad</option>
        <option value="Senin">Senin</option>
        <option value="Selasa">Selasa</option>
        <option value="Rabu">Rabu</option>
        <option value="Kamis">Kamis</option>
        <option value="Jumat">Jumat</option>
        <option value="Sabtu">Sabtu</option>
      </select>
    </div>

    <!-- Pilihan Jamaah -->
    <div class="space-y-1.5">
      <label class="text-xs font-bold text-slate-700">Peserta Jamaah</label>
      <div class="grid grid-cols-3 gap-2 text-xs">
        <button type="button" onclick="setAudienceFilter('Semua')" class="audience-btn py-2 rounded-xl border border-emerald-700 bg-emerald-50 text-emerald-800 font-bold" data-audience="Semua">Semua</button>
        <button type="button" onclick="setAudienceFilter('Ikhwan')" class="audience-btn py-2 rounded-xl border border-slate-200 bg-white font-medium hover:border-emerald-700" data-audience="Ikhwan">Ikhwan</button>
        <button type="button" onclick="setAudienceFilter('Akhwat')" class="audience-btn py-2 rounded-xl border border-slate-200 bg-white font-medium hover:border-emerald-700" data-audience="Akhwat">Akhwat</button>
      </div>
    </div>

    <div class="pt-2 flex items-center space-x-2">
      <button onclick="resetAllFilters(); closeFilterModal();" class="w-1/2 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl">
        Reset
      </button>
      <button onclick="applyFilters(); closeFilterModal();" class="w-1/2 py-2.5 bg-emerald-800 hover:bg-emerald-900 text-white text-xs font-bold rounded-xl shadow-sm">
        Terapkan
      </button>
    </div>
  </div>
</div>

<!-- Modal: Pilih Kota / Lokasi Geospasial -->
<div id="locationModal" class="hidden fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/60 backdrop-blur-sm p-0 sm:p-4">
  <div class="bg-white w-full max-w-md rounded-t-3xl sm:rounded-2xl p-5 space-y-4 max-h-[85vh] overflow-y-auto no-scrollbar">
    <div class="flex items-center justify-between border-b border-slate-100 pb-2">
      <h3 class="text-sm font-bold text-slate-900">Pilih Lokasi Wilayah</h3>
      <button onclick="closeLocationModal()" class="text-slate-400 hover:text-slate-600 p-1" aria-label="Tutup Lokasi">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
      </button>
    </div>

    <button onclick="useGpsLocation(); closeLocationModal();" class="w-full flex items-center justify-between p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-900 text-xs font-bold">
      <div class="flex items-center space-x-2">
        <svg class="w-4 h-4 text-emerald-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <span>Gunakan Titik GPS Akurat Perangkat</span>
      </div>
      <span>Deteksi</span>
    </button>

    <div class="space-y-1.5 text-xs">
      <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Jabodetabek & Jawa Barat</p>
      <div class="grid grid-cols-2 gap-2">
        <button onclick="setCityLocation('Jakarta Selatan', -6.2615, 106.8106)" class="p-2.5 text-left bg-white border border-slate-200 hover:border-emerald-700 rounded-xl font-medium text-slate-800">Jakarta Selatan</button>
        <button onclick="setCityLocation('Jakarta Pusat', -6.1805, 106.8284)" class="p-2.5 text-left bg-white border border-slate-200 hover:border-emerald-700 rounded-xl font-medium text-slate-800">Jakarta Pusat</button>
        <button onclick="setCityLocation('Bekasi', -6.2383, 106.9756)" class="p-2.5 text-left bg-white border border-slate-200 hover:border-emerald-700 rounded-xl font-medium text-slate-800">Bekasi</button>
        <button onclick="setCityLocation('Bogor', -6.5971, 106.8060)" class="p-2.5 text-left bg-white border border-slate-200 hover:border-emerald-700 rounded-xl font-medium text-slate-800">Bogor</button>
        <button onclick="setCityLocation('Karawang', -6.3040, 107.3075)" class="p-2.5 text-left bg-white border border-slate-200 hover:border-emerald-700 rounded-xl font-medium text-slate-800">Karawang</button>
        <button onclick="setCityLocation('Bandung', -6.9175, 107.6191)" class="p-2.5 text-left bg-white border border-slate-200 hover:border-emerald-700 rounded-xl font-medium text-slate-800">Bandung</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Formulir Ajukan Jadwal Kajian Baru -->
<div id="submitModal" class="hidden fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/60 backdrop-blur-sm p-0 sm:p-4">
  <div class="bg-white w-full max-w-lg rounded-t-3xl sm:rounded-2xl p-5 space-y-4 max-h-[90vh] overflow-y-auto no-scrollbar">
    <div class="flex items-center justify-between border-b border-slate-100 pb-2">
      <h3 class="text-sm font-bold text-slate-900">Ajukan Info Kajian Baru</h3>
      <button onclick="closeSubmitModal()" class="text-slate-400 hover:text-slate-600 p-1" aria-label="Tutup Pengajuan">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
      </button>
    </div>

    <form method="POST" action="{{ route('kajian.store') }}" class="space-y-3 text-xs">
      @csrf
      <div>
        <label class="block font-bold text-slate-700 mb-1">Judul / Tema Kajian *</label>
        <input type="text" name="title" required placeholder="Contoh: Kajian Kitab Riyadhus Shalihin" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-700">
      </div>

      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="block font-bold text-slate-700 mb-1">Nama Pemateri / Ustadz *</label>
          <input type="text" name="ustadz" required placeholder="Ustadz..." class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-700">
        </div>
        <div>
          <label class="block font-bold text-slate-700 mb-1">Kategori *</label>
          <select name="category" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-700">
            <option value="Tazkiyatun Nufus">Tazkiyatun Nufus</option>
            <option value="Fiqih">Fiqih</option>
            <option value="Aqidah">Aqidah</option>
            <option value="Sirah">Sirah</option>
            <option value="Akhlak">Akhlak</option>
            <option value="Tematik">Tematik</option>
          </select>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="block font-bold text-slate-700 mb-1">Nama Masjid *</label>
          <input type="text" name="masjid" required placeholder="Masjid..." class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-700">
        </div>
        <div>
          <label class="block font-bold text-slate-700 mb-1">Peserta Jamaah *</label>
          <select name="audience" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-700">
            <option value="Umum">Umum (Ikhwan & Akhwat)</option>
            <option value="Ikhwan">Khusus Ikhwan</option>
            <option value="Akhwat">Khusus Akhwat</option>
          </select>
        </div>
      </div>

      <div>
        <label class="block font-bold text-slate-700 mb-1">Alamat Lengkap Masjid *</label>
        <textarea name="address" required rows="2" placeholder="Jl. Raya..." class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-700"></textarea>
      </div>

      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="block font-bold text-slate-700 mb-1">Hari Rutin *</label>
          <select name="day" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-700">
            <option value="Ahad">Ahad</option>
            <option value="Senin">Senin</option>
            <option value="Selasa">Selasa</option>
            <option value="Rabu">Rabu</option>
            <option value="Kamis">Kamis</option>
            <option value="Jumat">Jumat</option>
            <option value="Sabtu">Sabtu</option>
          </select>
        </div>
        <div>
          <label class="block font-bold text-slate-700 mb-1">Waktu / Jam *</label>
          <input type="text" name="time" required placeholder="09:00 WIB / Ba'da Maghrib" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-700">
        </div>
      </div>

      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="block font-bold text-slate-700 mb-1">Latitude</label>
          <input type="number" step="any" name="lat" id="submitLat" value="-6.2615" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-700">
        </div>
        <div>
          <label class="block font-bold text-slate-700 mb-1">Longitude</label>
          <input type="number" step="any" name="lon" id="submitLon" value="106.8106" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-700">
        </div>
      </div>

      <div>
        <label class="block font-bold text-slate-700 mb-1">Deskripsi & Keterangan Tambahan</label>
        <textarea name="description" rows="2" placeholder="Catatan adab, parkir, streaming YouTube..." class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-700"></textarea>
      </div>

      <button type="submit" class="w-full py-3 bg-emerald-800 hover:bg-emerald-900 text-white text-xs font-bold rounded-xl shadow-md touch-btn">
        Ajukan Jadwal Kajian
      </button>
    </form>
  </div>
</div>
@endsection

@section('scripts')
<script>
  // Seluruh database kajian dari controller Laravel 13
  const rawKajians = {!! $allKajiansJson !!};
  const userBookmarkIds = new Set({!! json_encode($userBookmarks) !!});
  const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  // Titik koordinat Ka'bah (Mekkah)
  const KAABA_COORDS = { lat: 21.4225, lon: 39.8262 };

  // Titik lokasi default (Jakarta Selatan)
  let userCoords = {
    lat: -6.2615,
    lon: 106.8106,
    name: 'Jakarta Selatan'
  };

  // State Filter Aktif
  let activeSearch = '';
  let activeCategory = 'Semua';
  let activeRadius = 25; // km
  let activeDay = 'Semua';
  let activeAudience = 'Semua';
  let currentDetailEvent = null;

  // 1. Rumus Haversine Presisi
  function calculateHaversine(lat1, lon1, lat2, lon2) {
    const R = 6371; // Jari-jari bumi km
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
              Math.sin(dLon / 2) * Math.sin(dLon / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
  }

  // 2. Kalkulasi Waktu Sholat Surya Mandiri
  function calculatePrayerTimes(date, lat, lon) {
    const dayOfYear = Math.floor((date - new Date(date.getFullYear(), 0, 0)) / 1000 / 60 / 60 / 24);
    const b = 2 * Math.PI * (dayOfYear - 81) / 365;
    const eot = 9.87 * Math.sin(2 * b) - 7.53 * Math.cos(b) - 1.5 * Math.sin(b);
    const declination = 23.45 * Math.sin(2 * Math.PI * (284 + dayOfYear) / 365) * Math.PI / 180;

    const timezone = 7; // WIB
    const solarNoon = 12 + (timezone * 15 - lon) / 15 - (eot / 60);

    function hourAngle(altitudeAngle) {
      const altRad = altitudeAngle * Math.PI / 180;
      const latRad = lat * Math.PI / 180;
      const cosHA = (Math.sin(altRad) - Math.sin(latRad) * Math.sin(declination)) / (Math.cos(latRad) * Math.cos(declination));
      if (cosHA > 1) return 0;
      if (cosHA < -1) return Math.PI;
      return Math.acos(cosHA) * 180 / Math.PI / 15;
    }

    const haFajr = hourAngle(-20);
    const haSunrise = hourAngle(-0.833);
    const asharAlt = Math.atan(1 / (1 + Math.tan(Math.abs(lat * Math.PI / 180 - declination)))) * 180 / Math.PI;
    const haAshar = hourAngle(asharAlt);
    const haMaghrib = hourAngle(-0.833);
    const haIsha = hourAngle(-18);

    function fmt(hours) {
      let h = Math.floor(hours);
      let m = Math.floor((hours - h) * 60);
      return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
    }

    return {
      subuh: fmt(solarNoon - haFajr),
      terbit: fmt(solarNoon - haSunrise),
      dzuhur: fmt(solarNoon + (2/60)),
      ashar: fmt(solarNoon + haAshar),
      maghrib: fmt(solarNoon + haMaghrib),
      isya: fmt(solarNoon + haIsha)
    };
  }

  // Render & Update Waktu Sholat
  function updatePrayerDisplay() {
    const now = new Date();
    const times = calculatePrayerTimes(now, userCoords.lat, userCoords.lon);
    
    document.getElementById('time-subuh').innerText = times.subuh;
    document.getElementById('time-terbit').innerText = times.terbit;
    document.getElementById('time-dzuhur').innerText = times.dzuhur;
    document.getElementById('time-ashar').innerText = times.ashar;
    document.getElementById('time-maghrib').innerText = times.maghrib;
    document.getElementById('time-isya').innerText = times.isya;

    // Hitung waktu sholat berikutnya
    const currentMins = now.getHours() * 60 + now.getMinutes();
    function toMins(tStr) {
      const parts = tStr.split(':');
      return parseInt(parts[0]) * 60 + parseInt(parts[1]);
    }

    const prayerList = [
      { name: 'Subuh', mins: toMins(times.subuh), el: 'prayer-subuh' },
      { name: 'Dzuhur', mins: toMins(times.dzuhur), el: 'prayer-dzuhur' },
      { name: 'Ashar', mins: toMins(times.ashar), el: 'prayer-ashar' },
      { name: 'Maghrib', mins: toMins(times.maghrib), el: 'prayer-maghrib' },
      { name: 'Isya', mins: toMins(times.isya), el: 'prayer-isya' },
    ];

    let nextPrayer = null;
    for (let p of prayerList) {
      if (p.mins > currentMins) {
        nextPrayer = p;
        break;
      }
    }
    if (!nextPrayer) nextPrayer = prayerList[0]; // Besok Subuh

    // Highlight slot sholat berikutnya
    prayerList.forEach(p => {
      const box = document.getElementById(p.el);
      if (box) {
        if (p.name === nextPrayer.name) {
          box.classList.remove('bg-emerald-800/50');
          box.classList.add('bg-amber-500/30', 'border-amber-400');
        } else {
          box.classList.remove('bg-amber-500/30', 'border-amber-400');
          box.classList.add('bg-emerald-800/50');
        }
      }
    });

    const diffMins = nextPrayer.mins > currentMins ? (nextPrayer.mins - currentMins) : (1440 - currentMins + nextPrayer.mins);
    const diffH = Math.floor(diffMins / 60);
    const diffM = diffMins % 60;
    document.getElementById('nextPrayerCountdown').innerText = `Menuju ${nextPrayer.name}: ${diffH > 0 ? diffH + 'j ' : ''}${diffM}m`;
  }

  // 3. Render Daftar Kajian
  function renderKajianFeed() {
    const container = document.getElementById('kajianCardsList');
    const emptyState = document.getElementById('emptyStateContainer');
    const heroSpotlight = document.getElementById('heroSpotlightContainer');
    const countDisplay = document.getElementById('filteredCountText');

    let processed = rawKajians.map(item => {
      const dist = calculateHaversine(userCoords.lat, userCoords.lon, item.lat, item.lon);
      return { ...item, distance: dist };
    });

    // Urutkan berdasarkan jarak terdekat
    processed.sort((a, b) => a.distance - b.distance);

    // Filter
    let filtered = processed.filter(item => {
      if (activeCategory !== 'Semua' && item.category !== activeCategory) return false;
      if (activeDay !== 'Semua' && item.day !== activeDay) return false;
      if (activeAudience !== 'Semua' && item.audience !== activeAudience) return false;
      if (activeRadius < 900 && item.distance > activeRadius) return false;
      if (activeSearch) {
        const q = activeSearch.toLowerCase();
        const matchTitle = (item.title || '').toLowerCase().includes(q);
        const matchSpeaker = (item.ustadz || '').toLowerCase().includes(q);
        const matchMasjid = (item.masjid || '').toLowerCase().includes(q);
        const matchAddress = (item.address || '').toLowerCase().includes(q);
        if (!matchTitle && !matchSpeaker && !matchMasjid && !matchAddress) return false;
      }
      return true;
    });

    countDisplay.innerText = filtered.length;

    // Spotlight pada item terdekat jika ada
    if (filtered.length > 0 && !activeSearch) {
      const top = filtered[0];
      heroSpotlight.classList.remove('hidden');
      document.getElementById('spotlightTitle').innerText = top.title;
      document.getElementById('spotlightSpeaker').innerText = top.ustadz;
      document.getElementById('spotlightMasjid').innerText = top.masjid;
      document.getElementById('spotlightTime').innerText = `${top.day}, ${top.time}`;
      document.getElementById('spotlightDistance').innerText = `${top.distance.toFixed(1)} km`;
      document.getElementById('spotlightDetailBtn').onclick = () => openDetailModalWithEvent(top);
      document.getElementById('spotlightRouteBtn').onclick = () => openGoogleMapsRoute(top.lat, top.lon);
    } else {
      heroSpotlight.classList.add('hidden');
    }

    if (filtered.length === 0) {
      container.innerHTML = '';
      emptyState.classList.remove('hidden');
      return;
    }

    emptyState.classList.add('hidden');
    container.innerHTML = '';

    filtered.forEach(ev => {
      const isSaved = userBookmarkIds.has(ev.id);
      const card = document.createElement('article');
      card.className = 'bg-white rounded-2xl p-4 border border-slate-200/90 shadow-sm hover:border-emerald-700/60 transition-all space-y-3';
      card.innerHTML = `
        <div class="flex items-start justify-between">
          <div class="flex items-center space-x-1.5 flex-wrap">
            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-100">
              ${ev.category || 'Kajian'}
            </span>
            <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-600">
              ${ev.audience || 'Umum'}
            </span>
            ${ev.status === 'batal' ? '<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700">Dibatalkan</span>' : ''}
          </div>
          <div class="flex items-center space-x-1 text-xs font-bold text-emerald-800">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
            </svg>
            <span>${ev.distance.toFixed(1)} km</span>
          </div>
        </div>

        <div>
          <h3 class="text-sm font-bold text-slate-900 leading-snug line-clamp-2 hover:text-emerald-800 cursor-pointer" onclick="openDetailModalWithId(${ev.id})">
            ${ev.title}
          </h3>
          <p class="text-xs font-medium text-slate-600 mt-1 flex items-center space-x-1">
            <span>${ev.ustadz}</span>
          </p>
        </div>

        <div class="space-y-1 text-xs text-slate-500 pt-1 border-t border-slate-100">
          <div class="flex items-center space-x-1.5">
            <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
            </svg>
            <span class="font-medium text-slate-700 truncate">${ev.masjid}</span>
          </div>
          <div class="flex items-center space-x-1.5">
            <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span>${ev.day}, ${ev.time}</span>
          </div>
        </div>

        <div class="pt-2 flex items-center justify-between">
          <button onclick="toggleBookmarkAction(${ev.id}, this)" class="bookmark-btn p-2 rounded-xl border ${isSaved ? 'bg-amber-50 text-amber-600 border-amber-300' : 'bg-slate-50 text-slate-500 border-slate-200'} hover:border-amber-400 touch-btn" aria-label="Simpan Kajian">
            <svg class="w-4 h-4" fill="${isSaved ? 'currentColor' : 'none'}" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
            </svg>
          </button>

          <div class="flex items-center space-x-1.5">
            <button onclick="openDetailModalWithId(${ev.id})" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl touch-btn">
              Detail
            </button>
            <button onclick="openGoogleMapsRoute(${ev.lat}, ${ev.lon})" class="px-3 py-1.5 bg-emerald-800 hover:bg-emerald-900 text-white text-xs font-bold rounded-xl shadow-sm touch-btn flex items-center space-x-1">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
              </svg>
              <span>Rute</span>
            </button>
          </div>
        </div>
      `;
      container.appendChild(card);
    });
  }

  // 4. Modal Handlers
  function openDetailModalWithId(id) {
    const ev = rawKajians.find(k => k.id === id);
    if (ev) openDetailModalWithEvent(ev);
  }

  function openDetailModalWithEvent(ev) {
    currentDetailEvent = ev;
    document.getElementById('modalCategory').innerText = ev.category || 'Kajian Sunnah';
    document.getElementById('modalTitle').innerText = ev.title;
    document.getElementById('modalSpeaker').innerText = ev.ustadz;
    document.getElementById('modalMasjid').innerText = ev.masjid;
    document.getElementById('modalAddress').innerText = ev.address;
    document.getElementById('modalDay').innerText = ev.day;
    document.getElementById('modalTime').innerText = ev.time;
    document.getElementById('modalAudience').innerText = ev.audience || 'Umum';
    document.getElementById('modalDescription').innerText = ev.description || 'Kajian ilmiah ahlussunnah wal jamaah, terbuka untuk jamaah yang ingin menuntut ilmu syar\'i.';
    
    document.getElementById('modalRouteBtn').onclick = () => openGoogleMapsRoute(ev.lat, ev.lon);
    document.getElementById('detailModal').classList.remove('hidden');
  }

  function closeDetailModal() {
    document.getElementById('detailModal').classList.add('hidden');
  }

  function openFilterModal() {
    document.getElementById('filterModal').classList.remove('hidden');
  }
  function closeFilterModal() {
    document.getElementById('filterModal').classList.add('hidden');
  }

  function openLocationModal() {
    document.getElementById('locationModal').classList.remove('hidden');
  }
  function closeLocationModal() {
    document.getElementById('locationModal').classList.add('hidden');
  }

  function openSubmitModal() {
    document.getElementById('submitLat').value = userCoords.lat;
    document.getElementById('submitLon').value = userCoords.lon;
    document.getElementById('submitModal').classList.remove('hidden');
  }
  function closeSubmitModal() {
    document.getElementById('submitModal').classList.add('hidden');
  }

  // Keyboard accessibility: Escape key closes active modal (R-32)
  window.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      closeDetailModal();
      closeFilterModal();
      closeLocationModal();
      closeSubmitModal();
    }
  });

  // 5. Filter Actions
  function setCategoryFilter(cat) {
    activeCategory = cat;
    document.querySelectorAll('.category-chip').forEach(btn => {
      if (btn.getAttribute('data-cat') === cat) {
        btn.classList.remove('bg-white', 'text-slate-700', 'border', 'border-slate-200');
        btn.classList.add('bg-emerald-800', 'text-white', 'font-semibold', 'shadow-sm');
      } else {
        btn.classList.remove('bg-emerald-800', 'text-white', 'font-semibold', 'shadow-sm');
        btn.classList.add('bg-white', 'text-slate-700', 'border', 'border-slate-200');
      }
    });
    renderKajianFeed();
  }

  function setRadiusFilter(r) {
    activeRadius = r;
    document.querySelectorAll('.radius-chip').forEach(btn => {
      if (parseInt(btn.getAttribute('data-radius')) === r) {
        btn.classList.remove('bg-white', 'text-slate-500', 'border-slate-200');
        btn.classList.add('border-emerald-700', 'bg-emerald-50', 'text-emerald-800', 'font-bold');
      } else {
        btn.classList.remove('border-emerald-700', 'bg-emerald-50', 'text-emerald-800', 'font-bold');
        btn.classList.add('bg-white', 'text-slate-500', 'border-slate-200');
      }
    });
    renderKajianFeed();
  }

  function setAudienceFilter(aud) {
    activeAudience = aud;
    document.querySelectorAll('.audience-btn').forEach(btn => {
      if (btn.getAttribute('data-audience') === aud) {
        btn.classList.remove('bg-white', 'border-slate-200', 'font-medium');
        btn.classList.add('border-emerald-700', 'bg-emerald-50', 'text-emerald-800', 'font-bold');
      } else {
        btn.classList.remove('border-emerald-700', 'bg-emerald-50', 'text-emerald-800', 'font-bold');
        btn.classList.add('bg-white', 'border-slate-200', 'font-medium');
      }
    });
  }

  function applyFilters() {
    const daySelect = document.getElementById('filterDaySelect');
    if (daySelect) activeDay = daySelect.value;
    renderKajianFeed();
  }

  function resetAllFilters() {
    activeSearch = '';
    activeCategory = 'Semua';
    activeRadius = 25;
    activeDay = 'Semua';
    activeAudience = 'Semua';
    
    document.getElementById('searchInput').value = '';
    document.getElementById('clearSearchBtn').classList.add('hidden');
    
    setCategoryFilter('Semua');
    setRadiusFilter(25);
    setAudienceFilter('Semua');
    
    const daySelect = document.getElementById('filterDaySelect');
    if (daySelect) daySelect.value = 'Semua';

    renderKajianFeed();
  }

  function onSearchFilterChange() {
    const val = document.getElementById('searchInput').value.trim();
    activeSearch = val;
    const clearBtn = document.getElementById('clearSearchBtn');
    if (val.length > 0) {
      clearBtn.classList.remove('hidden');
    } else {
      clearBtn.classList.add('hidden');
    }
    renderKajianFeed();
  }

  function clearSearchInput() {
    document.getElementById('searchInput').value = '';
    document.getElementById('clearSearchBtn').classList.add('hidden');
    activeSearch = '';
    renderKajianFeed();
  }

  // 6. Lokasi GPS & Kota
  function useGpsLocation() {
    if (!navigator.geolocation) {
      alert('Perangkat Anda tidak mendukung fitur geolokasi GPS.');
      return;
    }

    navigator.geolocation.getCurrentPosition(
      (pos) => {
        userCoords = {
          lat: pos.coords.latitude,
          lon: pos.coords.longitude,
          name: 'Lokasi GPS Saya'
        };
        document.getElementById('currentLocationName').innerText = userCoords.name;
        updatePrayerDisplay();
        renderKajianFeed();
      },
      (err) => {
        alert('Gagal mendeteksi lokasi GPS. Mempertahankan lokasi default.');
      },
      { enableHighAccuracy: true, timeout: 10000 }
    );
  }

  function setCityLocation(name, lat, lon) {
    userCoords = { lat, lon, name };
    document.getElementById('currentLocationName').innerText = name;
    closeLocationModal();
    updatePrayerDisplay();
    renderKajianFeed();
  }

  // 7. Rute & Berbagi
  function openGoogleMapsRoute(lat, lon) {
    const url = `https://www.google.com/maps/dir/?api=1&destination=${lat},${lon}`;
    window.open(url, '_blank', 'noopener,noreferrer');
  }

  function shareCurrentKajian() {
    if (!currentDetailEvent) return;
    const shareText = `*${currentDetailEvent.title}*\nBersama: ${currentDetailEvent.ustadz}\nWaktu: ${currentDetailEvent.day}, ${currentDetailEvent.time}\nTempat: ${currentDetailEvent.masjid}\nAlamat: ${currentDetailEvent.address}\n\nDitemukan via SiniKajian: https://sinikajian.vercel.app`;

    if (navigator.share) {
      navigator.share({
        title: currentDetailEvent.title,
        text: shareText,
        url: window.location.href
      }).catch(() => {});
    } else {
      navigator.clipboard.writeText(shareText).then(() => {
        alert('Informasi kajian berhasil disalin ke clipboard.');
      });
    }
  }

  // 8. Bookmark Interaktif
  function toggleBookmarkAction(kajianId, btnEl) {
    fetch(`/kajian/${kajianId}/bookmark`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': CSRF_TOKEN
      }
    })
    .then(res => {
      if (res.status === 401) {
        window.location.href = '{{ route("login") }}';
        return;
      }
      return res.json();
    })
    .then(data => {
      if (!data) return;
      if (data.saved) {
        userBookmarkIds.add(kajianId);
        btnEl.classList.remove('bg-slate-50', 'text-slate-500', 'border-slate-200');
        btnEl.classList.add('bg-amber-50', 'text-amber-600', 'border-amber-300');
        btnEl.querySelector('svg').setAttribute('fill', 'currentColor');
      } else {
        userBookmarkIds.delete(kajianId);
        btnEl.classList.remove('bg-amber-50', 'text-amber-600', 'border-amber-300');
        btnEl.classList.add('bg-slate-50', 'text-slate-500', 'border-slate-200');
        btnEl.querySelector('svg').setAttribute('fill', 'none');
      }
    })
    .catch(err => {
      console.warn('Gagal mengubah bookmark:', err);
    });
  }

  // Inisialisasi awal
  document.addEventListener('DOMContentLoaded', () => {
    updatePrayerDisplay();
    renderKajianFeed();
    setInterval(updatePrayerDisplay, 60000); // Perbarui waktu sholat tiap menit
  });
</script>
@endsection
