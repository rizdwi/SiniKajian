@extends('layouts.app')

@section('title', 'Majelisku | Jadwal Kajian Sunnah & Waktu Sholat')

@section('content')
<div class="max-w-md mx-auto px-4 pt-4 pb-8 space-y-5">
  
  <!-- Header Area Sesuai Desain Figma (Assalamu'alaikum & Lokasi) -->
  <header class="flex items-center justify-between pt-2">
    <div>
      <p class="text-xs text-[#607072] font-medium">Assalamu'alaikum, {{ Auth::check() ? Auth::user()->name : 'Tamu' }}</p>
      <h1 class="text-xl font-extrabold text-[#1D4D4F] tracking-tight mt-0.5">Yuk, kita ngaji.</h1>
      <button type="button" onclick="openLocationModal()" class="inline-flex items-center gap-1 text-[11px] font-semibold text-[#1D4D4F] bg-[#E6F4F1] hover:bg-[#D1EAE5] px-2.5 py-0.5 rounded-full mt-1.5 transition">
        <svg class="w-3 h-3 text-[#1D4D4F]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
        <span id="currentLocationName">Mendeteksi Lokasi...</span>
        <svg class="w-3 h-3 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
      </button>
    </div>

    <div class="flex items-center gap-2">
      <!-- Tombol Masukan Info Kajian -->
      <button type="button" onclick="openSubmitModal()" title="Masukan Info Kajian" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-full bg-[#E6F4F1] hover:bg-[#D1EAE5] text-[#1D4D4F] text-xs font-bold border border-[#D1ECE7] shadow-2xs touch-btn transition" aria-label="Masukan Info Kajian">
        <svg class="w-4 h-4 text-[#1D4D4F] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 4v16m8-8H4"></path>
        </svg>
        <span class="text-[11px] font-bold whitespace-nowrap hidden xs:inline">Info Kajian</span>
      </button>

      <!-- Tombol Foto Profil (Langsung ke Halaman Profil) -->
      <a href="{{ route('profile') }}" title="Lihat Profil Saya" class="w-10 h-10 rounded-full overflow-hidden bg-[#1D4D4F] text-white flex items-center justify-center font-extrabold text-sm border-2 border-white shadow-xs ring-2 ring-[#1D4D4F]/20 touch-btn hover:opacity-90 transition shrink-0 relative" aria-label="Profil Saya">
        <img id="headerUserAvatarImg" src="{{ Auth::check() && !empty(Auth::user()->avatar) ? Auth::user()->avatar : '' }}" alt="{{ Auth::check() ? Auth::user()->name : 'Tamu' }}" class="w-full h-full object-cover {{ Auth::check() && !empty(Auth::user()->avatar) ? '' : 'hidden' }}">
        <span id="headerUserAvatarInitial" class="{{ Auth::check() && !empty(Auth::user()->avatar) ? 'hidden' : '' }}">
          @auth
            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
          @else
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
            </svg>
          @endauth
        </span>
      </a>
    </div>
  </header>

  <!-- Waktu Sholat Astronomis Surya Presisi (Spruce Teal Aesthetic) -->
  <section class="bg-gradient-to-br from-[#1D4D4F] to-[#153A3B] text-white p-3.5 rounded-2xl shadow-xs border border-[#1D4D4F]/80">
    <div class="flex items-center justify-between mb-2.5 border-b border-white/15 pb-2">
      <div class="flex items-center space-x-1.5">
        <span class="w-2 h-2 rounded-full bg-[#FCD34D] animate-pulse"></span>
        <span class="text-[11px] font-bold text-[#E6F4F1] tracking-wider uppercase">JADWAL SHOLAT ASTRONOMIS</span>
      </div>
      <div id="nextPrayerCountdown" class="text-[11px] font-semibold text-[#FCD34D]">
        Menghitung jadwal...
      </div>
    </div>

    <div class="grid grid-cols-6 gap-1 text-center" id="prayerTimesGrid">
      <div class="bg-white/10 p-1.5 rounded-xl border border-white/10" id="prayer-subuh">
        <p class="text-[9px] text-[#A4D3CC] font-medium">Subuh</p>
        <p class="text-xs font-bold text-white font-mono mt-0.5" id="time-subuh">--:--</p>
      </div>
      <div class="bg-white/5 p-1.5 rounded-xl border border-white/5" id="prayer-terbit">
        <p class="text-[9px] text-[#A4D3CC] font-medium">Terbit</p>
        <p class="text-xs font-medium text-white/80 font-mono mt-0.5" id="time-terbit">--:--</p>
      </div>
      <div class="bg-white/10 p-1.5 rounded-xl border border-white/10" id="prayer-dzuhur">
        <p class="text-[9px] text-[#A4D3CC] font-medium">Dzuhur</p>
        <p class="text-xs font-bold text-white font-mono mt-0.5" id="time-dzuhur">--:--</p>
      </div>
      <div class="bg-white/10 p-1.5 rounded-xl border border-white/10" id="prayer-ashar">
        <p class="text-[9px] text-[#A4D3CC] font-medium">Ashar</p>
        <p class="text-xs font-bold text-white font-mono mt-0.5" id="time-ashar">--:--</p>
      </div>
      <div class="bg-white/10 p-1.5 rounded-xl border border-white/10" id="prayer-maghrib">
        <p class="text-[9px] text-[#A4D3CC] font-medium">Maghrib</p>
        <p class="text-xs font-bold text-white font-mono mt-0.5" id="time-maghrib">--:--</p>
      </div>
      <div class="bg-white/10 p-1.5 rounded-xl border border-white/10" id="prayer-isya">
        <p class="text-[9px] text-[#A4D3CC] font-medium">Isya</p>
        <p class="text-xs font-bold text-white font-mono mt-0.5" id="time-isya">--:--</p>
      </div>
    </div>
  </section>

  <!-- Kolom Pencarian & Aksi Filter (Figma Screen 1: Search Pill & Slider Button) -->
  <section class="space-y-3">
    <div class="flex items-center gap-2">
      <div class="relative flex-1">
        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#607072]">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
          </svg>
        </span>
        <input 
          type="text" 
          id="searchInput"
          placeholder="Cari tema kajian, ustadz, atau masjid" 
          class="w-full pl-10 pr-9 py-2.5 bg-white border border-[#E5ECEB] rounded-2xl text-xs sm:text-sm text-[#1C2224] placeholder-[#607072] focus:outline-none focus:ring-2 focus:ring-[#1D4D4F] focus:border-[#1D4D4F] shadow-xs"
          oninput="onSearchFilterChange()"
        >
        <button 
          id="clearSearchBtn"
          onclick="clearSearchInput()"
          class="hidden absolute inset-y-0 right-0 pr-3 flex items-center text-[#607072] hover:text-[#1C2224]"
          aria-label="Bersihkan pencarian"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
      </div>

      <button 
        type="button" 
        onclick="openFilterModal()" 
        class="w-10 h-10 bg-white border border-[#E5ECEB] rounded-2xl text-[#1D4D4F] hover:bg-[#E6F4F1] shadow-xs touch-btn flex items-center justify-center shrink-0 transition"
        aria-label="Buka Filter"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
        </svg>
      </button>
    </div>

    <!-- Quick Chips: Kategori Kajian (Figma Screen 1: Filter Chips) -->
    <div class="flex items-center gap-2 overflow-x-auto no-scrollbar py-0.5 text-xs">
      <button onclick="setCategoryFilter('Semua')" class="category-chip whitespace-nowrap px-4 py-2 rounded-full font-bold transition-all bg-[#1D4D4F] text-white shadow-xs" data-cat="Semua">
        Untukmu
      </button>
      @foreach($categories as $cat)
      <button onclick="setCategoryFilter('{{ $cat }}')" class="category-chip whitespace-nowrap px-4 py-2 rounded-full font-medium transition-all bg-white text-[#607072] border border-[#E5ECEB] hover:border-[#1D4D4F] hover:text-[#1D4D4F]" data-cat="{{ $cat }}">
        {{ $cat }}
      </button>
      @endforeach
    </div>

    <!-- Quick Chips: Radius Jarak & Hari -->
    <div class="flex items-center justify-between text-xs text-[#607072] pt-0.5">
      <div class="flex items-center space-x-1.5 overflow-x-auto no-scrollbar">
        <span class="text-[11px] font-medium text-[#607072]">Radius:</span>
        <button onclick="setRadiusFilter(5)" class="radius-chip px-2.5 py-1 rounded-xl border border-[#E5ECEB] bg-white font-medium hover:border-[#1D4D4F]" data-radius="5">5 km</button>
        <button onclick="setRadiusFilter(15)" class="radius-chip px-2.5 py-1 rounded-xl border border-[#E5ECEB] bg-white font-medium hover:border-[#1D4D4F]" data-radius="15">15 km</button>
        <button onclick="setRadiusFilter(25)" class="radius-chip px-2.5 py-1 rounded-xl border border-[#1D4D4F] bg-[#E6F4F1] text-[#1D4D4F] font-bold" data-radius="25">25 km</button>
        <button onclick="setRadiusFilter(999)" class="radius-chip px-2.5 py-1 rounded-xl border border-[#E5ECEB] bg-white font-medium hover:border-[#1D4D4F]" data-radius="999">Semua</button>
      </div>

      <div class="flex items-center space-x-1">
        <span id="filteredCountBadge" class="font-bold text-[#1D4D4F] bg-[#E6F4F1] px-2 py-0.5 rounded-full">{{ $kajians->count() }}</span>
        <span class="text-[11px]">Kajian</span>
      </div>
    </div>
  </section>

  <!-- Hero Spotlight: Majelis Pilihan Terdekat (Figma Screen 1: KAJIAN PILIHAN) -->
  <section id="heroSpotlightContainer" class="hidden">
    <div class="relative rounded-3xl overflow-hidden shadow-md border border-[#E5ECEB] bg-[#153A3B] group cursor-pointer" onclick="document.getElementById('spotlightDetailBtn').click()">
      <!-- Hero Image / Flyer Background -->
      <img id="spotlightHeroImg" src="https://images.unsplash.com/photo-1542816417-0983c9c9ad53?auto=format&fit=crop&w=800&q=80" alt="Kajian Pilihan" class="w-full h-52 object-cover opacity-85 group-hover:scale-105 transition-transform duration-500">
      <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-black/30"></div>

      <!-- Top Badge & Bookmark Button -->
      <div class="absolute top-3 inset-x-3 flex items-center justify-between">
        <span class="px-3 py-1 rounded-full text-[10px] font-extrabold tracking-wider uppercase bg-[#E6F4F1] text-[#1D4D4F] shadow-xs">
          KAJIAN PILIHAN
        </span>
        <button id="spotlightBookmarkBtn" type="button" class="w-8 h-8 rounded-full bg-black/40 backdrop-blur-md text-white flex items-center justify-center hover:bg-black/60 transition touch-btn" onclick="event.stopPropagation();">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path></svg>
        </button>
      </div>

      <!-- Bottom Floating Glassmorphism Overlay Card -->
      <div class="absolute bottom-3 inset-x-3 p-3.5 rounded-2xl bg-white/15 backdrop-blur-md border border-white/25 text-white shadow-sm">
        <div class="flex items-center gap-2 mb-1">
          <span id="spotlightTimePill" class="text-[10px] font-bold uppercase tracking-wider text-[#FCD34D]">Ahad • 09.00 WIB</span>
          <span class="text-white/40">•</span>
          <span id="spotlightDistance" class="text-[10px] text-white/90 font-medium">850 m</span>
        </div>
        <h2 id="spotlightTitle" class="text-sm sm:text-base font-bold text-white leading-snug line-clamp-1">
          Menata Hati, Menemukan Tenang
        </h2>
        <p id="spotlightMasjid" class="text-xs text-white/85 mt-0.5 flex items-center gap-1 truncate">
          <svg class="w-3.5 h-3.5 text-[#E6F4F1] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
          <span>Masjid Terdekat di Sekitarmu</span>
        </p>
      </div>
      <button id="spotlightDetailBtn" class="hidden"></button>
    </div>
  </section>

  <!-- Feed Section Header: Luangkan waktu untuk ilmu -->
  <div class="flex items-center justify-between pt-1">
    <div>
      <h2 class="text-sm font-bold text-[#1C2224]">Luangkan waktu untuk ilmu</h2>
      <p class="text-[11px] text-[#607072]">Kajian sunnah pilihan di sekitarmu</p>
    </div>
    <span id="filteredCountText" class="text-xs font-bold text-[#1D4D4F] bg-[#E6F4F1] px-2.5 py-0.5 rounded-full">
      {{ $kajians->count() }} Kajian
    </span>
  </div>

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

<!-- Modal Bottom Sheet: Detail Kajian (Sesuai Desain Figma Screen 2) -->
<div id="detailModal" class="hidden fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/60 backdrop-blur-sm p-0 sm:p-4 transition-opacity">
  <div class="bg-white w-full max-w-lg rounded-t-3xl sm:rounded-3xl max-h-[92vh] overflow-y-auto no-scrollbar shadow-2xl p-4 sm:p-5 space-y-4">
    <!-- Mobile Drag Handle -->
    <div class="w-12 h-1 bg-slate-200 rounded-full mx-auto sm:hidden mb-1"></div>
    
    <!-- Top Navigation Bar: Back Chevron, Title, Bookmark & Share Buttons -->
    <div class="flex items-center justify-between border-b border-[#E5ECEB] pb-3">
      <button onclick="closeDetailModal()" class="w-9 h-9 rounded-full bg-[#F8FAF9] hover:bg-[#E6F4F1] border border-[#E5ECEB] text-[#1D4D4F] flex items-center justify-center touch-btn" aria-label="Kembali">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
      </button>
      <h2 class="text-sm font-bold text-[#1C2224]">Detail Kajian</h2>
      <div class="flex items-center gap-1.5">
        <button id="modalBookmarkBtn" onclick="toggleModalBookmark()" class="w-9 h-9 rounded-full bg-[#F8FAF9] hover:bg-[#E6F4F1] border border-[#E5ECEB] text-[#607072] flex items-center justify-center touch-btn transition" aria-label="Tandai / Simpan">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path></svg>
        </button>
        <button onclick="shareCurrentKajian()" class="w-9 h-9 rounded-full bg-[#F8FAF9] hover:bg-[#E6F4F1] border border-[#E5ECEB] text-[#1D4D4F] flex items-center justify-center touch-btn" aria-label="Bagikan">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path></svg>
        </button>
        <button onclick="shareViaWhatsApp()" class="w-9 h-9 rounded-full bg-[#25D366] hover:bg-[#1DA851] text-white flex items-center justify-center touch-btn" aria-label="Bagikan via WhatsApp">
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
        </button>
      </div>
    </div>

    <!-- Hero Media Card (Flyer Preview / Mosque) -->
    <div id="modalFlyerWrapper" class="relative rounded-2xl overflow-hidden border border-[#E5ECEB] bg-slate-900 group">
      <img id="modalFlyerImg" src="" alt="Flyer Kajian" class="w-full h-48 sm:h-56 object-cover" loading="lazy">
      <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-transparent to-black/20"></div>
      
      <!-- Badges on Image -->
      <div class="absolute top-2.5 inset-x-2.5 flex items-center justify-between">
        <span class="px-2.5 py-0.5 rounded-full text-[9px] font-extrabold uppercase tracking-wider bg-[#E6F4F1] text-[#1D4D4F] shadow-xs">
          KAJIAN PILIHAN
        </span>
        <a id="modalFlyerFullLink" href="#" target="_blank" rel="noopener" class="bg-black/60 hover:bg-black text-white text-[10px] font-bold px-2 py-0.5 rounded-lg backdrop-blur-xs flex items-center gap-1">
          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
          <span>Buka Penuh ↗</span>
        </a>
      </div>

      <div class="absolute bottom-2.5 inset-x-2.5 text-white">
        <p id="modalHeroSub" class="text-xs text-white/90 font-medium truncate"></p>
      </div>
    </div>

    <!-- Metadata Badges Row -->
    <div class="flex items-center gap-1.5 flex-wrap">
      <span id="modalCategory" class="px-3 py-1 rounded-full text-[10px] font-bold bg-[#E6F4F1] text-[#1D4D4F]">
        Akhlak & Hati
      </span>
      <span id="modalAudience" class="px-3 py-1 rounded-full text-[10px] font-medium bg-[#F8FAF9] text-[#607072] border border-[#E5ECEB]">
        Terbuka untuk umum
      </span>
      <span class="px-3 py-1 rounded-full text-[10px] font-medium bg-emerald-50 text-emerald-800 border border-emerald-100">
        Gratis
      </span>
    </div>

    <!-- Main Title Heading -->
    <div>
      <h1 id="modalTitle" class="text-lg font-extrabold text-[#1C2224] leading-snug">Judul Kajian</h1>
    </div>

    <!-- Detail Cards List (Figma Screen 2) -->
    <div class="space-y-2.5">
      <!-- Card 1: Tanggal & Waktu -->
      <div class="bg-[#F8FAF9] p-3 rounded-2xl border border-[#E5ECEB] flex items-start gap-3">
        <div class="w-9 h-9 rounded-full bg-[#E6F4F1] text-[#1D4D4F] flex items-center justify-center shrink-0 mt-0.5">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-[10px] font-extrabold uppercase tracking-wider text-[#607072]">TANGGAL & WAKTU</p>
          <p id="modalDateTimeStr" class="text-xs font-bold text-[#1C2224] mt-0.5">Ahad • 09:00 WIB</p>
          <p class="text-[11px] text-[#607072] mt-0.5">Hadir 15 menit lebih awal untuk menyempurnakan shaf</p>
        </div>
      </div>

      <!-- Card 2: Tempat Kajian -->
      <div class="bg-[#F8FAF9] p-3 rounded-2xl border border-[#E5ECEB] flex items-start gap-3">
        <div class="w-9 h-9 rounded-full bg-[#E6F4F1] text-[#1D4D4F] flex items-center justify-center shrink-0 mt-0.5">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-[10px] font-extrabold uppercase tracking-wider text-[#607072]">TEMPAT KAJIAN</p>
          <p id="modalMasjid" class="text-xs font-bold text-[#1C2224] mt-0.5">Masjid</p>
          <p id="modalAddress" class="text-[11px] text-[#607072] mt-0.5">Alamat lengkap...</p>
          <a id="modalRouteBtn" href="#" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-[11px] font-bold text-[#1D4D4F] mt-1.5 hover:underline">
            <span>Lihat petunjuk arah ↗</span>
          </a>
        </div>
      </div>

      <!-- Card 3: Pembicara / Ustadz -->
      <div class="bg-[#F8FAF9] p-3 rounded-2xl border border-[#E5ECEB] flex items-center gap-3">
        <div class="w-9 h-9 rounded-full bg-[#1D4D4F] text-white flex items-center justify-center font-bold text-xs shrink-0">
          <span id="modalSpeakerInitial">U</span>
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-[10px] font-extrabold uppercase tracking-wider text-[#607072]">PEMBICARA / USTADZ</p>
          <p id="modalSpeaker" class="text-xs font-bold text-[#1C2224] mt-0.5 truncate">Ustadz</p>
          <p class="text-[10px] text-[#607072]">Pengasuh Kajian Sunnah</p>
        </div>
      </div>
    </div>

    <!-- Deskripsi & Pokok Bahasan -->
    <div class="bg-white p-3.5 rounded-2xl border border-[#E5ECEB] space-y-2">
      <h4 class="text-xs font-bold text-[#1C2224]">Apa yang akan dibahas?</h4>
      <p id="modalDescription" class="text-xs text-[#607072] leading-relaxed">
        Kajian ilmiah sunnah ahlussunnah wal jamaah, terbuka untuk jamaah ikhwan dan akhwat yang ingin memperdalam ilmu syar'i.
      </p>
      <div class="space-y-1.5 pt-1 text-[11px] text-[#1C2224]">
        <div class="flex items-center gap-2">
          <span class="w-4 h-4 rounded-full bg-[#E6F4F1] text-[#1D4D4F] flex items-center justify-center text-[10px] font-bold shrink-0">✓</span>
          <span>Pembahasan dalil shahih dari Al-Qur'an dan As-Sunnah</span>
        </div>
        <div class="flex items-center gap-2">
          <span class="w-4 h-4 rounded-full bg-[#E6F4F1] text-[#1D4D4F] flex items-center justify-center text-[10px] font-bold shrink-0">✓</span>
          <span>Tanya jawab seputar persoalan ibadah dan muamalah</span>
        </div>
      </div>
    </div>

    <!-- Tombol Sumber Asli Meta (Facebook / Instagram / Threads) -->
    <a id="modalSourceBtn" href="#" target="_blank" rel="noopener noreferrer" class="hidden w-full touch-btn py-2.5 rounded-2xl text-white font-bold text-xs flex items-center justify-center gap-2 no-underline shadow-xs transition hover:opacity-90">
      <span id="modalSourceGlyph" class="font-extrabold text-sm"></span>
      <span id="modalSourceLabel">Buka Sumber Asli di Facebook ↗</span>
    </a>

    <!-- Sticky Bottom CTA Action (Kajian Tersimpan & Majelis Diikuti) -->
    <div class="pt-2 space-y-2">
      <div class="grid grid-cols-2 gap-2">
        <!-- Tombol 1: Simpan Kajian (Kajian Tersimpan) -->
        <button id="modalSaveBtn" onclick="toggleModalSaveAction()" class="w-full py-3 px-2 rounded-2xl border-2 border-[#1D4D4F] text-[#1D4D4F] bg-[#E6F4F1] hover:bg-[#D1EAE5] text-xs font-extrabold shadow-2xs touch-btn flex items-center justify-center gap-1.5 transition">
          <svg id="modalSaveIcon" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
          </svg>
          <span id="modalSaveLabel" class="truncate">Simpan Kajian</span>
        </button>

        <!-- Tombol 2: Ikuti Majelis (Majelis Diikuti) -->
        <button id="modalFollowBtn" onclick="toggleModalFollowAction()" class="w-full py-3 px-2 rounded-2xl bg-[#1D4D4F] hover:bg-[#153A3B] text-white text-xs font-extrabold shadow-sm touch-btn flex items-center justify-center gap-1.5 transition">
          <svg id="modalFollowIcon" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
          </svg>
          <span id="modalFollowLabel" class="truncate">Ikuti Majelis</span>
        </button>
      </div>
      <p class="text-[10px] text-center text-[#607072]">
        Kajian tersimpan di agenda pribadi dan majelis tercatat diikuti di profil.
      </p>
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
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
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
  <div class="bg-white w-full max-w-lg rounded-t-3xl sm:rounded-3xl p-6 space-y-4 max-h-[90vh] overflow-y-auto no-scrollbar border border-[#E5ECEB] shadow-xl">
    <div class="flex items-center justify-between border-b border-[#E5ECEB] pb-3">
      <div class="flex items-center gap-2.5">
        <div class="w-8 h-8 rounded-full bg-[#E6F4F1] text-[#1D4D4F] flex items-center justify-center">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
        </div>
        <div>
          <h3 class="text-sm font-bold text-[#1C2224]">Ajukan Info Kajian Baru</h3>
          <p class="text-[11px] text-[#607072]">Bantu sebarkan majelis ilmu sunnah di sekitar Anda.</p>
        </div>
      </div>
      <button onclick="closeSubmitModal()" class="w-8 h-8 rounded-full bg-[#F8FAF9] text-[#607072] hover:text-[#1C2224] flex items-center justify-center transition" aria-label="Tutup Pengajuan">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
      </button>
    </div>

    <form method="POST" action="{{ route('kajian.store') }}" class="space-y-3.5 text-xs">
      @csrf

      <!-- Anti-Bot Honeypot Trap (Invisible) -->
      <div style="display:none !important; opacity:0; position:absolute; left:-9999px;" aria-hidden="true">
        <input type="text" name="website_trap" tabindex="-1" autocomplete="off" value="">
      </div>
      <div>
        <label class="block font-bold text-[#1C2224] mb-1">Judul / Tema Kajian *</label>
        <input type="text" name="title" required placeholder="Contoh: Kajian Kitab Riyadhus Shalihin" class="w-full bg-[#F8FAF9] border border-[#E5ECEB] rounded-2xl p-2.5 text-xs text-[#1C2224] focus:outline-none focus:ring-2 focus:ring-[#1D4D4F]">
      </div>

      <div class="grid grid-cols-2 gap-2.5">
        <div>
          <label class="block font-bold text-[#1C2224] mb-1">Nama Pemateri / Ustadz *</label>
          <input type="text" name="ustadz" required placeholder="Ustadz..." class="w-full bg-[#F8FAF9] border border-[#E5ECEB] rounded-2xl p-2.5 text-xs text-[#1C2224] focus:outline-none focus:ring-2 focus:ring-[#1D4D4F]">
        </div>
        <div>
          <label class="block font-bold text-[#1C2224] mb-1">Kategori *</label>
          <select name="category" required class="w-full bg-[#F8FAF9] border border-[#E5ECEB] rounded-2xl p-2.5 text-xs text-[#1C2224] focus:outline-none focus:ring-2 focus:ring-[#1D4D4F]">
            <option value="Tazkiyatun Nufus">Tazkiyatun Nufus</option>
            <option value="Fiqih">Fiqih</option>
            <option value="Aqidah">Aqidah</option>
            <option value="Sirah">Sirah</option>
            <option value="Akhlak">Akhlak</option>
            <option value="Tematik">Tematik</option>
          </select>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-2.5">
        <div>
          <label class="block font-bold text-[#1C2224] mb-1">Nama Masjid *</label>
          <input type="text" name="masjid" required placeholder="Masjid..." class="w-full bg-[#F8FAF9] border border-[#E5ECEB] rounded-2xl p-2.5 text-xs text-[#1C2224] focus:outline-none focus:ring-2 focus:ring-[#1D4D4F]">
        </div>
        <div>
          <label class="block font-bold text-[#1C2224] mb-1">Peserta Jamaah *</label>
          <select name="audience" required class="w-full bg-[#F8FAF9] border border-[#E5ECEB] rounded-2xl p-2.5 text-xs text-[#1C2224] focus:outline-none focus:ring-2 focus:ring-[#1D4D4F]">
            <option value="Umum">Umum (Ikhwan & Akhwat)</option>
            <option value="Ikhwan">Khusus Ikhwan</option>
            <option value="Akhwat">Khusus Akhwat</option>
          </select>
        </div>
      </div>

      <div>
        <label class="block font-bold text-[#1C2224] mb-1">Alamat Lengkap Masjid *</label>
        <textarea name="address" required rows="2" placeholder="Jl. Raya..." class="w-full bg-[#F8FAF9] border border-[#E5ECEB] rounded-2xl p-2.5 text-xs text-[#1C2224] focus:outline-none focus:ring-2 focus:ring-[#1D4D4F]"></textarea>
      </div>

      <div class="grid grid-cols-2 gap-2.5">
        <div>
          <label class="block font-bold text-[#1C2224] mb-1">Hari Rutin *</label>
          <select name="day" required class="w-full bg-[#F8FAF9] border border-[#E5ECEB] rounded-2xl p-2.5 text-xs text-[#1C2224] focus:outline-none focus:ring-2 focus:ring-[#1D4D4F]">
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
          <label class="block font-bold text-[#1C2224] mb-1">Waktu / Jam *</label>
          <input type="text" name="time" required placeholder="09:00 WIB / Ba'da Maghrib" class="w-full bg-[#F8FAF9] border border-[#E5ECEB] rounded-2xl p-2.5 text-xs text-[#1C2224] focus:outline-none focus:ring-2 focus:ring-[#1D4D4F]">
        </div>
      </div>

      <div class="grid grid-cols-2 gap-2.5">
        <div>
          <label class="block font-bold text-[#1C2224] mb-1">Latitude</label>
          <input type="number" step="any" name="lat" id="submitLat" value="-6.2615" required class="w-full bg-[#F8FAF9] border border-[#E5ECEB] rounded-2xl p-2.5 text-xs text-[#1C2224] focus:outline-none focus:ring-2 focus:ring-[#1D4D4F]">
        </div>
        <div>
          <label class="block font-bold text-[#1C2224] mb-1">Longitude</label>
          <input type="number" step="any" name="lon" id="submitLon" value="106.8106" required class="w-full bg-[#F8FAF9] border border-[#E5ECEB] rounded-2xl p-2.5 text-xs text-[#1C2224] focus:outline-none focus:ring-2 focus:ring-[#1D4D4F]">
        </div>
      </div>

      <div>
        <label class="block font-bold text-[#1C2224] mb-1">Deskripsi & Keterangan Tambahan</label>
        <textarea name="description" rows="2" placeholder="Catatan adab, parkir, streaming YouTube..." class="w-full bg-[#F8FAF9] border border-[#E5ECEB] rounded-2xl p-2.5 text-xs text-[#1C2224] focus:outline-none focus:ring-2 focus:ring-[#1D4D4F]"></textarea>
      </div>

      <button type="submit" class="w-full py-3 bg-[#1D4D4F] hover:bg-[#143839] text-white text-xs font-bold rounded-full shadow-sm active:scale-98 transition touch-btn">
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
  const userBookmarkIds = new Set(@json($userBookmarks));
  // Sinkronkan bookmark dari localStorage
  try {
    const localSaved = JSON.parse(localStorage.getItem('sinikajian_bookmarks') || '[]');
    if (Array.isArray(localSaved)) {
      localSaved.forEach(id => userBookmarkIds.add(Number(id)));
    }
  } catch(e) {}
  const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  // Titik koordinat Ka'bah (Mekkah)
  const KAABA_COORDS = { lat: 21.4225, lon: 39.8262 };

  // Titik lokasi default (disinkronkan dari SiniKajianGeo atau cache)
  let userCoords = (() => {
    try {
      const cached = JSON.parse(localStorage.getItem('sk_geo_state') || 'null');
      if (cached && cached.lat && cached.lon) {
        return { lat: Number(cached.lat), lon: Number(cached.lon), name: cached.name || cached.city || 'Lokasi Saya' };
      }
    } catch(e) {}
    if (typeof window.SiniKajianGeo !== 'undefined') {
      return window.SiniKajianGeo.getCoords();
    }
    return { lat: -6.9175, lon: 107.6191, name: 'Mendeteksi Lokasi...' };
  })();

  // State Filter Aktif
  let activeSearch = '';
  let activeCategory = 'Semua';
  let activeRadius = 25; // km
  let activeDay = 'Semua';
  let activeAudience = 'Semua';
  let currentDetailEvent = null;

  // Helper: format tanggal YYYY-MM-DD menjadi "19 Okt 2026"
  function formatTanggalIndo(dateStr) {
    if (!dateStr) return '';
    const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    const parts = String(dateStr).split('T')[0].split('-');
    if (parts.length !== 3) return '';
    const day = parseInt(parts[2], 10);
    const month = months[parseInt(parts[1], 10) - 1] || '';
    return `${day} ${month} ${parts[0]}`;
  }

  // Helper: badge "X hari lagi" untuk kejelasan jadwal
  // Hari ini (merah), Besok (kuning), 2+ hari (hijau)
  function hariLagiBadge(dateStr) {
    if (!dateStr) return '';
    const today = new Date();
    today.setHours(0,0,0,0);
    const target = new Date(String(dateStr).split('T')[0]);
    target.setHours(0,0,0,0);
    const diff = Math.round((target - today) / 86400000);
    if (diff < 0) return '';
    let label = diff === 0 ? 'Hari ini' : diff === 1 ? 'Besok' : diff + ' hari lagi';
    let bg = diff === 0 ? '#DC2626' : diff === 1 ? '#EAB308' : '#16A34A';
    return `<span style="background:${bg};color:#fff;font-size:9px;font-weight:700;padding:2px 8px;border-radius:9999px;white-space:nowrap;flex-shrink:0;">${label}</span>`;
  }

  // Helper: validasi URL aman (cegah javascript: dll)
  function safeUrl(url) {
    if (!url) return '#';
    try {
      const u = new URL(url, window.location.origin);
      if (['http:', 'https:'].includes(u.protocol)) return url;
    } catch (e) {}
    return '#';
  }

  // Helper: escape HTML untuk cegah XSS
  function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }

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

  const SOURCE_META = {
    instagram: { label: 'Instagram', style: 'background:linear-gradient(45deg,#f09433,#e6683c,#dc2743,#cc2366,#bc1888)', glyph: 'IG' },
    facebook: { label: 'Facebook', style: 'background:#1877F2', glyph: 'f' },
    threads: { label: 'Threads', style: 'background:#111111', glyph: '@' },
  };

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

    // Urutkan berdasarkan tanggal terdekat (bukan jarak)
    processed.sort((a, b) => {
      const dateA = a.event_date || '9999-12-31';
      const dateB = b.event_date || '9999-12-31';
      if (dateA !== dateB) return dateA.localeCompare(dateB);
      // Jika tanggal sama, urutkan berdasarkan waktu
      return (a.time || '').localeCompare(b.time || '');
    });

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

    // Spotlight pada item terdekat jika ada (Figma Screen 1)
    if (filtered.length > 0 && !activeSearch) {
      const top = filtered[0];
      heroSpotlight.classList.remove('hidden');
      document.getElementById('spotlightTitle').innerText = top.title;
      document.getElementById('spotlightMasjid').innerHTML = `
        <svg class="w-3.5 h-3.5 text-[#E6F4F1] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
        <span>${top.masjid} • ${top.address ? top.address.split(',')[0] : 'Bandung'}</span>
      `;
      document.getElementById('spotlightTimePill').innerText = `${top.day.toUpperCase()}${top.event_date ? ', ' + formatTanggalIndo(top.event_date) : ''} • ${top.time}`;
      document.getElementById('spotlightDistance').innerText = `${top.distance.toFixed(1)} km`;
      if (top.poster_url) {
        document.getElementById('spotlightHeroImg').src = top.poster_url;
      }
      document.getElementById('spotlightDetailBtn').onclick = () => openDetailModalWithEvent(top);
      
      const isTopSaved = userBookmarkIds.has(Number(top.id));
      const topBookmarkBtn = document.getElementById('spotlightBookmarkBtn');
      if (topBookmarkBtn) {
        topBookmarkBtn.setAttribute('data-kajian-id', top.id);
        topBookmarkBtn.innerHTML = `
          <svg class="w-4 h-4" fill="${isTopSaved ? '#FCD34D' : 'none'}" stroke="${isTopSaved ? '#FCD34D' : 'currentColor'}" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
          </svg>
        `;
        topBookmarkBtn.onclick = (e) => {
          e.stopPropagation();
          toggleBookmarkAction(top.id, topBookmarkBtn);
        };
      }
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
      const srcMeta = SOURCE_META[ev.source] || SOURCE_META.facebook;
      const hasSource = ev.source_url && ev.source_url !== '#';
      const thumbUrl = ev.poster_url
        ? ev.poster_url + (ev.poster_url.includes('?') ? '&' : '?') + 'width=200&quality=70'
        : null;
      const flyerThumb = thumbUrl
        ? `<div class="w-16 h-16 rounded-2xl overflow-hidden shrink-0 bg-slate-100 border border-[#E5ECEB] shadow-xs">
             <img src="${thumbUrl}" alt="Flyer" class="w-full h-full object-cover" loading="lazy" decoding="async" onerror="this.parentElement.style.display='none'">
           </div>`
        : `<div class="w-16 h-16 rounded-2xl overflow-hidden shrink-0 bg-[#E6F4F1] text-[#1D4D4F] flex items-center justify-center font-bold text-xs border border-[#E5ECEB]">
             <span>${(ev.category || 'ILMU').substring(0, 4)}</span>
           </div>`;
      
      const card = document.createElement('article');
      card.className = 'bg-white rounded-2xl p-3.5 border border-[#E5ECEB] shadow-xs hover:border-[#1D4D4F]/50 transition-all cursor-pointer';
      card.onclick = () => openDetailModalWithId(ev.id);
      card.innerHTML = `
        <div class="flex items-start gap-3">
          ${flyerThumb}
          <div class="min-w-0 flex-1">
            <div class="flex items-center justify-between gap-1 mb-1">
              <div class="flex items-center gap-1 flex-wrap">
                <span class="text-[10px] font-bold text-[#1D4D4F] bg-[#E6F4F1] px-2 py-0.5 rounded-full whitespace-nowrap">
                  ${ev.day}${ev.event_date ? ', ' + formatTanggalIndo(ev.event_date) : ''} • ${ev.time}
                </span>
                ${hariLagiBadge(ev.event_date)}
              </div>
              <div class="flex items-center gap-1 text-[11px] font-bold text-[#1D4D4F]">
                <svg class="w-3 h-3 text-[#1D4D4F]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                </svg>
                <span>${ev.distance.toFixed(1)} km</span>
              </div>
            </div>
            
            <h3 class="text-xs sm:text-sm font-bold text-[#1C2224] leading-snug line-clamp-2 hover:text-[#1D4D4F]">
              ${escapeHtml(ev.title)}
            </h3>
            
            <p class="text-[11px] text-[#607072] mt-0.5 truncate">
              ${escapeHtml(ev.ustadz)} • ${escapeHtml(ev.masjid)}
            </p>
            
            <div class="flex items-center gap-1.5 mt-2 flex-wrap" onclick="event.stopPropagation();">
              <span class="text-[9px] font-medium text-[#607072] bg-[#F8FAF9] border border-[#E5ECEB] px-2 py-0.5 rounded-md">
                ${escapeHtml(ev.category || 'Umum')}
              </span>
              ${hasSource ? `
                <a href="${safeUrl(ev.source_url)}" target="_blank" rel="noopener noreferrer" title="Buka sumber asli di ${escapeHtml(srcMeta.label)}" class="inline-flex items-center gap-1 text-[9px] font-bold text-white px-2 py-0.5 rounded-full shadow-xs no-underline transition hover:opacity-90 cursor-pointer" style="${srcMeta.style}">
                  <span class="font-extrabold text-[9px]">${srcMeta.glyph}</span>
                  <span>${escapeHtml(srcMeta.label)} ↗</span>
                </a>
              ` : ''}
              ${ev.status === 'batal' ? '<span class="px-2 py-0.5 rounded text-[9px] font-bold bg-rose-50 text-rose-700">Dibatalkan</span>' : ''}
            </div>
          </div>
          
          <div class="self-center flex flex-col items-center gap-2 pl-1">
            <button data-kajian-id="${ev.id}" onclick="event.stopPropagation(); toggleBookmarkAction(${ev.id}, this)" class="bookmark-btn p-1.5 rounded-full ${isSaved ? 'text-amber-500 bg-amber-50' : 'text-[#607072] hover:text-[#1D4D4F]'} transition touch-btn" aria-label="Simpan">
              <svg class="w-4 h-4" fill="${isSaved ? 'currentColor' : 'none'}" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
              </svg>
            </button>
            <svg class="w-4 h-4 text-[#607072]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
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
    document.getElementById('modalSpeakerInitial').innerText = (ev.ustadz || 'U').replace(/^Ustadz\s+/i, '').charAt(0) || 'U';
    document.getElementById('modalMasjid').innerText = ev.masjid;
    document.getElementById('modalAddress').innerText = ev.address;
    let modalDateText = ev.day;
    if (ev.event_datetime) {
      try {
        const d = new Date(ev.event_datetime);
        if (!isNaN(d.getTime())) {
          const dayNum = d.getDate();
          const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
          modalDateText = `${ev.day}, ${dayNum} ${months[d.getMonth()]} ${d.getFullYear()}`;
        }
      } catch (e) {}
    }
    document.getElementById('modalDateTimeStr').innerText = `${modalDateText} • ${ev.time}`;
    document.getElementById('modalAudience').innerText = ev.audience ? `Terbuka untuk ${ev.audience}` : 'Terbuka untuk umum';
    document.getElementById('modalHeroSub').innerText = `${ev.ustadz} • ${ev.masjid}`;
    document.getElementById('modalDescription').innerText = ev.description || 'Kajian ilmiah ahlussunnah wal jamaah, terbuka untuk jamaah yang ingin menuntut ilmu syar\'i.';
    
    // Flyer Preview - hanya tampil jika ada flyer asli
    const flyerWrapper = document.getElementById('modalFlyerWrapper');
    const flyerImg = document.getElementById('modalFlyerImg');
    const flyerLink = document.getElementById('modalFlyerFullLink');
    if (ev.poster_url) {
      flyerImg.src = ev.poster_url;
      flyerLink.href = ev.poster_url;
      flyerWrapper.classList.remove('hidden');
    } else {
      flyerWrapper.classList.add('hidden');
    }

    // Sumber Asli Link
    const sourceBtn = document.getElementById('modalSourceBtn');
    const sourceGlyph = document.getElementById('modalSourceGlyph');
    const sourceLabel = document.getElementById('modalSourceLabel');
    const targetUrl = ev.source_url || ev.sourceUrl;
    if (targetUrl && targetUrl !== '#') {
      const srcMeta = SOURCE_META[ev.source] || SOURCE_META.facebook;
      sourceBtn.href = targetUrl;
      sourceBtn.style.cssText = srcMeta.style;
      sourceGlyph.innerText = srcMeta.glyph;
      sourceLabel.innerText = `Buka Sumber Asli di ${srcMeta.label} ↗`;
      sourceBtn.classList.remove('hidden');
    } else {
      sourceBtn.classList.add('hidden');
    }

    // Status tombol Simpan Kajian & Ikuti Majelis
    const isSaved = userBookmarkIds.has(Number(ev.id));
    syncAllBookmarkButtons(Number(ev.id), isSaved);

    const isFollowed = getFollowedMajelisList().includes(ev.masjid);
    updateModalFollowButtonState(isFollowed);

    document.getElementById('modalRouteBtn').onclick = () => openGoogleMapsRoute(ev.lat, ev.lon);
    document.getElementById('detailModal').classList.remove('hidden');
  }

  function getFollowedMajelisList() {
    try {
      return JSON.parse(localStorage.getItem('sinikajian_followed_majelis') || '[]');
    } catch(e) {
      return [];
    }
  }

  function setFollowedMajelisList(list) {
    try {
      localStorage.setItem('sinikajian_followed_majelis', JSON.stringify(list));
    } catch(e) {}
  }

  function updateModalFollowButtonState(isFollowed) {
    const btn = document.getElementById('modalFollowBtn');
    const lbl = document.getElementById('modalFollowLabel');
    if (!btn) return;
    if (isFollowed) {
      btn.className = 'w-full py-3 px-2 rounded-2xl bg-[#1D4D4F] hover:bg-[#153A3B] text-emerald-300 text-xs font-extrabold shadow-sm touch-btn flex items-center justify-center gap-1.5 transition';
      if (lbl) lbl.innerText = '✓ Majelis Diikuti';
    } else {
      btn.className = 'w-full py-3 px-2 rounded-2xl bg-[#1D4D4F] hover:bg-[#153A3B] text-white text-xs font-extrabold shadow-sm touch-btn flex items-center justify-center gap-1.5 transition';
      if (lbl) lbl.innerText = 'Ikuti Majelis';
    }
  }

  function toggleModalSaveAction() {
    if (!currentDetailEvent) return;
    toggleBookmarkAction(currentDetailEvent.id, document.getElementById('modalBookmarkBtn'));
  }

  function toggleModalBookmark() {
    toggleModalSaveAction();
  }

  function toggleModalFollowAction() {
    if (!currentDetailEvent || !currentDetailEvent.masjid) return;
    const masjid = currentDetailEvent.masjid;
    let list = getFollowedMajelisList();
    const wasFollowed = list.includes(masjid);
    const newFollowed = !wasFollowed;

    if (newFollowed) {
      if (!list.includes(masjid)) list.push(masjid);
    } else {
      list = list.filter(m => m !== masjid);
    }
    setFollowedMajelisList(list);
    updateModalFollowButtonState(newFollowed);
    showBookmarkToast(newFollowed ? `Mengikuti ${masjid}` : `Berhenti mengikuti ${masjid}`);

    fetch('{{ route("majelis.follow") }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': CSRF_TOKEN,
        'Accept': 'application/json'
      },
      body: JSON.stringify({ masjid: masjid })
    })
    .catch(err => console.warn('Gagal follow majelis ke server:', err));
  }

  // Alias backwards compatibility
  function submitModalAttendance() {
    toggleModalSaveAction();
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
        btn.className = 'category-chip whitespace-nowrap px-4 py-2 rounded-full font-bold transition-all bg-[#1D4D4F] text-white shadow-xs';
      } else {
        btn.className = 'category-chip whitespace-nowrap px-4 py-2 rounded-full font-medium transition-all bg-white text-[#607072] border border-[#E5ECEB] hover:border-[#1D4D4F] hover:text-[#1D4D4F]';
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

  // 6. Lokasi GPS & Kota (Auto Engine)
  function useGpsLocation() {
    if (window.SiniKajianGeo) {
      window.SiniKajianGeo.requestLiveGps();
    } else if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition((pos) => {
        userCoords = {
          lat: pos.coords.latitude,
          lon: pos.coords.longitude,
          name: 'Lokasi GPS Saya'
        };
        const el = document.getElementById('currentLocationName');
        if (el) el.innerText = userCoords.name;
        updatePrayerDisplay();
        renderKajianFeed();
      }, (err) => {
        alert('Gagal mendeteksi lokasi GPS.');
      }, { enableHighAccuracy: true });
    }
  }

  function setCityLocation(name, lat, lon) {
    if (window.SiniKajianGeo) {
      window.SiniKajianGeo.setManualCity(name, lat, lon);
    } else {
      userCoords = { lat, lon, name };
      const el = document.getElementById('currentLocationName');
      if (el) el.innerText = name;
      updatePrayerDisplay();
      renderKajianFeed();
    }
    closeLocationModal();
  }

  // Dengarkan event pembaruan GPS otomatis dari background
  window.addEventListener('sk:location-updated', (e) => {
    if (e.detail) {
      userCoords = {
        lat: e.detail.lat,
        lon: e.detail.lon,
        name: e.detail.name || 'Lokasi Saya'
      };
      const el = document.getElementById('currentLocationName');
      if (el) el.innerText = userCoords.name;
      updatePrayerDisplay();
      renderKajianFeed();
    }
  });

  // 7. Rute & Berbagi
  function openGoogleMapsRoute(lat, lon) {
    const url = `https://www.google.com/maps/dir/?api=1&destination=${lat},${lon}`;
    window.open(url, '_blank', 'noopener,noreferrer');
  }

  function shareCurrentKajian() {
    if (!currentDetailEvent) return;
    const shareText = `*${currentDetailEvent.title}*\nBersama: ${currentDetailEvent.ustadz}\nWaktu: ${currentDetailEvent.day}, ${currentDetailEvent.time}\nTempat: ${currentDetailEvent.masjid}\nAlamat: ${currentDetailEvent.address}\n\nDitemukan via Majelisku: https://majelisku.web.id`;

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

  function shareViaWhatsApp() {
    if (!currentDetailEvent) return;
    const shareText = `*${currentDetailEvent.title}*\nBersama: ${currentDetailEvent.ustadz}\nWaktu: ${currentDetailEvent.day}, ${currentDetailEvent.time}\nTempat: ${currentDetailEvent.masjid}\nAlamat: ${currentDetailEvent.address}\n\nDitemukan via Majelisku: https://majelisku.web.id`;
    window.open('https://wa.me/?text=' + encodeURIComponent(shareText), '_blank', 'noopener');
  }

  // 8. Bookmark Interaktif (Offline-first & Cloud-synced)
  function toggleBookmarkAction(kajianId, btnEl) {
    const numericId = Number(kajianId);
    const wasSaved = userBookmarkIds.has(numericId);
    const newSaved = !wasSaved;

    // 1. Optimistic update local Set
    if (newSaved) {
      userBookmarkIds.add(numericId);
    } else {
      userBookmarkIds.delete(numericId);
    }

    // 2. Persist in localStorage
    try {
      localStorage.setItem('sinikajian_bookmarks', JSON.stringify(Array.from(userBookmarkIds)));
    } catch(e) {}

    // 3. Update all related buttons across page
    syncAllBookmarkButtons(numericId, newSaved);
    showBookmarkToast(newSaved ? 'Kajian berhasil disimpan!' : 'Kajian dihapus dari tersimpan.');

    // 4. Sinkronkan ke server jika pengguna sudah login
    const isLoggedIn = {{ Auth::check() ? 'true' : 'false' }};
    if (isLoggedIn) {
      fetch(`/kajian/${numericId}/bookmark`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': CSRF_TOKEN,
          'Accept': 'application/json'
        }
      })
      .then(res => res.json())
      .then(data => {
        if (data && typeof data.saved === 'boolean') {
          if (data.saved !== newSaved) {
            syncAllBookmarkButtons(numericId, data.saved);
            if (data.saved) userBookmarkIds.add(numericId); else userBookmarkIds.delete(numericId);
          }
        }
      })
      .catch(err => {
        console.warn('Gagal sinkronisasi bookmark server:', err);
      });
    }
  }

  function syncAllBookmarkButtons(numericId, saved) {
    document.querySelectorAll(`.bookmark-btn[data-kajian-id="${numericId}"]`).forEach(btn => {
      updateBookmarkBtn(btn, saved);
    });
    const spotlightBtn = document.getElementById('spotlightBookmarkBtn');
    if (spotlightBtn && spotlightBtn.getAttribute('data-kajian-id') == numericId) {
      updateBookmarkBtn(spotlightBtn, saved);
    }
    if (currentDetailEvent && Number(currentDetailEvent.id) === numericId) {
      const modalBtn = document.getElementById('modalBookmarkBtn');
      if (modalBtn) updateBookmarkBtn(modalBtn, saved);

      const modalSaveBtn = document.getElementById('modalSaveBtn');
      const modalSaveLabel = document.getElementById('modalSaveLabel');
      const modalSaveIcon = document.getElementById('modalSaveIcon');
      if (modalSaveBtn) {
        if (saved) {
          modalSaveBtn.className = 'w-full py-3 px-2 rounded-2xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-extrabold shadow-sm touch-btn flex items-center justify-center gap-1.5 transition';
          if (modalSaveLabel) modalSaveLabel.innerText = '✓ Tersimpan';
          if (modalSaveIcon) modalSaveIcon.setAttribute('fill', 'currentColor');
        } else {
          modalSaveBtn.className = 'w-full py-3 px-2 rounded-2xl border-2 border-[#1D4D4F] text-[#1D4D4F] bg-[#E6F4F1] hover:bg-[#D1EAE5] text-xs font-extrabold shadow-2xs touch-btn flex items-center justify-center gap-1.5 transition';
          if (modalSaveLabel) modalSaveLabel.innerText = 'Simpan Kajian';
          if (modalSaveIcon) modalSaveIcon.setAttribute('fill', 'none');
        }
      }
    }
  }

  function updateBookmarkBtn(btnEl, saved) {
    if (!btnEl) return;
    const isSpotlight = btnEl.id === 'spotlightBookmarkBtn';
    const isModal = btnEl.id === 'modalBookmarkBtn';

    if (isSpotlight) {
      const svg = btnEl.querySelector('svg');
      if (svg) {
        svg.setAttribute('fill', saved ? '#FCD34D' : 'none');
        svg.setAttribute('stroke', saved ? '#FCD34D' : 'currentColor');
      }
    } else if (isModal) {
      if (saved) {
        btnEl.className = 'w-9 h-9 rounded-full bg-amber-50 border border-amber-200 text-amber-500 flex items-center justify-center touch-btn transition';
        const svg = btnEl.querySelector('svg');
        if (svg) svg.setAttribute('fill', 'currentColor');
      } else {
        btnEl.className = 'w-9 h-9 rounded-full bg-[#F8FAF9] hover:bg-[#E6F4F1] border border-[#E5ECEB] text-[#607072] flex items-center justify-center touch-btn transition';
        const svg = btnEl.querySelector('svg');
        if (svg) svg.setAttribute('fill', 'none');
      }
    } else {
      if (saved) {
        btnEl.classList.remove('text-[#607072]');
        btnEl.classList.add('text-amber-500', 'bg-amber-50');
        const svg = btnEl.querySelector('svg');
        if (svg) svg.setAttribute('fill', 'currentColor');
      } else {
        btnEl.classList.add('text-[#607072]');
        btnEl.classList.remove('text-amber-500', 'bg-amber-50');
        const svg = btnEl.querySelector('svg');
        if (svg) svg.setAttribute('fill', 'none');
      }
    }
  }

  let toastTimeout = null;
  function showBookmarkToast(message) {
    let toast = document.getElementById('appBookmarkToast');
    if (!toast) {
      toast = document.createElement('div');
      toast.id = 'appBookmarkToast';
      toast.className = 'fixed top-5 left-1/2 -translate-x-1/2 z-[100] px-4 py-2.5 rounded-full bg-[#1D4D4F] text-white text-xs font-bold shadow-lg transition-all duration-300 pointer-events-none opacity-0 -translate-y-2 flex items-center gap-2';
      document.body.appendChild(toast);
    }
    toast.innerHTML = `
      <svg class="w-4 h-4 text-amber-300" fill="currentColor" viewBox="0 0 24 24"><path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path></svg>
      <span>${message}</span>
    `;
    clearTimeout(toastTimeout);
    requestAnimationFrame(() => {
      toast.classList.remove('opacity-0', '-translate-y-2');
      toast.classList.add('opacity-100', 'translate-y-0');
    });
    toastTimeout = setTimeout(() => {
      toast.classList.remove('opacity-100', 'translate-y-0');
      toast.classList.add('opacity-0', '-translate-y-2');
    }, 2200);
  }

  // Inisialisasi awal
  document.addEventListener('DOMContentLoaded', () => {
    try {
      const cached = JSON.parse(localStorage.getItem('sk_geo_state') || 'null');
      if (cached && (cached.name || cached.city)) {
        userCoords = {
          lat: Number(cached.lat),
          lon: Number(cached.lon),
          name: cached.name || cached.city
        };
      } else if (window.SiniKajianGeo) {
        userCoords = window.SiniKajianGeo.getCoords();
      }
    } catch(e) {}

    const el = document.getElementById('currentLocationName');
    if (el) el.innerText = userCoords.name || 'Mendeteksi Lokasi...';

    // Sinkronisasi avatar header dari localStorage
    try {
      const localAvatar = localStorage.getItem('sinikajian_user_avatar');
      const headerImg = document.getElementById('headerUserAvatarImg');
      const headerInitial = document.getElementById('headerUserAvatarInitial');
      if (localAvatar && localAvatar.startsWith('data:image')) {
        if (headerImg) {
          headerImg.src = localAvatar;
          headerImg.classList.remove('hidden');
        }
        if (headerInitial) {
          headerInitial.classList.add('hidden');
        }
      }
    } catch(e) {}

    updatePrayerDisplay();
    renderKajianFeed();
    setInterval(updatePrayerDisplay, 60000); // Perbarui waktu sholat tiap menit
  });
</script>
@endsection
