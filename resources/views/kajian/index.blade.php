@extends('layouts.app')

@section('title', 'Majelisku | Jadwal Kajian Sunnah & Waktu Sholat')

@section('content')
<div class="max-w-md mx-auto px-4 pt-4 pb-8 space-y-5">
  
    <!-- Header Area (desain mockup 2026-10-29: lokasi + sholat berikutnya di
       baris atas, salam + judul + avatar di bawahnya) -->
  <header class="pt-2">
    <div class="flex items-center gap-2">
      <button type="button" onclick="openLocationModal()" class="inline-flex items-center gap-1.5 bg-[#1D4D4F] text-white text-xs font-bold px-3.5 py-2 rounded-full shadow-xs touch-btn transition shrink-0 max-w-[45%]">
        <span>📍</span>
        <span id="currentLocationName" class="truncate">Mendeteksi Lokasi...</span>
      </button>
      <a href="{{ route('kajian.sholat') }}" class="inline-flex items-center justify-center gap-1.5 bg-[#E6F4F1] dark:bg-[#162526] text-[#1D4D4F] dark:text-[#E8EDED] text-xs font-bold px-3.5 py-2 rounded-full shadow-xs touch-btn transition flex-1 min-w-0">
        <span>🕌</span>
        <span id="nextPrayerName" class="whitespace-nowrap">—</span>
        <span id="nextPrayerTime" class="whitespace-nowrap">—</span>
        <span class="text-[#607072] dark:text-[#9BA8A9]">•</span>
        <span id="nextPrayerCountdown" class="whitespace-nowrap">—</span>
      </a>
    </div>

    <div class="flex items-start justify-between gap-3 mt-3">
      <div class="min-w-0">
        <p class="text-xs text-[#607072] dark:text-[#9BA8A9] font-medium">Assalamu'alaikum, {{ Auth::check() ? Auth::user()->name : 'Tamu' }} 👋</p>
        <h1 class="text-[26px] leading-tight font-extrabold text-[#1D4D4F] dark:text-[#E8EDED] tracking-tight mt-0.5">Temukan Majelis Ilmu</h1>
      </div>

      <div class="flex items-center gap-2 shrink-0">
        <!-- Tombol Masukan Info Kajian (tetap: fitur kirim info kajian) -->
        <button type="button" onclick="openSubmitModal()" title="Masukan Info Kajian" class="w-10 h-10 rounded-full bg-white dark:bg-[#162526] border border-[#E5ECEB] dark:border-[#1E2E2F] text-[#1D4D4F] flex items-center justify-center shadow-xs touch-btn transition" aria-label="Masukan Info Kajian">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 4v16m8-8H4"></path>
          </svg>
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
    </div>
  </header>

  

    <!-- Kolom Pencarian (desain mockup 2026-10-29: pill + tombol filter + kalender) -->
  <section>
    <div class="flex items-center gap-2">
      <div class="relative flex-1">
        <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-[#607072] dark:text-[#9BA8A9]">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
          </svg>
        </span>
        <input
          type="text"
          id="searchInput"
          placeholder="Cari kajian, ustadz, masjid..."
          class="w-full pl-11 pr-10 py-3 bg-white dark:bg-[#162526] border border-[#E5ECEB] dark:border-[#1E2E2F] rounded-full text-sm text-[#1C2224] dark:text-[#E8EDED] placeholder-[#607072] focus:outline-none focus:ring-2 focus:ring-[#1D4D4F] focus:border-[#1D4D4F] shadow-xs"
          oninput="onSearchFilterChange()"
        >
        <button
          id="clearSearchBtn"
          onclick="clearSearchInput()"
          class="hidden absolute inset-y-0 right-0 pr-4 flex items-center text-[#607072] dark:text-[#9BA8A9] hover:text-[#1C2224] dark:text-[#E8EDED]"
          aria-label="Bersihkan pencarian"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
      </div>
      <button type="button" onclick="document.getElementById('filterChipsSection').scrollIntoView({behavior:'smooth',block:'center'})" title="Filter" aria-label="Filter" class="w-11 h-11 rounded-2xl bg-white dark:bg-[#162526] border border-[#E5ECEB] dark:border-[#1E2E2F] text-[#1D4D4F] flex items-center justify-center shrink-0 shadow-xs touch-btn transition">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
      </button>
      <button id="viewModeToggle" onclick="openCalendarModal()" class="w-11 h-11 rounded-2xl bg-white dark:bg-[#162526] border border-[#E5ECEB] dark:border-[#1E2E2F] text-[#1D4D4F] flex items-center justify-center shrink-0 shadow-xs touch-btn transition" title="Buka kalender">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
        </svg>
        <span class="hidden" id="viewModeLabel">Kalender</span>
      </button>
    </div>
  </section>

  @php
    $skMonths = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    $skTanggalNoYear = function ($date) use ($skMonths) {
      if (!$date) return '';
      $p = explode('-', substr((string) $date, 0, 10));
      if (count($p) !== 3) return '';
      return ((int) $p[2]) . ' ' . ($skMonths[((int) $p[1]) - 1] ?? '');
    };
    $skTanggalIndo = function ($date) use ($skMonths) {
      if (!$date) return '';
      $p = explode('-', substr((string) $date, 0, 10));
      if (count($p) !== 3) return '';
      return ((int) $p[2]) . ' ' . ($skMonths[((int) $p[1]) - 1] ?? '') . ' ' . $p[0];
    };
    $skHariLagi = function ($date) {
      if (!$date) return '';
      $today = \Carbon\Carbon::now('Asia/Jakarta')->startOfDay();
      $target = \Carbon\Carbon::parse(substr((string) $date, 0, 10))->startOfDay();
      $diff = $today->diffInDays($target, false);
      if ($diff < 0) return '';
      $label = $diff === 0 ? 'Hari ini' : ($diff === 1 ? 'Besok' : $diff . ' hari lagi');
      $bg = $diff === 0 ? '#FEE2E2' : ($diff === 1 ? '#FEF3C7' : '#DCFCE7');
      $fg = $diff === 0 ? '#DC2626' : ($diff === 1 ? '#92400E' : '#166534');
      return '<span style="background:' . $bg . ';color:' . $fg . ';font-size:9px;font-weight:700;padding:2px 8px;border-radius:9999px;white-space:nowrap;flex-shrink:0;">' . $label . '</span>';
    };
    // URL transform Supabase langsung (fallback bila proxy /api/thumbs gagal).
    $skThumbDirect = function ($url, $w = 200) {
      if (!$url) return null;
      $parts = parse_url($url);
      if (!isset($parts['host']) || !str_contains($parts['host'], 'supabase.co')) return $url;
      if (!isset($parts['path']) || !str_contains($parts['path'], '/storage/v1/object/public/')) return $url;
      $path = str_replace('/storage/v1/object/public/', '/storage/v1/render/image/public/', $parts['path']);
      return ($parts['scheme'] ?? 'https') . '://' . $parts['host'] . $path . '?width=' . (int) $w . '&quality=60&format=webp&resize=cover';
    };
    // Thumbnail lewat proxy edge Vercel (LCP fix 2026-10-27): Supabase
    // menyajikan gambar no-cache/DYNAMIC, jadi thumb diarahkan ke
    // /api/thumbs/{id}-{w}.webp yang di-cache edge. Hanya untuk flyer di
    // bucket flyers/; URL Supabase lain tetap transform langsung.
    $skThumb = function ($url, $w = 200) use ($skThumbDirect) {
      if (!$url) return null;
      $parts = parse_url($url);
      if (!isset($parts['host']) || !str_contains($parts['host'], 'supabase.co')) return $url;
      if (!isset($parts['path']) || !str_contains($parts['path'], '/storage/v1/object/public/')) return $url;
      if (!str_contains($parts['path'], '/storage/v1/object/public/flyers/')) return $skThumbDirect($url, $w);
      $fid = preg_replace('/\.[^.]+$/', '', basename($parts['path']));
      if (!ctype_digit((string) $fid)) return $skThumbDirect($url, $w);
      return '/api/thumbs/' . $fid . '-' . (int) $w . '.webp';
    };
    $skSourceMeta = function ($source) {
      $map = [
        'instagram' => ['label' => 'Instagram', 'style' => 'background:linear-gradient(45deg,#f09433,#e6683c,#dc2743,#cc2366,#bc1888)', 'glyph' => 'IG'],
        'facebook' => ['label' => 'Facebook', 'style' => 'background:#1877F2', 'glyph' => 'f'],
        'threads' => ['label' => 'Threads', 'style' => 'background:#111111', 'glyph' => '@'],
      ];
      return $map[$source] ?? $map['facebook'];
    };
    $skSafeUrl = function ($url) {
      if (!$url) return '#';
      $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
      return in_array($scheme, ['http', 'https'], true) ? $url : '#';
    };
  @endphp

  {{-- Preload gambar LCP: slide pertama carousel (masuk <head> via stack styles) --}}
  @if(!empty($serverSpotlight))
    @php $skPreloadImg = $skThumb($serverSpotlight[0]['poster_url'] ?? null, 800) ?: 'https://images.unsplash.com/photo-1542816417-0983c9c9ad53?auto=format&fit=crop&w=800&q=80'; @endphp
    @push('styles')
    <link rel="preload" as="image" fetchpriority="high" href="{{ $skPreloadImg }}">
    @endpush
  @endif

  <!-- Hero Spotlight: Majelis Pilihan Terdekat (Figma Screen 1: KAJIAN PILIHAN) -->
  <!-- Banner carousel: berdiri sendiri sebagai track horizontal, bisa di-swipe -->
  <section id="heroSpotlightContainer">
    <div id="spotlightViewport" class="overflow-hidden rounded-3xl" style="touch-action:pan-y;min-height:240px;">
      <div id="spotlightTrack" class="flex will-change-transform" @if(!empty($serverSpotlight)) data-server-rendered="1" @endif>
        @if(!empty($serverSpotlight))
        {{-- Slide server-render (LCP 2026-10-27): 3 slide pertama tampilan default
             dirender di server agar gambar LCP (slide 1, fetchpriority high)
             ketemu saat parse HTML, bukan setelah JS jalan. JS (renderKajianFeed)
             mempertahankan slide ini bila id-nya sama persis dengan hasil
             hitungnya; kalau beda, dibangun ulang seperti biasa. --}}
        @foreach($serverSpotlight as $si => $s)
          @php
            $sThumb = $skThumb($s['poster_url'] ?? null, 800) ?: 'https://images.unsplash.com/photo-1542816417-0983c9c9ad53?auto=format&fit=crop&w=800&q=80';
            $sAddr = !empty($s['address']) ? explode(',', $s['address'])[0] : 'Lokasi belum tersedia';
          @endphp
        <div class="spot-slide relative shrink-0 cursor-pointer overflow-hidden bg-[#153A3B]" style="width:100%;" data-slide-idx="{{ $si }}" data-kajian-id="{{ $s['id'] }}">
          <img src="{{ $sThumb }}" data-poster="{{ $s['poster_url'] ?? '' }}" alt="Kajian Pilihan" class="w-full h-60 object-cover opacity-90" draggable="false" @if($si === 0) fetchpriority="high" @else loading="lazy" @endif decoding="async" onerror="thumbFallback(this, 800)">
          <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/45 to-black/20"></div>
          <div class="absolute top-3 inset-x-3 flex items-center justify-between gap-2">
            <span class="px-3 py-1.5 rounded-full text-[10px] font-extrabold tracking-wider uppercase bg-black/70 text-white shadow-xs">⭐ KAJIAN PILIHAN</span>
            <span data-dist class="px-3 py-1.5 rounded-full text-[10px] font-bold bg-black/60 text-white shadow-xs whitespace-nowrap">{{ isset($s['distance']) ? number_format((float) $s['distance'], 1) . ' km' : '— km' }}</span>
          </div>
          @if(!empty($s['is_cancelled']))
          <div class="absolute inset-0 flex items-center justify-center pointer-events-none" style="z-index:20;">
            <span style="border:4px solid #dc2626;color:#dc2626;font-weight:900;font-size:30px;letter-spacing:4px;padding:6px 22px;border-radius:8px;transform:rotate(-12deg);background:rgba(255,255,255,0.78);text-transform:uppercase;">Dibatalkan</span>
          </div>
          @endif
          <div class="absolute bottom-0 inset-x-0 p-4">
            <h2 class="text-lg font-extrabold text-white leading-snug line-clamp-2">{{ $s['title'] }}</h2>
            <p class="text-sm font-bold text-amber-300 mt-1 truncate">{{ $s['ustadz'] ?? '' }}</p>
            <p class="text-xs text-white/90 mt-1 truncate">{{ $s['day'] ?? '' }}{{ $s['event_date'] ? ', ' . $skTanggalNoYear($s['event_date']) : '' }} • {{ $s['time'] ?? '' }} • {{ $s['masjid'] }}</p>
            <span class="inline-flex items-center gap-1 mt-3 px-4 py-2 rounded-full bg-white text-[#1D4D4F] text-xs font-extrabold shadow">Lihat Detail Kajian →</span>
          </div>
        </div>
        @endforeach
        @else
        <!-- Skeleton: ruang hero dicadangkan sejak paint pertama agar tidak
             ada lompatan tata letak (CLS) saat JS mengisi carousel (2026-10-25) -->
        <div class="w-full h-52 bg-[#153A3B] animate-pulse shrink-0" aria-hidden="true"></div>
        @endif
      </div>
    </div>
  </section>

  <!-- Filter Chips (desain mockup 2026-10-29: di bawah spotlight, bisa di-scroll) -->
  <section id="filterChipsSection" class="space-y-2.5">
    <div class="flex items-center gap-2 overflow-x-auto no-scrollbar py-0.5 text-xs">
      <button onclick="setCategoryFilter('Semua')" class="category-chip whitespace-nowrap px-4 py-2 rounded-full font-bold transition-all bg-[#1D4D4F] text-white shadow-xs" data-cat="Semua">
        Untukmu
      </button>
      <div class="relative shrink-0">
        <select id="radiusSelect" onchange="setRadiusFilter(parseInt(this.value, 10))" aria-label="Radius pencarian" class="appearance-none whitespace-nowrap pl-4 pr-8 py-2 rounded-full font-bold bg-white dark:bg-[#162526] text-[#1D4D4F] dark:text-[#E8EDED] border border-[#E5ECEB] dark:border-[#1E2E2F] transition cursor-pointer">
          <option value="5">Radius: 5 km</option>
          <option value="15">Radius: 15 km</option>
          <option value="25" selected>Radius: 25 km</option>
          <option value="999">Radius: Semua</option>
        </select>
        <span class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-[#1D4D4F] text-[10px]">▾</span>
      </div>
      <button id="todayToggle" onclick="setTodayFilter()" class="whitespace-nowrap px-4 py-2 rounded-full font-medium transition-all bg-white text-[#607072] dark:text-[#9BA8A9] border border-[#E5ECEB] hover:border-[#1D4D4F] hover:text-[#1D4D4F] shrink-0">
        Hari Ini
      </button>
      @foreach($categories as $cat)
      @if(!preg_match('/^kajian/i', trim($cat)))
      <button onclick="setCategoryFilter('{{ $cat }}')" class="category-chip whitespace-nowrap px-4 py-2 rounded-full font-medium transition-all bg-white dark:bg-[#162526] text-[#607072] dark:text-[#9BA8A9] border border-[#E5ECEB] hover:border-[#1D4D4F] hover:text-[#1D4D4F]" data-cat="{{ $cat }}">
        {{ $cat }}
      </button>
      @endif
      @endforeach
    </div>

    <div class="flex items-center gap-2 overflow-x-auto no-scrollbar py-0.5 text-xs text-[#607072] dark:text-[#9BA8A9]">
      <span class="text-[11px] font-medium whitespace-nowrap shrink-0">Urutkan:</span>
      <button onclick="setSortMode('tanggal')" class="sort-chip px-3.5 py-1.5 rounded-xl border border-[#1D4D4F] bg-[#E6F4F1] text-[#1D4D4F] font-bold whitespace-nowrap shrink-0" data-sort="tanggal">Tanggal</button>
      <button onclick="setSortMode('jarak')" class="sort-chip px-3.5 py-1.5 rounded-xl border border-[#E5ECEB] dark:border-[#1E2E2F] bg-white dark:bg-[#162526] font-medium hover:border-[#1D4D4F] whitespace-nowrap shrink-0" data-sort="jarak">Terdekat</button>
      <button id="favUstadzToggle" onclick="toggleFavUstadzFilter()" class="px-2.5 py-1 rounded-xl border border-[#E5ECEB] dark:border-[#1E2E2F] bg-white dark:bg-[#162526] font-medium hover:border-amber-500 flex items-center gap-1 transition" title="Hanya ustadz favorit">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
        </svg>
        <span class="text-[11px]">Favorit</span>
      </button>
    </div>
  </section>

  <!-- Feed Section Header (desain mockup 2026-10-29) -->
  <div class="flex items-center justify-between pt-1">
    <h2 class="text-base font-extrabold text-[#1C2224] dark:text-[#E8EDED]">Kajian Terdekat <span id="filteredCountText" class="text-[#607072] dark:text-[#9BA8A9] font-bold">({{ $kajians->count() }})</span></h2>
    <button onclick="resetAllFilters()" class="text-xs font-bold text-[#1D4D4F] hover:underline shrink-0">Lihat Semua</button>
  </div>

  @if($kajianLoadError ?? false)
  <!-- Status error muat data (FE-H6): dibedakan dari keadaan memang kosong -->
  <div class="mb-3 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-center" role="alert">
    <p class="text-sm font-bold text-red-700">Gagal memuat data kajian</p>
    <p class="text-xs text-red-600 mt-0.5">Server data sedang tidak terjangkau. Periksa koneksi internetmu lalu coba lagi.</p>
    <button onclick="location.reload()" class="mt-2 px-4 py-1.5 rounded-full bg-red-600 text-white text-xs font-bold">Muat Ulang</button>
  </div>
  @endif

  <!-- Feed Daftar Kajian -->
  {{-- Kartu server-render (B+C 2026-10-26): 12 kartu pertama tampilan default
       dirender di server dari koleksi yang sama (tanpa query tambahan),
       agar cat pertama sudah berisi konten, bukan skeleton. JS
       (renderKajianFeed) mempertahankan kartu ini bila urutannya sama
       persis dengan hasil hitungnya; kalau beda, dibangun ulang seperti
       biasa. Bila $serverCards kosong, skeleton lama dipakai fallback. --}}
  <section class="space-y-3" id="kajianCardsList" @if(!empty($serverCards)) data-server-rendered="1" @endif>
    @if(!empty($serverCards))
      @foreach($serverCards as $c)
        @php
          $cThumb = $skThumb($c['poster_url'] ?? null, 200);
          $cMeta = $skSourceMeta($c['source'] ?? 'instagram');
          $cHasSource = !empty($c['source_url']) && $c['source_url'] !== '#';
        @endphp
        <article data-kajian-id="{{ $c['id'] }}" class="bg-white dark:bg-[#162526] rounded-3xl p-3 border border-[#E5ECEB] dark:border-[#1E2E2F] shadow-xs hover:border-[#1D4D4F]/50 transition-all cursor-pointer{{ !empty($c['is_cancelled']) ? ' !border-red-300' : '' }}" style="position:relative;overflow:hidden;">
          @if(!empty($c['is_cancelled']))
          <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;pointer-events:none;z-index:10;">
            <span style="border:4px solid #dc2626;color:#dc2626;font-weight:900;font-size:28px;letter-spacing:4px;padding:6px 20px;border-radius:8px;transform:rotate(-12deg);background:rgba(255,255,255,0.75);text-transform:uppercase;">Dibatalkan</span>
          </div>
          @endif
          <div class="flex items-start gap-3"@if(!empty($c['is_cancelled'])) style="opacity:0.45;filter:grayscale(0.6);"@endif>
            @if($cThumb)
            <div class="w-[88px] h-[88px] rounded-2xl overflow-hidden shrink-0 bg-slate-100 border border-[#E5ECEB] shadow-xs">
              <img src="{{ $cThumb }}" data-poster="{{ $c['poster_url'] ?? '' }}" alt="Flyer" class="w-full h-full object-cover" @if($loop->index >= 2) loading="lazy" @endif decoding="async" onerror="thumbFallback(this, 200, true)">
            </div>
            @else
            <div class="w-[88px] h-[88px] rounded-2xl overflow-hidden shrink-0 bg-[#E6F4F1] text-[#1D4D4F] flex items-center justify-center font-bold text-xs border border-[#E5ECEB]">
              <span>{{ mb_substr($c['category'] ?: 'ILMU', 0, 4) }}</span>
            </div>
            @endif
            <div class="min-w-0 flex-1">
              <div class="flex items-center justify-between gap-2">
                {!! $skHariLagi($c['event_date']) !!}
                <span data-dist class="text-[11px] font-bold text-[#607072] dark:text-[#9BA8A9] whitespace-nowrap ml-auto">{{ isset($c['distance']) ? number_format((float) $c['distance'], 1) . ' km' : '— km' }}</span>
              </div>
              <p class="text-[11px] text-[#607072] dark:text-[#9BA8A9] mt-1 truncate">{{ $c['day'] }}{{ $c['event_date'] ? ', ' . $skTanggalNoYear($c['event_date']) : '' }} • {{ $c['time'] }}</p>
              <h3 class="text-sm font-bold text-[#1C2224] dark:text-[#E8EDED] leading-snug line-clamp-2 hover:text-[#1D4D4F] mt-0.5">{{ $c['title'] }}</h3>
              <p class="text-[11px] text-[#607072] dark:text-[#9BA8A9] mt-0.5 truncate">{{ $c['ustadz'] }} • {{ $c['masjid'] }}</p>
              <div class="flex items-center gap-1.5 mt-2 flex-wrap" onclick="event.stopPropagation();">
                <span class="text-[10px] font-bold text-[#1D4D4F] bg-[#E6F4F1] px-2 py-0.5 rounded-full">{{ $c['category'] ?: 'Umum' }}</span>
                @if($cHasSource)
                <a href="{{ $skSafeUrl($c['source_url']) }}" target="_blank" rel="noopener noreferrer" title="Buka sumber asli di {{ $cMeta['label'] }}" aria-label="Buka sumber asli di {{ $cMeta['label'] }} — kajian #{{ $c['id'] }}" class="inline-flex items-center text-[10px] font-bold text-[#1C2224] bg-[#F3F4F6] px-2 py-0.5 rounded-full no-underline transition hover:opacity-90 cursor-pointer">
                  <span>{{ $cMeta['label'] }} ↗</span>
                </a>
                @endif
              </div>
            </div>
          </div>
        </article>
      @endforeach
    @else
    <!-- Skeleton loading (diganti JS saat data siap) -->
    <div id="feedSkeleton" class="space-y-3" aria-hidden="true">
      <div class="bg-white dark:bg-[#162526] rounded-2xl p-3.5 border border-[#E5ECEB] dark:border-[#1E2E2F]">
        <div class="flex items-start gap-3">
          <div class="w-[88px] h-[88px] rounded-2xl bg-slate-200 dark:bg-[#2A3F40] animate-pulse shrink-0"></div>
          <div class="flex-1 space-y-2">
            <div class="h-3 bg-slate-200 rounded-full animate-pulse w-2/3"></div>
            <div class="h-4 bg-slate-200 rounded animate-pulse w-full"></div>
            <div class="h-3 bg-slate-100 dark:bg-[#223336] rounded animate-pulse w-1/2"></div>
          </div>
        </div>
      </div>
      <div class="bg-white dark:bg-[#162526] rounded-2xl p-3.5 border border-[#E5ECEB] dark:border-[#1E2E2F]">
        <div class="flex items-start gap-3">
          <div class="w-[88px] h-[88px] rounded-2xl bg-slate-200 dark:bg-[#2A3F40] animate-pulse shrink-0"></div>
          <div class="flex-1 space-y-2">
            <div class="h-3 bg-slate-200 rounded-full animate-pulse w-1/2"></div>
            <div class="h-4 bg-slate-200 rounded animate-pulse w-5/6"></div>
            <div class="h-3 bg-slate-100 dark:bg-[#223336] rounded animate-pulse w-2/3"></div>
          </div>
        </div>
      </div>
      <div class="bg-white dark:bg-[#162526] rounded-2xl p-3.5 border border-[#E5ECEB] dark:border-[#1E2E2F]">
        <div class="flex items-start gap-3">
          <div class="w-[88px] h-[88px] rounded-2xl bg-slate-200 dark:bg-[#2A3F40] animate-pulse shrink-0"></div>
          <div class="flex-1 space-y-2">
            <div class="h-3 bg-slate-200 rounded-full animate-pulse w-3/5"></div>
            <div class="h-4 bg-slate-200 rounded animate-pulse w-full"></div>
            <div class="h-3 bg-slate-100 dark:bg-[#223336] rounded animate-pulse w-1/3"></div>
          </div>
        </div>
      </div>
    </div>
    @endif
  </section>

  <!-- Empty State (R-27 compliant) -->
  <div id="emptyStateContainer" class="hidden text-center py-12 px-4 bg-white dark:bg-[#162526] rounded-2xl border border-slate-200 dark:border-[#1E2E2F]">
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
  <div class="bg-white dark:bg-[#162526] w-full max-w-lg rounded-t-3xl sm:rounded-3xl overflow-y-auto overscroll-contain no-scrollbar shadow-2xl p-4 sm:p-5 space-y-4" style="touch-action: pan-y; -webkit-overflow-scrolling: touch; max-height:92vh; max-height:92dvh;">
    <!-- Mobile Drag Handle (juga gagang swipe-down untuk menutup modal) -->
    <div id="detailDragHandle" class="mx-auto sm:hidden py-2" style="touch-action:none;">
      <div class="w-12 h-1 bg-slate-200 rounded-full mx-auto"></div>
    </div>
    
    <!-- Top Navigation Bar: Back Chevron, Title, Bookmark & Share Buttons -->
    <div class="flex items-center justify-between border-b border-[#E5ECEB] pb-3">
      <button onclick="closeDetailModal()" class="w-9 h-9 rounded-full bg-[#F8FAF9] hover:bg-[#E6F4F1] border border-[#E5ECEB] text-[#1D4D4F] flex items-center justify-center touch-btn" aria-label="Kembali">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
      </button>
      <h2 class="text-sm font-bold text-[#1C2224] dark:text-[#E8EDED]">Detail Kajian</h2>
      <div class="flex items-center gap-1.5">
        <button id="modalBookmarkBtn" onclick="toggleModalBookmark()" class="w-9 h-9 rounded-full bg-[#F8FAF9] hover:bg-[#E6F4F1] border border-[#E5ECEB] text-[#607072] dark:text-[#9BA8A9] flex items-center justify-center touch-btn transition" aria-label="Tandai / Simpan">
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

      <!-- Stempel DIBATALKAN -->
      <div id="modalCancelledStamp" class="hidden absolute inset-0 items-center justify-center pointer-events-none" style="z-index:20;">
        <span style="border:4px solid #dc2626;color:#dc2626;font-weight:900;font-size:32px;letter-spacing:4px;padding:8px 24px;border-radius:8px;transform:rotate(-12deg);background:rgba(255,255,255,0.78);text-transform:uppercase;">Dibatalkan</span>
      </div>
      
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
      <span id="modalAudience" class="px-3 py-1 rounded-full text-[10px] font-medium bg-[#F8FAF9] text-[#607072] dark:text-[#9BA8A9] border border-[#E5ECEB]">
        Terbuka untuk umum
      </span>
      <span class="px-3 py-1 rounded-full text-[10px] font-medium bg-emerald-50 text-emerald-800 border border-emerald-100">
        Gratis
      </span>
    </div>

    <!-- Main Title Heading -->
    <div>
      <h1 id="modalTitle" class="text-lg font-extrabold text-[#1C2224] dark:text-[#E8EDED] leading-snug">Judul Kajian</h1>
    </div>

    <!-- Detail Cards List (Figma Screen 2) -->
    <div class="space-y-2.5">
      <!-- Card 1: Tanggal & Waktu -->
      <div class="bg-[#F8FAF9] p-3 rounded-2xl border border-[#E5ECEB] flex items-start gap-3">
        <div class="w-9 h-9 rounded-full bg-[#E6F4F1] text-[#1D4D4F] flex items-center justify-center shrink-0 mt-0.5">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-[10px] font-extrabold uppercase tracking-wider text-[#607072] dark:text-[#9BA8A9]">TANGGAL & WAKTU</p>
          <p id="modalDateTimeStr" class="text-xs font-bold text-[#1C2224] dark:text-[#E8EDED] mt-0.5">Ahad • 09:00 WIB</p>
          <p class="text-[11px] text-[#607072] dark:text-[#9BA8A9] mt-0.5">Hadir 15 menit lebih awal untuk menyempurnakan shaf</p>
        </div>
      </div>

      <!-- Card 2: Tempat Kajian -->
      <div class="bg-[#F8FAF9] p-3 rounded-2xl border border-[#E5ECEB] flex items-start gap-3">
        <div class="w-9 h-9 rounded-full bg-[#E6F4F1] text-[#1D4D4F] flex items-center justify-center shrink-0 mt-0.5">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-[10px] font-extrabold uppercase tracking-wider text-[#607072] dark:text-[#9BA8A9]">TEMPAT KAJIAN</p>
          <p id="modalMasjid" class="text-xs font-bold text-[#1C2224] dark:text-[#E8EDED] mt-0.5">Masjid</p>
          <p id="modalAddress" class="text-[11px] text-[#607072] dark:text-[#9BA8A9] mt-0.5">Alamat lengkap...</p>
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
          <p class="text-[10px] font-extrabold uppercase tracking-wider text-[#607072] dark:text-[#9BA8A9]">PEMBICARA / USTADZ</p>
          <p id="modalSpeaker" class="text-xs font-bold text-[#1C2224] dark:text-[#E8EDED] mt-0.5 truncate">Ustadz</p>
          <p class="text-[10px] text-[#607072] dark:text-[#9BA8A9]">Pengasuh Kajian Sunnah</p>
        </div>
        <button id="modalFavUstadzBtn" onclick="if(currentDetailEvent) toggleFavUstadz(currentDetailEvent.ustadz)" class="p-2 rounded-full text-[#607072] dark:text-[#9BA8A9] hover:text-amber-500 transition touch-btn shrink-0" aria-label="Ustadz favorit" title="Tandai ustadz favorit">
          <svg id="modalFavUstadzIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
          </svg>
        </button>
      </div>
    </div>

    <!-- Deskripsi & Pokok Bahasan -->
    <div class="bg-white p-3.5 rounded-2xl border border-[#E5ECEB] space-y-2">
      <h4 class="text-xs font-bold text-[#1C2224] dark:text-[#E8EDED]">Apa yang akan dibahas?</h4>
      <p id="modalDescription" class="text-xs text-[#607072] dark:text-[#9BA8A9] leading-relaxed">
        Kajian ilmiah sunnah ahlussunnah wal jamaah, terbuka untuk jamaah ikhwan dan akhwat yang ingin memperdalam ilmu syar'i.
      </p>
      <div class="space-y-1.5 pt-1 text-[11px] text-[#1C2224] dark:text-[#E8EDED]">
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
    <a id="modalSourceBtn" href="#" target="_blank" rel="noopener noreferrer" class="hidden w-full touch-btn py-2.5 rounded-2xl text-white font-bold text-xs flex items-center justify-center gap-2 no-underline transition hover:opacity-90">
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

        <!-- Tombol 3: Tambah ke Kalender (full width, di tengah) -->
        <button onclick="if(currentDetailEvent) downloadICS(currentDetailEvent.id)" class="col-span-2 w-full py-3 px-2 rounded-2xl bg-[#E6F4F1] dark:bg-[#1E2E2F] hover:bg-[#D1EAE5] text-[#1D4D4F] dark:text-[#7FC4BC] text-xs font-extrabold shadow-sm touch-btn flex items-center justify-center gap-1.5 transition">
          <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
          </svg>
          <span class="truncate">Tambah ke Kalender</span>
        </button>
      </div>
      <p class="text-[10px] text-center text-[#607072] dark:text-[#9BA8A9]">
        Kajian tersimpan di agenda pribadi dan majelis tercatat diikuti di profil.
      </p>
    </div>
  </div>
</div>

<!-- Modal: Kalender Kajian -->
<div id="calendarModal" class="hidden fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/60 backdrop-blur-sm p-0 sm:p-4">
  <div class="bg-white dark:bg-[#162526] w-full max-w-md rounded-t-3xl sm:rounded-2xl p-5 space-y-4 max-h-[85vh] overflow-y-auto no-scrollbar">
    <div class="flex items-center justify-between border-b border-[#E5ECEB] dark:border-[#1E2E2F] pb-3">
      <h3 class="text-sm font-bold text-[#1C2224] dark:text-[#E8EDED]">Kalender Kajian</h3>
      <button onclick="closeCalendarModal()" class="text-[#607072] dark:text-[#9BA8A9] hover:text-[#1C2224] dark:text-[#E8EDED] p-1" aria-label="Tutup Kalender">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
      </button>
    </div>

    <!-- Navigasi bulan -->
    <div class="flex items-center justify-between">
      <button onclick="changeCalendarMonth(-1)" class="w-9 h-9 rounded-full bg-[#F8FAF9] dark:bg-[#1E2E2F] border border-[#E5ECEB] dark:border-[#1E2E2F] flex items-center justify-center text-[#1D4D4F] dark:text-[#7FC4BC] touch-btn" aria-label="Bulan sebelumnya">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
      </button>
      <h4 id="calendarMonthLabel" class="text-sm font-extrabold text-[#1C2224] dark:text-[#E8EDED]"></h4>
      <button onclick="changeCalendarMonth(1)" class="w-9 h-9 rounded-full bg-[#F8FAF9] dark:bg-[#1E2E2F] border border-[#E5ECEB] dark:border-[#1E2E2F] flex items-center justify-center text-[#1D4D4F] dark:text-[#7FC4BC] touch-btn" aria-label="Bulan berikutnya">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
      </button>
    </div>

    <!-- Grid kalender -->
    <div class="grid grid-cols-7 gap-1 text-center text-[10px] font-bold text-[#607072] dark:text-[#9BA8A9] uppercase">
      <div>Min</div><div>Sen</div><div>Sel</div><div>Rab</div><div>Kam</div><div>Jum</div><div>Sab</div>
    </div>
    <div id="calendarGrid" class="grid grid-cols-7 gap-1"></div>

    <p class="text-[11px] text-[#607072] dark:text-[#9BA8A9] text-center">
      <span class="inline-block w-2 h-2 rounded-full bg-[#1D4D4F] mr-1"></span>Ada kajian
      <span class="inline-block w-2 h-2 rounded-full bg-red-500 ml-3 mr-1"></span>Hari ini
    </p>

    <!-- Daftar kajian tanggal terpilih -->
    <div id="calendarDateList" class="hidden space-y-2">
      <h4 id="calendarDateLabel" class="text-xs font-extrabold text-[#1C2224] dark:text-[#E8EDED]"></h4>
      <div id="calendarDateItems" class="space-y-2 max-h-48 overflow-y-auto no-scrollbar"></div>
    </div>
  </div>
</div>

<!-- Modal: Filter Kajian -->
<div id="filterModal" class="hidden fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/60 backdrop-blur-sm p-0 sm:p-4">
  <div class="bg-white dark:bg-[#162526] w-full max-w-md rounded-t-3xl sm:rounded-2xl p-5 space-y-4">
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
  <div class="bg-white dark:bg-[#162526] w-full max-w-md rounded-t-3xl sm:rounded-2xl p-5 space-y-4 max-h-[85vh] overflow-y-auto no-scrollbar">
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
  <div class="bg-white dark:bg-[#162526] w-full max-w-lg rounded-t-3xl sm:rounded-3xl p-6 space-y-4 max-h-[90vh] overflow-y-auto no-scrollbar border border-[#E5ECEB] shadow-xl">
    <div class="flex items-center justify-between border-b border-[#E5ECEB] pb-3">
      <div class="flex items-center gap-2.5">
        <div class="w-8 h-8 rounded-full bg-[#E6F4F1] text-[#1D4D4F] flex items-center justify-center">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
        </div>
        <div>
          <h3 class="text-sm font-bold text-[#1C2224] dark:text-[#E8EDED]">Ajukan Info Kajian Baru</h3>
          <p class="text-[11px] text-[#607072] dark:text-[#9BA8A9]">Bantu sebarkan majelis ilmu sunnah di sekitar Anda.</p>
        </div>
      </div>
      <button onclick="closeSubmitModal()" class="w-8 h-8 rounded-full bg-[#F8FAF9] text-[#607072] dark:text-[#9BA8A9] hover:text-[#1C2224] dark:text-[#E8EDED] flex items-center justify-center transition" aria-label="Tutup Pengajuan">
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
        <label class="block font-bold text-[#1C2224] dark:text-[#E8EDED] mb-1">Judul / Tema Kajian *</label>
        <input type="text" name="title" required placeholder="Contoh: Kajian Kitab Riyadhus Shalihin" class="w-full bg-[#F8FAF9] border border-[#E5ECEB] rounded-2xl p-2.5 text-xs text-[#1C2224] dark:text-[#E8EDED] focus:outline-none focus:ring-2 focus:ring-[#1D4D4F]">
      </div>

      <div class="grid grid-cols-2 gap-2.5">
        <div>
          <label class="block font-bold text-[#1C2224] dark:text-[#E8EDED] mb-1">Nama Pemateri / Ustadz *</label>
          <input type="text" name="ustadz" required placeholder="Ustadz..." class="w-full bg-[#F8FAF9] border border-[#E5ECEB] rounded-2xl p-2.5 text-xs text-[#1C2224] dark:text-[#E8EDED] focus:outline-none focus:ring-2 focus:ring-[#1D4D4F]">
        </div>
        <div>
          <label class="block font-bold text-[#1C2224] dark:text-[#E8EDED] mb-1">Kategori *</label>
          <select name="category" required class="w-full bg-[#F8FAF9] border border-[#E5ECEB] rounded-2xl p-2.5 text-xs text-[#1C2224] dark:text-[#E8EDED] focus:outline-none focus:ring-2 focus:ring-[#1D4D4F]">
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
          <label class="block font-bold text-[#1C2224] dark:text-[#E8EDED] mb-1">Nama Masjid *</label>
          <input type="text" name="masjid" required placeholder="Masjid..." class="w-full bg-[#F8FAF9] border border-[#E5ECEB] rounded-2xl p-2.5 text-xs text-[#1C2224] dark:text-[#E8EDED] focus:outline-none focus:ring-2 focus:ring-[#1D4D4F]">
        </div>
        <div>
          <label class="block font-bold text-[#1C2224] dark:text-[#E8EDED] mb-1">Peserta Jamaah *</label>
          <select name="audience" required class="w-full bg-[#F8FAF9] border border-[#E5ECEB] rounded-2xl p-2.5 text-xs text-[#1C2224] dark:text-[#E8EDED] focus:outline-none focus:ring-2 focus:ring-[#1D4D4F]">
            <option value="Umum">Umum (Ikhwan & Akhwat)</option>
            <option value="Ikhwan">Khusus Ikhwan</option>
            <option value="Akhwat">Khusus Akhwat</option>
          </select>
        </div>
      </div>

      <div>
        <label class="block font-bold text-[#1C2224] dark:text-[#E8EDED] mb-1">Alamat Lengkap Masjid *</label>
        <textarea name="address" required rows="2" placeholder="Jl. Raya..." class="w-full bg-[#F8FAF9] border border-[#E5ECEB] rounded-2xl p-2.5 text-xs text-[#1C2224] dark:text-[#E8EDED] focus:outline-none focus:ring-2 focus:ring-[#1D4D4F]"></textarea>
      </div>

      <div class="grid grid-cols-2 gap-2.5">
        <div>
          <label class="block font-bold text-[#1C2224] dark:text-[#E8EDED] mb-1">Hari Rutin *</label>
          <select name="day" required class="w-full bg-[#F8FAF9] border border-[#E5ECEB] rounded-2xl p-2.5 text-xs text-[#1C2224] dark:text-[#E8EDED] focus:outline-none focus:ring-2 focus:ring-[#1D4D4F]">
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
          <label class="block font-bold text-[#1C2224] dark:text-[#E8EDED] mb-1">Waktu / Jam *</label>
          <input type="text" name="time" required placeholder="09:00 WIB / Ba'da Maghrib" class="w-full bg-[#F8FAF9] border border-[#E5ECEB] rounded-2xl p-2.5 text-xs text-[#1C2224] dark:text-[#E8EDED] focus:outline-none focus:ring-2 focus:ring-[#1D4D4F]">
        </div>
      </div>

      <div class="grid grid-cols-2 gap-2.5">
        <div>
          <label class="block font-bold text-[#1C2224] dark:text-[#E8EDED] mb-1">Latitude</label>
          <input type="number" step="any" name="lat" id="submitLat" value="-6.2615" required class="w-full bg-[#F8FAF9] border border-[#E5ECEB] rounded-2xl p-2.5 text-xs text-[#1C2224] dark:text-[#E8EDED] focus:outline-none focus:ring-2 focus:ring-[#1D4D4F]">
        </div>
        <div>
          <label class="block font-bold text-[#1C2224] dark:text-[#E8EDED] mb-1">Longitude</label>
          <input type="number" step="any" name="lon" id="submitLon" value="106.8106" required class="w-full bg-[#F8FAF9] border border-[#E5ECEB] rounded-2xl p-2.5 text-xs text-[#1C2224] dark:text-[#E8EDED] focus:outline-none focus:ring-2 focus:ring-[#1D4D4F]">
        </div>
      </div>

      <div>
        <label class="block font-bold text-[#1C2224] dark:text-[#E8EDED] mb-1">Deskripsi & Keterangan Tambahan</label>
        <textarea name="description" rows="2" placeholder="Catatan adab, parkir, streaming YouTube..." class="w-full bg-[#F8FAF9] border border-[#E5ECEB] rounded-2xl p-2.5 text-xs text-[#1C2224] dark:text-[#E8EDED] focus:outline-none focus:ring-2 focus:ring-[#1D4D4F]"></textarea>
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
  var rawKajians = {!! $allKajiansJson !!};
  var userBookmarkIds = new Set(@json($userBookmarks));
  // Server = sumber kebenaran untuk user login: localStorage MENIRU server.
  // Menggabungkan localStorage ke set server (pola lama) menghidupkan lagi
  // bookmark hantu dari ketukan lama yang gagal tersimpan. Untuk tamu
  // (belum login), bookmark memang hanya lokal sehingga tetap digabungkan.
  try {
    @auth
    localStorage.setItem('sinikajian_bookmarks', JSON.stringify(Array.from(userBookmarkIds)));
    @else
    const localSaved = JSON.parse(localStorage.getItem('sinikajian_bookmarks') || '[]');
    if (Array.isArray(localSaved)) {
      localSaved.forEach(id => userBookmarkIds.add(Number(id)));
    }
    @endauth
  } catch(e) {}
  var CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  // Titik koordinat Ka'bah (Mekkah)
  var KAABA_COORDS = { lat: 21.4225, lon: 39.8262 };

  // Titik lokasi default (disinkronkan dari SiniKajianGeo atau cache)
  var userCoords = (() => {
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
  var activeSearch = '';
  var activeCategory = 'Semua';
  var activeRadius = 25; // km
  var activeDay = 'Semua';
  var activeAudience = 'Semua';
  var activeFavUstadz = false; // filter hanya ustadz favorit
  var todayOnly = false; // filter hanya kajian hari ini (chip Hari Ini)
  var sortMode = 'tanggal'; // 'tanggal' (tanggal → jarak) atau 'jarak' (jarak → tanggal) — Segmen No.3, 2026-10-28
  var viewMode = 'list'; // 'list' atau 'agenda'
  var currentDetailEvent = null;

  // Ustadz Favorit (localStorage)
  function getFavUstadz() {
    try { return JSON.parse(localStorage.getItem('majelisku_fav_ustadz') || '[]'); }
    catch (e) { return []; }
  }
  function isFavUstadz(name) {
    if (!name) return false;
    return getFavUstadz().includes(name.trim());
  }
  function toggleFavUstadz(name) {
    if (!name) return;
    name = name.trim();
    let list = getFavUstadz();
    const nowFav = !list.includes(name);
    if (nowFav) {
      list.push(name);
    } else {
      list = list.filter(n => n !== name);
    }
    try { localStorage.setItem('majelisku_fav_ustadz', JSON.stringify(list)); } catch (e) {}
    renderKajianFeed();
    // Sinkronkan ke server (follow ustadz + notif kajian baru) bila login.
    // Tamu tetap jalan lokal-only — tidak diblokir.
    try {
      var csrf = document.querySelector('meta[name="csrf-token"]');
      fetch('/ustadz/follow', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf ? csrf.getAttribute('content') : '' },
        body: JSON.stringify({ ustadz: name, follow: nowFav })
      }).catch(function () {});
    } catch (e) {}
  }

  function toggleFavUstadzFilter() {
    activeFavUstadz = !activeFavUstadz;
    const btn = document.getElementById('favUstadzToggle');
    if (btn) {
      if (activeFavUstadz) {
        btn.className = 'px-2.5 py-1 rounded-xl border border-amber-500 bg-amber-50 text-amber-700 font-bold flex items-center gap-1 transition';
        btn.querySelector('svg').setAttribute('fill', 'currentColor');
      } else {
        btn.className = 'px-2.5 py-1 rounded-xl border border-[#E5ECEB] dark:border-[#1E2E2F] bg-white dark:bg-[#162526] font-medium hover:border-amber-500 flex items-center gap-1 transition';
        btn.querySelector('svg').setAttribute('fill', 'none');
      }
    }
    renderKajianFeed();
  }

  function toggleViewMode() {
    viewMode = viewMode === 'list' ? 'agenda' : 'list';
    const label = document.getElementById('viewModeLabel');
    if (label) label.innerText = viewMode === 'list' ? 'Kalender' : 'List';
    renderKajianFeed();
  }

  // ===== KALENDER MODAL =====
  var calendarCursor = new Date();
  calendarCursor.setDate(1);

  // Ubah tombol "Kalender" jadi buka modal kalender (bukan toggle agenda)
  function openCalendarModal() {
    document.getElementById('calendarModal').classList.remove('hidden');
    renderCalendar();
  }
  function closeCalendarModal() {
    document.getElementById('calendarModal').classList.add('hidden');
  }
  function changeCalendarMonth(delta) {
    calendarCursor.setMonth(calendarCursor.getMonth() + delta);
    renderCalendar();
  }

  function getKajianDatesMap() {
    const map = {};
    (typeof rawKajians !== 'undefined' ? rawKajians : []).forEach(ev => {
      if (ev.event_date && !ev.is_cancelled) {
        const key = String(ev.event_date).split('T')[0];
        if (!map[key]) map[key] = [];
        map[key].push(ev);
      }
    });
    return map;
  }

  function renderCalendar() {
    const year = calendarCursor.getFullYear();
    const month = calendarCursor.getMonth();
    const months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    document.getElementById('calendarMonthLabel').innerText = `${months[month]} ${year}`;

    const grid = document.getElementById('calendarGrid');
    grid.innerHTML = '';

    const firstDay = new Date(year, month, 1).getDay(); // 0=Min
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const _now = new Date(); const todayStr = _now.getFullYear() + '-' + String(_now.getMonth() + 1).padStart(2, '0') + '-' + String(_now.getDate()).padStart(2, '0');
    const kajianMap = getKajianDatesMap();

    for (let i = 0; i < firstDay; i++) {
      grid.appendChild(document.createElement('div'));
    }

    for (let d = 1; d <= daysInMonth; d++) {
      const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
      const hasKajian = !!(kajianMap[dateStr] && kajianMap[dateStr].length);
      const isToday = dateStr === todayStr;

      const cell = document.createElement('button');
      cell.className = 'relative aspect-square rounded-xl text-xs font-semibold flex flex-col items-center justify-center transition touch-btn ' +
        (isToday
          ? 'bg-red-500 text-white'
          : hasKajian
            ? 'bg-[#E6F4F1] dark:bg-[#1E2E2F] text-[#1D4D4F] dark:text-[#7FC4BC] hover:bg-[#D1EAE5]'
            : 'text-[#607072] dark:text-[#9BA8A9] hover:bg-slate-100 dark:hover:bg-[#1E2E2F]');
      cell.innerHTML = `<span>${d}</span>` +
        (hasKajian && !isToday ? `<span class="w-1.5 h-1.5 rounded-full bg-[#1D4D4F] dark:bg-[#7FC4BC] mt-0.5"></span>` : '') +
        (hasKajian && isToday ? `<span class="w-1.5 h-1.5 rounded-full bg-white mt-0.5"></span>` : '');
      if (hasKajian) {
        cell.onclick = () => showCalendarDateKajian(dateStr, kajianMap[dateStr]);
      } else {
        cell.disabled = true;
        cell.classList.add('opacity-60');
      }
      grid.appendChild(cell);
    }

    // Sembunyikan daftar tanggal saat ganti bulan
    document.getElementById('calendarDateList').classList.add('hidden');
  }

  function showCalendarDateKajian(dateStr, items) {
    const listWrap = document.getElementById('calendarDateList');
    const label = document.getElementById('calendarDateLabel');
    const itemsWrap = document.getElementById('calendarDateItems');
    label.innerText = `${formatTanggalIndo(dateStr)} — ${items.length} kajian`;
    itemsWrap.innerHTML = '';
    items.forEach(ev => {
      const row = document.createElement('div');
      row.setAttribute('data-kajian-id', ev.id);
      row.className = 'bg-[#F8FAF9] dark:bg-[#1E2E2F] rounded-xl p-3 flex items-center gap-2 cursor-pointer hover:border hover:border-[#1D4D4F]/40 transition';
      row.addEventListener('click', (e) => {
        if (e.target.closest('button')) return;
        try { closeCalendarModal(); openDetailModalWithEvent(ev); } catch (err) { console.error('Gagal buka detail:', err); }
      });
      const timeShort = (ev.time || '').split('-')[0] || '';
      row.innerHTML = `
        <div class="flex-1 min-w-0">
          <div class="text-[13px] font-bold text-[#1C2224] dark:text-[#E8EDED] truncate">${escapeHtml(ev.title)}</div>
          <div class="text-[11px] text-[#607072] dark:text-[#9BA8A9] truncate">${escapeHtml(timeShort)} • ${escapeHtml(ev.ustadz)} • ${escapeHtml(ev.masjid)}</div>
        </div>
        <button onclick="event.stopPropagation(); downloadICS(${ev.id})" class="p-2 rounded-lg bg-[#E6F4F1] dark:bg-[#0F1B1C] text-[#1D4D4F] dark:text-[#7FC4BC] touch-btn" title="Tambah ke kalender" aria-label="Tambah ke kalender">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
        </button>
      `;
      itemsWrap.appendChild(row);
    });
    listWrap.classList.remove('hidden');
  }

  // Tambah ke kalender user langsung (tanpa download):
  // - iPhone: navigasi ke blob text/calendar → iOS langsung buka preview
  //   "Add to Calendar" Apple Calendar bawaan
  // - Android/Desktop: buka Google Calendar dengan event terisi
  function downloadICS(kajianId) {
    const ev = (typeof rawKajians !== 'undefined' ? rawKajians : []).find(k => k.id === kajianId);
    if (!ev || !ev.event_date) return;
    const dateStr = String(ev.event_date).split('T')[0].replace(/-/g, '');
    let timeStart = '090000', timeEnd = '110000';
    const m = (ev.time || '').match(/(\d{1,2})[:.](\d{2})/);
    if (m) {
      timeStart = m[1].padStart(2, '0') + m[2] + '00';
      const endH = (parseInt(m[1], 10) + 2) % 24;
      timeEnd = String(endH).padStart(2, '0') + m[2] + '00';
    }
    const title = ev.title || 'Kajian';
    const desc = `${ev.ustadz || ''} - majelisku.web.id`;
    const loc = ((ev.masjid || '') + ' ' + (ev.address || '')).substring(0, 120);
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);

    if (isIOS) {
      // Apple Calendar bawaan: iOS kenali text/calendar → tampilkan "Add to Calendar"
      const esc = (s) => String(s || '').replace(/\\/g, '\\\\').replace(/;/g, '\\;').replace(/,/g, '\\,').replace(/\n/g, '\\n');
      const fold = (line) => {
        let out = '';
        while (line.length > 73) { out += line.slice(0, 73) + '\r\n '; line = line.slice(73); }
        return out + line;
      };
      const dtstamp = new Date().toISOString().replace(/[-:]/g, '').split('.')[0] + 'Z';
      const ics = [
        'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Majelisku//Kajian//ID', 'METHOD:PUBLISH',
        'BEGIN:VEVENT',
        `UID:kajian-${ev.id}-${Date.now()}@majelisku.web.id`,
        `DTSTAMP:${dtstamp}`,
        `DTSTART;TZID=Asia/Jakarta:${dateStr}T${timeStart}`,
        `DTEND;TZID=Asia/Jakarta:${dateStr}T${timeEnd}`,
        fold(`SUMMARY:${esc(title)}`),
        fold(`DESCRIPTION:${esc(desc)}`),
        fold(`LOCATION:${esc(loc)}`),
        'END:VEVENT', 'END:VCALENDAR'
      ].join('\r\n');
      const blob = new Blob([ics], { type: 'text/calendar;charset=utf-8' });
      // Navigasi langsung (bukan download) → iOS buka Apple Calendar
      window.location.href = URL.createObjectURL(blob);
    } else {
      // Android: intent langsung buka aplikasi Google Calendar
      // Desktop: buka Google Calendar web
      const dates = `${dateStr}T${timeStart}/${dateStr}T${timeEnd}`;
      const params = `action=TEMPLATE` +
        `&text=${encodeURIComponent(title)}` +
        `&dates=${dates}` +
        `&ctz=Asia/Jakarta` +
        `&details=${encodeURIComponent(desc)}` +
        `&location=${encodeURIComponent(loc)}`;
      const isAndroid = /Android/.test(navigator.userAgent);
      if (isAndroid) {
        // Android: buka Google Calendar langsung via HTTPS (lebih andal daripada intent://
        // yang sering jatuh jadi download di Chrome). Chrome akan tawarkan buka di aplikasi.
        window.location.href = 'https://calendar.google.com/calendar/render?' + params;
      } else {
        window.open('https://calendar.google.com/calendar/render?' + params, '_blank');
      }
    }
  }

  // Render agenda: grup berdasarkan tanggal
  function renderAgendaView(container, items) {
    const groups = {};
    items.forEach(ev => {
      const key = ev.event_date || 'rutin';
      if (!groups[key]) groups[key] = [];
      groups[key].push(ev);
    });

    const sortedKeys = Object.keys(groups).sort((a, b) => {
      if (a === 'rutin') return 1;
      if (b === 'rutin') return -1;
      return a.localeCompare(b);
    });

    const today = todayStr;

    sortedKeys.forEach(key => {
      const list = groups[key];
      const header = document.createElement('div');
      header.className = 'sticky top-0 z-10 py-2';

      let label = 'Kajian Rutin';
      let badge = '';
      if (key !== 'rutin') {
        const d = new Date(key + 'T00:00:00');
        const days = ['Ahad', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        label = `${days[d.getDay()]}, ${formatTanggalIndo(key)}`;
        if (key === today) badge = '<span class="ml-2 px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-700">HARI INI</span>';
        else {
          const diff = Math.round((d - new Date(today + 'T00:00:00')) / 86400000);
          if (diff === 1) badge = '<span class="ml-2 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700">BESOK</span>';
          else if (diff > 1 && diff <= 7) badge = `<span class="ml-2 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700">${diff} HARI LAGI</span>`;
        }
      }

      header.innerHTML = `
        <div class="flex items-center gap-2 bg-[#E6F4F1] dark:bg-[#1E2E2F] rounded-xl px-3 py-2">
          <span class="text-xs font-extrabold text-[#1D4D4F] dark:text-[#7FC4BC] uppercase tracking-wide">${label}</span>
          ${badge}
          <span class="ml-auto text-[11px] font-medium text-[#607072] dark:text-[#9BA8A9]">${list.length} kajian</span>
        </div>
      `;
      container.appendChild(header);

      list.forEach(ev => {
        const row = document.createElement('div');
        row.setAttribute('data-kajian-id', ev.id);
        row.className = 'kajian-row bg-white dark:bg-[#162526] rounded-xl p-3 mb-2 border border-[#E5ECEB] dark:border-[#1E2E2F] flex items-center gap-3 cursor-pointer hover:border-[#1D4D4F]/50 transition' + (ev.is_cancelled ? ' opacity-60' : '');
        const timeShort = (ev.time || '').split('-')[0].split(' ')[0] || '';
        row.innerHTML = `
          <div class="text-center shrink-0 w-14">
            <div class="text-sm font-extrabold text-[#1D4D4F] dark:text-[#7FC4BC]">${escapeHtml(timeShort)}</div>
            <div class="text-[10px] text-[#607072] dark:text-[#9BA8A9]">WIB</div>
          </div>
          <div class="w-px self-stretch bg-[#E5ECEB] dark:bg-[#1E2E2F]"></div>
          <div class="flex-1 min-w-0">
            <div class="text-[13px] font-bold text-[#1C2224] dark:text-[#E8EDED] truncate">${escapeHtml(ev.title)}${ev.is_cancelled ? ' <span class="text-red-600 text-[10px] font-extrabold">BATAL</span>' : ''}</div>
            <div class="text-[11px] text-[#607072] dark:text-[#9BA8A9] truncate">${escapeHtml(ev.ustadz)} • ${escapeHtml(ev.masjid)}</div>
          </div>
          <svg class="w-4 h-4 text-[#607072] dark:text-[#9BA8A9] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
          </svg>
        `;
        container.appendChild(row);
      });
    });
  }

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
    // Kontras aksesibilitas (Lighthouse 2026-10-25): teks putih di kuning
    // #EAB308 (1,6:1) dan hijau #16A34A (3,0:1) gagal 4,5:1 untuk teks kecil.
    // Besok = teks gelap di kuning; 2+ hari = hijau lebih gelap + teks putih.
    let bg = diff === 0 ? '#FEE2E2' : diff === 1 ? '#FEF3C7' : '#DCFCE7';
    let fg = diff === 0 ? '#DC2626' : diff === 1 ? '#92400E' : '#166534';
    return `<span style="background:${bg};color:${fg};font-size:9px;font-weight:700;padding:2px 8px;border-radius:9999px;white-space:nowrap;flex-shrink:0;">${label}</span>`;
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

  // Transform Supabase langsung (fallback bila proxy /api/thumbs gagal).
  function supabaseThumbDirect(url, width) {
    if (!url) return url;
    try {
      const u = new URL(url);
      if (u.hostname.includes('supabase.co') && u.pathname.includes('/storage/v1/object/public/')) {
        u.pathname = u.pathname.replace('/storage/v1/object/public/', '/storage/v1/render/image/public/');
        u.searchParams.set('width', width || 200);
        u.searchParams.set('quality', 60);
        u.searchParams.set('format', 'webp');
        u.searchParams.set('resize', 'cover');
        return u.toString();
      }
    } catch (e) {}
    return url;
  }

  // Thumbnail lewat proxy edge Vercel (LCP fix 2026-10-27): Supabase
  // menyajikan gambar no-cache/DYNAMIC, jadi thumb flyer diarahkan ke
  // /api/thumbs/{id}-{w}.webp yang di-cache edge. URL non-flyer tetap
  // transform langsung seperti sebelumnya.
  function supabaseThumb(url, width) {
    if (!url) return url;
    try {
      const u = new URL(url);
      if (u.hostname.includes('supabase.co') && u.pathname.includes('/storage/v1/object/public/flyers/')) {
        const base = u.pathname.split('/').pop() || '';
        const fid = base.replace(/\.[^.]+$/, '');
        if (/^\d+$/.test(fid)) return '/api/thumbs/' + fid + '-' + (width || 200) + '.webp';
      }
      if (u.hostname.includes('supabase.co') && u.pathname.includes('/storage/v1/object/public/')) {
        return supabaseThumbDirect(url, width);
      }
    } catch (e) {}
    return url;
  }

  // Fallback gambar: sekali coba URL transform langsung, bila itu pun
  // gagal sembunyikan gambar (kartu: sembunyikan wadah thumb-nya).
  // Dipakai onerror img server-render + JS-render; poster asli dibaca
  // dari data-poster agar aman dari kutip di URL.
  function thumbFallback(img, width, hideParent) {
    if (!img.dataset.fb) {
      img.dataset.fb = '1';
      const poster = img.dataset.poster || '';
      const direct = poster ? supabaseThumbDirect(poster, width) : '';
      if (direct && direct !== img.src) { img.src = direct; return; }
    }
    if (hideParent && img.parentElement) img.parentElement.style.display = 'none';
    else img.style.display = 'none';
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

  // Render & Update Waktu Sholat — pill header (desain mockup 2026-10-29):
  // hanya nama + jam sholat berikutnya + hitung mundur; daftar lengkap
  // 6 waktu tidak lagi tampil di dashboard (ada di halaman /sholat).
  function updatePrayerDisplay() {
    if (!document.getElementById('nextPrayerName')) return;
    const now = new Date();
    const times = calculatePrayerTimes(now, userCoords.lat, userCoords.lon);

    const currentMins = now.getHours() * 60 + now.getMinutes();
    function toMins(tStr) {
      const parts = tStr.split(':');
      return parseInt(parts[0]) * 60 + parseInt(parts[1]);
    }

    const prayerList = [
      { name: 'Subuh', time: times.subuh, mins: toMins(times.subuh) },
      { name: 'Dzuhur', time: times.dzuhur, mins: toMins(times.dzuhur) },
      { name: 'Ashar', time: times.ashar, mins: toMins(times.ashar) },
      { name: 'Maghrib', time: times.maghrib, mins: toMins(times.maghrib) },
      { name: 'Isya', time: times.isya, mins: toMins(times.isya) },
    ];

    let nextPrayer = null;
    for (let p of prayerList) {
      if (p.mins > currentMins) { nextPrayer = p; break; }
    }
    if (!nextPrayer) nextPrayer = prayerList[0]; // Besok Subuh

    const diffMins = nextPrayer.mins > currentMins ? (nextPrayer.mins - currentMins) : (1440 - currentMins + nextPrayer.mins);
    const diffH = Math.floor(diffMins / 60);
    const diffM = diffMins % 60;
    document.getElementById('nextPrayerName').innerText = nextPrayer.name;
    document.getElementById('nextPrayerTime').innerText = nextPrayer.time;
    document.getElementById('nextPrayerCountdown').innerText = `-${diffH > 0 ? diffH + 'j ' : ''}${diffM}m`;
  }

  var SOURCE_META = {
    instagram: { label: 'Instagram', style: 'background:linear-gradient(45deg,#f09433,#e6683c,#dc2743,#cc2366,#bc1888)', glyph: 'IG' },
    facebook: { label: 'Facebook', style: 'background:#1877F2', glyph: 'f' },
    threads: { label: 'Threads', style: 'background:#111111', glyph: '@' },
  };

  // 3. Render Daftar Kajian
  // Event delegation: satu listener untuk semua kartu (lebih robust)
  (function initCardClickDelegation() {
    const container = document.getElementById('kajianCardsList');
    if (!container || container.dataset.delegated) return;
    container.dataset.delegated = '1';
    container.addEventListener('click', (e) => {
      // Abaikan jika klik tombol di dalam kartu (bookmark, dll)
      if (e.target.closest('button') || e.target.closest('a')) return;
      const card = e.target.closest('article[data-kajian-id], div[data-kajian-id]');
      if (card) {
        const id = card.getAttribute('data-kajian-id');
        const ev = (typeof rawKajians !== 'undefined' ? rawKajians : []).find(k => String(k.id) === String(id));
        if (ev) {
          try { openDetailModalWithEvent(ev); } catch (err) { console.error('Gagal buka detail:', err); }
        }
      }
    });
  })();

  function renderKajianFeed() {
    const container = document.getElementById('kajianCardsList');
    if (!container) return;
    const emptyState = document.getElementById('emptyStateContainer');
    const heroSpotlight = document.getElementById('heroSpotlightContainer');
    const countDisplay = document.getElementById('filteredCountText');

    let processed = rawKajians.map(item => {
      const latN = parseFloat(item.lat);
      const lonN = parseFloat(item.lon);
      let dist = (isNaN(latN) || isNaN(lonN)) ? null : calculateHaversine(userCoords.lat, userCoords.lon, latN, lonN);
      if (dist !== null && !Number.isFinite(dist)) dist = null;
      return { ...item, distance: dist };
    });

    // Urutkan: 'tanggal' = tanggal terdekat lalu jarak; 'jarak' = jarak
    // terdekat lalu tanggal (Segmen No.3 — Terdekat dari saya, 2026-10-28).
    processed.sort((a, b) => {
      const dateA = a.event_date || '9999-12-31';
      const dateB = b.event_date || '9999-12-31';
      const distA = (a.distance === null || a.distance === undefined) ? 999999 : a.distance;
      const distB = (b.distance === null || b.distance === undefined) ? 999999 : b.distance;
      const timeCmp = (a.time || '').localeCompare(b.time || '');
      if (sortMode === 'jarak') {
        if (distA !== distB) return distA - distB;
        if (dateA !== dateB) return dateA.localeCompare(dateB);
        return timeCmp;
      }
      if (dateA !== dateB) return dateA.localeCompare(dateB);
      // Jika tanggal sama, urutkan berdasarkan jarak terdekat
      if (distA !== distB) return distA - distB;
      // Jika jarak sama, urutkan berdasarkan waktu
      return timeCmp;
    });

    // Filter
    let filtered = processed.filter(item => {
      if (activeCategory !== 'Semua' && item.category !== activeCategory) return false;
      if (activeDay !== 'Semua' && item.day !== activeDay) return false;
      if (activeAudience !== 'Semua' && item.audience !== activeAudience) return false;
      if (activeFavUstadz && !isFavUstadz(item.ustadz)) return false;
      if (todayOnly) {
        const t = new Date();
        const todayStr = t.getFullYear() + '-' + String(t.getMonth() + 1).padStart(2, '0') + '-' + String(t.getDate()).padStart(2, '0');
        if (String(item.event_date || '').split('T')[0] !== todayStr) return false;
      }
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

    countDisplay.innerText = '(' + filtered.length + ')';

    // Spotlight carousel: track horizontal berdiri sendiri, swipe menggeser track
    if (filtered.length > 0 && !activeSearch) {
      const spotlightItems = filtered.slice(0, 3);
      let spotlightIdx = 0;
      const track = document.getElementById('spotlightTrack');
      const viewport = document.getElementById('spotlightViewport');
      heroSpotlight.classList.remove('hidden');

      const slideHTML = (item, i) => {
        const saved = userBookmarkIds.has(Number(item.id));
        const cancelStamp = item.is_cancelled ? `
          <div class="absolute inset-0 flex items-center justify-center pointer-events-none" style="z-index:20;">
            <span style="border:4px solid #dc2626;color:#dc2626;font-weight:900;font-size:30px;letter-spacing:4px;padding:6px 22px;border-radius:8px;transform:rotate(-12deg);background:rgba(255,255,255,0.78);text-transform:uppercase;">Dibatalkan</span>
          </div>` : '';
        return `
        <div class="spot-slide relative shrink-0 cursor-pointer overflow-hidden bg-[#153A3B]" style="width:100%;" data-slide-idx="${i}" data-kajian-id="${item.id}">
          <img src="${item.poster_url ? supabaseThumb(item.poster_url, 800) : 'https://images.unsplash.com/photo-1542816417-0983c9c9ad53?auto=format&fit=crop&w=800&q=80'}" data-poster="${item.poster_url || ''}" alt="Kajian Pilihan" class="w-full h-60 object-cover opacity-90" draggable="false" fetchpriority="high" decoding="async" onerror="thumbFallback(this, 800)">
          <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/45 to-black/20"></div>
          <div class="absolute top-3 inset-x-3 flex items-center justify-between gap-2">
            <span class="px-3 py-1.5 rounded-full text-[10px] font-extrabold tracking-wider uppercase bg-black/70 text-white shadow-xs">⭐ KAJIAN PILIHAN</span>
            <span data-dist class="px-3 py-1.5 rounded-full text-[10px] font-bold bg-black/60 text-white shadow-xs whitespace-nowrap">${item.distance != null ? item.distance.toFixed(1) + ' km' : '— km'}</span>
          </div>
          ${cancelStamp}
          <div class="absolute bottom-0 inset-x-0 p-4">
            <h2 class="text-lg font-extrabold text-white leading-snug line-clamp-2">${(item.title || '').replace(/</g, '&lt;')}</h2>
            <p class="text-sm font-bold text-amber-300 mt-1 truncate">${(item.ustadz || '').replace(/</g, '&lt;')}</p>
            <p class="text-xs text-white/90 mt-1 truncate">${item.day || ''}${item.event_date ? ', ' + formatTanggalIndo(item.event_date).replace(/\s\d{4}$/, '') : ''} • ${item.time || ''} • ${(item.masjid || '').replace(/</g, '&lt;')}</p>
            <span class="inline-flex items-center gap-1 mt-3 px-4 py-2 rounded-full bg-white text-[#1D4D4F] text-xs font-extrabold shadow">Lihat Detail Kajian →</span>
          </div>
        </div>`;
      };

      // Slide server-render (LCP 2026-10-27): pertahankan bila id-nya
      // sudah sama persis dengan hasil hitung JS. Kalau beda (mis. lokasi
      // tersimpan user bukan titik default / filter aktif), bangun ulang.
      // Jarak di slide server ikut diperbarui dari hitungan JS.
      let keptServerSpotlight = false;
      if (track.dataset.serverRendered === '1') {
        const serverSlides = [...track.querySelectorAll('.spot-slide')];
        const serverIds = serverSlides.map(el => Number(el.dataset.kajianId));
        const wantIds = spotlightItems.map(ev => Number(ev.id));
        if (serverIds.length === wantIds.length && serverIds.every((id, i) => id === wantIds[i])) {
          keptServerSpotlight = true;
          spotlightItems.forEach((ev, i) => {
            const span = serverSlides[i].querySelector('[data-dist]');
            if (span) span.textContent = ev.distance != null ? ev.distance.toFixed(1) + ' km' : '— km';
          });
        }
        delete track.dataset.serverRendered;
      }
      if (!keptServerSpotlight) {
        track.innerHTML = spotlightItems.map(slideHTML).join('');
      }

      // Klik slide -> detail (kecuali tombol bookmark)
      track.querySelectorAll('.spot-slide').forEach(slide => {
        slide.addEventListener('click', () => {
          if (window.__spotlightJustSwiped) { window.__spotlightJustSwiped = false; return; }
          const idx = Number(slide.dataset.slideIdx);
          if (spotlightItems[idx]) openDetailModalWithEvent(spotlightItems[idx]);
        });
      });
      const applyTrack = (animate) => {
        track.style.transition = animate ? 'transform .32s cubic-bezier(.22,1,.36,1)' : 'none';
        track.style.transform = `translateX(${-spotlightIdx * 100}%)`;
      };

      window.__spotlightGo = (i) => {
        spotlightIdx = (i + spotlightItems.length) % spotlightItems.length;
        applyTrack(true);
        renderSpotlightDots();
      };

      const renderSpotlightDots = () => {
        track.querySelectorAll('.spot-slide').forEach((slide, si) => {
          const spans = slide.querySelectorAll('span[style*="border-radius:99px"]');
          spans.forEach((sp, j) => {
            sp.style.width = j === spotlightIdx ? '20px' : '8px';
            sp.style.background = j === spotlightIdx ? '#FCD34D' : 'rgba(255,255,255,0.4)';
          });
        });
      };

      // Drag/swipe: track mengikuti jari secara real-time.
      // Handler disimpan di window agar render ulang feed tidak memakai closure lama.
      window.__spotDrag = { startX: 0, startY: 0, dx: 0, tracking: false, horiz: false };
      window.__spotContext = () => ({
        idx: spotlightIdx,
        items: spotlightItems,
        apply: applyTrack,
        go: window.__spotlightGo,
      });
      if (viewport && !viewport.dataset.swipeBound) {
        viewport.dataset.swipeBound = '1';
        const D = window.__spotDrag;

        viewport.addEventListener('touchstart', (e) => {
          D.startX = e.touches[0].clientX;
          D.startY = e.touches[0].clientY;
          D.dx = 0; D.tracking = true; D.horiz = false;
          window.__spotlightJustSwiped = false;
          const t = document.getElementById('spotlightTrack');
          if (t) t.style.transition = 'none';
        }, { passive: true });

        viewport.addEventListener('touchmove', (e) => {
          if (!D.tracking) return;
          const dx = e.touches[0].clientX - D.startX;
          const dy = e.touches[0].clientY - D.startY;
          if (!D.horiz) {
            if (Math.abs(dx) < 6 && Math.abs(dy) < 6) return;
            if (Math.abs(dy) > Math.abs(dx)) { D.tracking = false; return; }
            D.horiz = true;
          }
          D.dx = dx;
          const t = document.getElementById('spotlightTrack');
          if (!t) return;
          const ctx = window.__spotContext();
          const w = viewport.clientWidth || 1;
          const base = -ctx.idx * w;
          let pos = base + dx;
          const minPos = -(ctx.items.length - 1) * w;
          if (pos > 0) pos = pos * 0.35;
          if (pos < minPos) pos = minPos + (pos - minPos) * 0.35;
          t.style.transform = `translateX(${pos}px)`;
        }, { passive: true });

        const finishDrag = () => {
          if (!D.tracking && !D.horiz) return;
          const wasHoriz = D.horiz;
          D.tracking = false; D.horiz = false;
          const ctx = window.__spotContext();
          if (wasHoriz && Math.abs(D.dx) > 45) {
            window.__spotlightJustSwiped = true;
            ctx.go(ctx.idx + (D.dx < 0 ? 1 : -1));
            if (window.__spotlightTimer) {
              clearInterval(window.__spotlightTimer);
              window.__spotlightTimer = setInterval(() => {
                if (!document.hidden) {
                  const c = window.__spotContext();
                  c.go(c.idx + 1);
                }
              }, 6000);
            }
          } else {
            ctx.apply(true);
          }
          D.dx = 0;
        };
        viewport.addEventListener('touchend', finishDrag, { passive: true });
        viewport.addEventListener('touchcancel', finishDrag, { passive: true });
      }

      // Auto-advance tiap 6 detik
      if (window.__spotlightTimer) clearInterval(window.__spotlightTimer);
      if (spotlightItems.length > 1) {
        window.__spotlightTimer = setInterval(() => {
          if (!document.hidden) {
            const c = window.__spotContext();
            c.go(c.idx + 1);
          }
        }, 6000);
      }

      applyTrack(false);
    } else {
      heroSpotlight.classList.add('hidden');
      if (window.__spotlightTimer) clearInterval(window.__spotlightTimer);
    }

    if (filtered.length === 0) {
      container.innerHTML = '';
      emptyState.classList.remove('hidden');
      return;
    }

    emptyState.classList.add('hidden');

    // Mode agenda: grup per tanggal
    if (viewMode === 'agenda') {
      container.innerHTML = '';
      renderAgendaView(container, filtered);
      return;
    }

    // Pagination: tampilkan 12 dulu, load more jika ada
    const displayLimit = window.kajianDisplayLimit || 12;
    const toShow = filtered.slice(0, displayLimit);

    // Kartu server-render (B+C 2026-10-26): pertahankan bila urutannya
    // sudah sama persis dengan hasil hitung JS (id sama, urutan sama).
    // Kalau beda (mis. lokasi tersimpan user bukan titik default), bangun
    // ulang seperti biasa. Jarak di kartu server ikut diperbarui dari
    // hitungan JS agar tidak tertinggal jarak titik default.
    let keptServerCards = false;
    if (container.dataset.serverRendered === '1') {
      const serverCards = [...container.querySelectorAll('[data-kajian-id]')];
      const serverIds = serverCards.map(el => Number(el.dataset.kajianId));
      const wantIds = toShow.map(ev => Number(ev.id));
      if (serverIds.length === wantIds.length && serverIds.every((id, i) => id === wantIds[i])) {
        keptServerCards = true;
        toShow.forEach((ev, i) => {
          const span = serverCards[i].querySelector('[data-dist]');
          if (span) span.textContent = ev.distance != null ? ev.distance.toFixed(1) + ' km' : '— km';
        });
      }
      delete container.dataset.serverRendered;
    }

    if (!keptServerCards) {
    container.innerHTML = '';
    toShow.forEach((ev, i) => {
      const isSaved = userBookmarkIds.has(ev.id);
      const srcMeta = SOURCE_META[ev.source] || SOURCE_META.facebook;
      const hasSource = ev.source_url && ev.source_url !== '#';
      // Supabase Image Transform beneran (bukan query string palsu)
      const thumbUrl = ev.poster_url ? supabaseThumb(ev.poster_url, 200) : null;
      const flyerThumb = thumbUrl
        ? `<div class="w-[88px] h-[88px] rounded-2xl overflow-hidden shrink-0 bg-slate-100 border border-[#E5ECEB] shadow-xs">
             <img src="${thumbUrl}" data-poster="${ev.poster_url || ''}" alt="Flyer" class="w-full h-full object-cover"${i < 2 ? '' : ' loading="lazy"'} decoding="async" onerror="thumbFallback(this, 200, true)">
           </div>`
        : `<div class="w-[88px] h-[88px] rounded-2xl overflow-hidden shrink-0 bg-[#E6F4F1] text-[#1D4D4F] flex items-center justify-center font-bold text-xs border border-[#E5ECEB]">
             <span>${(ev.category || 'ILMU').substring(0, 4)}</span>
           </div>`;
      
      const card = document.createElement('article');
      card.setAttribute('data-kajian-id', ev.id);
      card.className = 'bg-white dark:bg-[#162526] rounded-3xl p-3 border border-[#E5ECEB] dark:border-[#1E2E2F] shadow-xs hover:border-[#1D4D4F]/50 transition-all cursor-pointer' + (ev.is_cancelled ? ' !border-red-300' : '');
      card.style.position = 'relative';
      card.style.overflow = 'hidden';
      const cancelledStamp = ev.is_cancelled ? `
        <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;pointer-events:none;z-index:10;">
          <span style="border:4px solid #dc2626;color:#dc2626;font-weight:900;font-size:28px;letter-spacing:4px;padding:6px 20px;border-radius:8px;transform:rotate(-12deg);background:rgba(255,255,255,0.75);text-transform:uppercase;">Dibatalkan</span>
        </div>` : '';
      const cancelledDim = ev.is_cancelled ? ' style="opacity:0.45;filter:grayscale(0.6);"' : '';
      card.innerHTML = `
        ${cancelledStamp}
        <div class="flex items-start gap-3"${cancelledDim}>
          ${flyerThumb}
          <div class="min-w-0 flex-1">
            <div class="flex items-center justify-between gap-2">
              ${hariLagiBadge(ev.event_date)}
              <span data-dist class="text-[11px] font-bold text-[#607072] dark:text-[#9BA8A9] whitespace-nowrap ml-auto">${ev.distance != null ? ev.distance.toFixed(1) + ' km' : '— km'}</span>
            </div>
            <p class="text-[11px] text-[#607072] dark:text-[#9BA8A9] mt-1 truncate">
              ${ev.day}${ev.event_date ? ', ' + formatTanggalIndo(ev.event_date).replace(/\s\d{4}$/, '') : ''} • ${ev.time}
            </p>
            <h3 class="text-sm font-bold text-[#1C2224] dark:text-[#E8EDED] leading-snug line-clamp-2 hover:text-[#1D4D4F] mt-0.5">
              ${escapeHtml(ev.title)}
            </h3>
            <p class="text-[11px] text-[#607072] dark:text-[#9BA8A9] mt-0.5 truncate">
              ${escapeHtml(ev.ustadz)} • ${escapeHtml(ev.masjid)}
            </p>
            <div class="flex items-center gap-1.5 mt-2 flex-wrap" onclick="event.stopPropagation();">
              <span class="text-[10px] font-bold text-[#1D4D4F] bg-[#E6F4F1] px-2 py-0.5 rounded-full">
                ${escapeHtml(ev.category || 'Umum')}
              </span>
              ${hasSource ? `
                <a href="${safeUrl(ev.source_url)}" target="_blank" rel="noopener noreferrer" title="Buka sumber asli di ${escapeHtml(srcMeta.label)}" aria-label="Buka sumber asli di ${escapeHtml(srcMeta.label)} — kajian #${ev.id}" class="inline-flex items-center text-[10px] font-bold text-[#1C2224] bg-[#F3F4F6] px-2 py-0.5 rounded-full no-underline transition hover:opacity-90 cursor-pointer">
                  <span>${escapeHtml(srcMeta.label)} ↗</span>
                </a>
              ` : ''}
              ${ev.status === 'batal' ? '<span class="px-2 py-0.5 rounded text-[9px] font-bold bg-rose-50 text-rose-700">Dibatalkan</span>' : ''}
            </div>
          </div>
        </div>
      `;
      container.appendChild(card);
    });
    }

    // Tombol "Muat Lebih Banyak" jika masih ada
    const existingBtn = document.getElementById('loadMoreBtn');
    if (existingBtn) existingBtn.remove();
    if (filtered.length > displayLimit) {
      const loadMoreBtn = document.createElement('button');
      loadMoreBtn.id = 'loadMoreBtn';
      loadMoreBtn.className = 'w-full mt-4 py-3 rounded-2xl bg-[#E6F4F1] text-[#1D4D4F] font-bold text-sm hover:bg-[#D1EAE5] transition';
      loadMoreBtn.innerText = `Muat ${Math.min(12, filtered.length - displayLimit)} lagi (${filtered.length - displayLimit} tersisa)`;
      loadMoreBtn.onclick = () => {
        window.kajianDisplayLimit = displayLimit + 12;
        renderKajianFeed();
        // Scroll sedikit agar user tahu ada konten baru
        loadMoreBtn.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      };
      container.appendChild(loadMoreBtn);
    }
  }

  // 4. Modal Handlers
  function openDetailModalWithId(id) {
    const ev = rawKajians.find(k => k.id === id);
    if (ev) openDetailModalWithEvent(ev);
  }

  function openDetailModalWithEvent(ev) {
    if (!ev) return;
    currentDetailEvent = ev;
    // Tampilkan modal DULU agar selalu merespons klik
    const modal = document.getElementById('detailModal');
    if (modal) modal.classList.remove('hidden');
    // Reset posisi sheet (habis swipe-down sebelumnya) agar modal
    // selalu terbuka penuh, bukan tertinggal bergeser.
    var dh = document.getElementById('detailDragHandle');
    if (dh && dh.parentElement) { dh.parentElement.style.transform = ''; dh.parentElement.style.transition = ''; }
    // Kunci scroll latar: tanpa ini gestur scroll di dalam modal ikut
    // menggerakkan halaman di belakangnya (keluhan: background ikut
    // terswipe sehingga detail terasa tidak bisa di-scroll).
    document.body.style.overflow = 'hidden';
    try {
    const set = (id, val) => { const el = document.getElementById(id); if (el) el.innerText = val; };
    set('modalCategory', ev.category || 'Kajian Sunnah');
    set('modalTitle', ev.title);
    set('modalSpeaker', ev.ustadz);
    const speakerInitial = document.getElementById('modalSpeakerInitial');
    if (speakerInitial) speakerInitial.innerText = (ev.ustadz || 'U').replace(/^Ustadz\s+/i, '').charAt(0) || 'U';
    set('modalMasjid', ev.masjid);
    set('modalAddress', ev.address);
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
    // Deskripsi tidak lagi dibawa JSON beranda (B+C): ambil on-demand.
    // Modal tetap langsung tampil dengan teks fallback di atas; deskripsi
    // asli menyusul tanpa menunda klik. Gagal fetch = fallback tetap.
    if (!ev.description) {
      fetch('/api/kajian/' + encodeURIComponent(ev.id), { headers: { 'Accept': 'application/json' } })
        .then(r => r.ok ? r.json() : null)
        .then(j => {
          const d = j && j.data && j.data.description;
          if (d) {
            ev.description = d;
            if (currentDetailEvent && Number(currentDetailEvent.id) === Number(ev.id)) {
              const el = document.getElementById('modalDescription');
              if (el) el.innerText = d;
            }
          }
        })
        .catch(() => {});
    }
    
    // Flyer Preview - hanya tampil jika ada flyer asli
    const flyerWrapper = document.getElementById('modalFlyerWrapper');
    const flyerImg = document.getElementById('modalFlyerImg');
    const flyerLink = document.getElementById('modalFlyerFullLink');
    if (ev.poster_url) {
      flyerImg.dataset.poster = ev.poster_url;
      delete flyerImg.dataset.fb;
      flyerImg.style.display = '';
      flyerImg.onerror = function () { thumbFallback(this, 800); };
      flyerImg.src = supabaseThumb(ev.poster_url, 800);
      flyerLink.href = safeUrl(ev.poster_url);
      flyerWrapper.classList.remove('hidden');
    } else {
      flyerWrapper.classList.add('hidden');
    }

    // Status favorit ustadz di modal
    const favBtn = document.getElementById('modalFavUstadzBtn');
    const favIcon = document.getElementById('modalFavUstadzIcon');
    if (favBtn && favIcon) {
      const fav = isFavUstadz(ev.ustadz);
      favIcon.setAttribute('fill', fav ? 'currentColor' : 'none');
      favBtn.classList.toggle('text-amber-500', fav);
    }

    // Stempel DIBATALKAN
    const cancelledStamp = document.getElementById('modalCancelledStamp');
    if (cancelledStamp) {      if (ev.is_cancelled) {
        cancelledStamp.classList.remove('hidden');
        cancelledStamp.classList.add('flex');
      } else {
        cancelledStamp.classList.add('hidden');
        cancelledStamp.classList.remove('flex');
      }
    }

    // Sumber Asli Link
    const sourceBtn = document.getElementById('modalSourceBtn');
    const sourceGlyph = document.getElementById('modalSourceGlyph');
    const sourceLabel = document.getElementById('modalSourceLabel');
    const targetUrl = ev.source_url || ev.sourceUrl;
    if (targetUrl && targetUrl !== '#') {
      const srcMeta = SOURCE_META[ev.source] || SOURCE_META.facebook;
      sourceBtn.href = safeUrl(targetUrl);
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

    const routeBtn = document.getElementById('modalRouteBtn');
    if (routeBtn) routeBtn.onclick = () => openGoogleMapsRoute(ev.lat, ev.lon);
    } catch (err) { console.error('Error isi modal:', err); }
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
    // Tamu yang mengetuk Ikuti Majelis langsung diarahkan ke login.
    const isLoggedInFollow = {{ Auth::check() ? 'true' : 'false' }};
    if (!isLoggedInFollow) { window.location.href = '{{ route('login') }}'; return; }
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
    .then(res => res.json())
    .then(data => {
      // Rekonsiliasi server (audit 2026-10-21, FE-M4): respons WAJIB dicek —
      // bila server menolak / sesi habis / status tak sesuai, batalkan
      // perubahan lokal agar toast tidak berbohong.
      if (!data || data.status !== 'success' || typeof data.followed !== 'boolean' || data.followed !== newFollowed) {
        let l2 = getFollowedMajelisList();
        if (wasFollowed) { if (!l2.includes(masjid)) l2.push(masjid); } else { l2 = l2.filter(m => m !== masjid); }
        setFollowedMajelisList(l2);
        updateModalFollowButtonState(wasFollowed);
        showBookmarkToast('Gagal menyinkronkan ke server, coba lagi');
      }
    })
    .catch(err => {
      console.warn('Gagal follow majelis ke server:', err);
      let l2 = getFollowedMajelisList();
      if (wasFollowed) { if (!l2.includes(masjid)) l2.push(masjid); } else { l2 = l2.filter(m => m !== masjid); }
      setFollowedMajelisList(l2);
      updateModalFollowButtonState(wasFollowed);
      showBookmarkToast('Gagal menyinkronkan ke server, coba lagi');
    });
  }

  // Alias backwards compatibility
  function submitModalAttendance() {
    toggleModalSaveAction();
  }

  function closeDetailModal() {
    document.getElementById('detailModal').classList.add('hidden');
    document.body.style.overflow = '';
  }

  // Tutup modal dengan swipe-down dari drag handle (2026-10-29):
  // seret handle ke bawah >110px → sheet meluncur turun lalu modal
  // tertutup; kurang dari itu sheet kembali ke posisi awal.
  // touch-action:none di handle mencegah scroll konten saat menyeret.
  (function initDetailSwipeClose() {
    var handle = document.getElementById('detailDragHandle');
    var modal = document.getElementById('detailModal');
    if (!handle || !modal) return;
    var sheet = handle.parentElement;
    if (!sheet) return;
    var startY = 0, dy = 0, dragging = false;
    handle.addEventListener('pointerdown', function (e) {
      dragging = true; startY = e.clientY; dy = 0;
      sheet.style.transition = 'none';
      try { handle.setPointerCapture(e.pointerId); } catch (err) {}
    });
    handle.addEventListener('pointermove', function (e) {
      if (!dragging) return;
      dy = e.clientY - startY;
      if (dy > 0) sheet.style.transform = 'translateY(' + dy + 'px)';
    });
    function endDrag() {
      if (!dragging) return;
      dragging = false;
      sheet.style.transition = 'transform 0.18s ease';
      if (dy > 110) {
        sheet.style.transform = 'translateY(100%)';
        setTimeout(closeDetailModal, 180);
      } else {
        sheet.style.transform = '';
      }
    }
    handle.addEventListener('pointerup', endDrag);
    handle.addEventListener('pointercancel', endDrag);
  })();

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
  // Turbo: skrip halaman jalan ulang tiap navigasi — ganti listener lama
  // (bukan tambah) agar tak menumpuk; jangan jalan di halaman lain.
  if (window.__skIndexKeydown) window.removeEventListener('keydown', window.__skIndexKeydown);
  window.__skIndexKeydown = function(e) {
    if (e.key !== 'Escape') return;
    if (!document.getElementById('kajianCardsList')) return;
    closeDetailModal();
    closeFilterModal();
    closeLocationModal();
    closeSubmitModal();
  };
  window.addEventListener('keydown', window.__skIndexKeydown);

  // 5. Filter Actions
  function setCategoryFilter(cat) {
    activeCategory = cat;
    window.kajianDisplayLimit = 12; // Reset pagination
    document.querySelectorAll('.category-chip').forEach(btn => {
      if (btn.getAttribute('data-cat') === cat) {
        btn.className = 'category-chip whitespace-nowrap px-4 py-2 rounded-full font-bold transition-all bg-[#1D4D4F] text-white shadow-xs';
      } else {
        btn.className = 'category-chip whitespace-nowrap px-4 py-2 rounded-full font-medium transition-all bg-white dark:bg-[#162526] text-[#607072] dark:text-[#9BA8A9] border border-[#E5ECEB] hover:border-[#1D4D4F] hover:text-[#1D4D4F]';
      }
    });
    renderKajianFeed();
  }

  function setTodayFilter() {
    todayOnly = !todayOnly;
    window.kajianDisplayLimit = 12; // Reset pagination
    const btn = document.getElementById('todayToggle');
    if (btn) {
      btn.className = todayOnly
        ? 'whitespace-nowrap px-4 py-2 rounded-full font-bold transition-all bg-[#1D4D4F] text-white shadow-xs shrink-0'
        : 'whitespace-nowrap px-4 py-2 rounded-full font-medium transition-all bg-white text-[#607072] dark:text-[#9BA8A9] border border-[#E5ECEB] hover:border-[#1D4D4F] hover:text-[#1D4D4F] shrink-0';
    }
    renderKajianFeed();
  }

  function setRadiusFilter(r) {
    activeRadius = r;
    const rs = document.getElementById('radiusSelect');
    if (rs) rs.value = String(r);
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

  function setSortMode(mode) {
    sortMode = mode;
    document.querySelectorAll('.sort-chip').forEach(btn => {
      var active = btn.getAttribute('data-sort') === mode;
      btn.classList.toggle('border-[#1D4D4F]', active);
      btn.classList.toggle('bg-[#E6F4F1]', active);
      btn.classList.toggle('text-[#1D4D4F]', active);
      btn.classList.toggle('font-bold', active);
      btn.classList.toggle('bg-white', !active);
      btn.classList.toggle('font-medium', !active);
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
    todayOnly = false;
    const todayBtn = document.getElementById('todayToggle');
    if (todayBtn) todayBtn.className = 'whitespace-nowrap px-4 py-2 rounded-full font-medium transition-all bg-white text-[#607072] dark:text-[#9BA8A9] border border-[#E5ECEB] hover:border-[#1D4D4F] hover:text-[#1D4D4F] shrink-0';
    
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
  // Turbo: ganti listener lama (bukan tambah); hanya jalan saat beranda tampil.
  if (window.__skIndexLocHandler) window.removeEventListener('sk:location-updated', window.__skIndexLocHandler);
  window.__skIndexLocHandler = (e) => {
    if (!e.detail) return;
    if (!document.getElementById('currentLocationName')) return;
    userCoords = {
      lat: e.detail.lat,
      lon: e.detail.lon,
      name: e.detail.name || 'Lokasi Saya'
    };
    const el = document.getElementById('currentLocationName');
    if (el) el.innerText = userCoords.name;
    updatePrayerDisplay();
    renderKajianFeed();
  };
  window.addEventListener('sk:location-updated', window.__skIndexLocHandler);

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
    // Aturan user 2026-10-20: simpan kajian HANYA untuk user login.
    // Tamu yang mengetuk Simpan langsung diarahkan ke halaman login —
    // tidak ada lagi simpan lokal khusus tamu.
    const isLoggedIn = {{ Auth::check() ? 'true' : 'false' }};
    if (!isLoggedIn) { window.location.href = '{{ route('login') }}'; return; }
    const numericId = Number(kajianId);
    // Penjaga in-flight (audit 2026-10-21, FE-M3): abaikan ketukan ganda
    // selama toggle untuk id ini masih menunggu jawaban server.
    window._bmInFlight = window._bmInFlight || new Set();
    if (window._bmInFlight.has(numericId)) return;
    window._bmInFlight.add(numericId);
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

    // 4. Sinkronkan ke server (tamu sudah diarahkan ke login di atas)
    {
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
        window._bmInFlight.delete(numericId);
        if (data && data.status === 'auth_required') {
          // Belum login: batalkan update optimistis agar tampilan jujur
          syncAllBookmarkButtons(numericId, wasSaved);
          if (wasSaved) userBookmarkIds.add(numericId); else userBookmarkIds.delete(numericId);
          try { localStorage.setItem('sinikajian_bookmarks', JSON.stringify(Array.from(userBookmarkIds))); } catch(e) {}
          showBookmarkToast('Masuk dulu untuk menyimpan kajian');
          return;
        }
        if (data && data.status === 'error') {
          syncAllBookmarkButtons(numericId, wasSaved);
          if (wasSaved) userBookmarkIds.add(numericId); else userBookmarkIds.delete(numericId);
          try { localStorage.setItem('sinikajian_bookmarks', JSON.stringify(Array.from(userBookmarkIds))); } catch(e) {}
          showBookmarkToast(data.message || 'Gagal menyimpan, coba lagi');
          return;
        }
        if (data && typeof data.saved === 'boolean') {
          if (data.saved !== newSaved) {
            syncAllBookmarkButtons(numericId, data.saved);
            if (data.saved) userBookmarkIds.add(numericId); else userBookmarkIds.delete(numericId);
            // Koreksi server WAJIB ikut tersimpan, agar Set & localStorage tidak berpisah lagi
            try { localStorage.setItem('sinikajian_bookmarks', JSON.stringify(Array.from(userBookmarkIds))); } catch(e) {}
          }
        }
      })
      .catch(err => {
        window._bmInFlight.delete(numericId);
        // Gagal total (jaringan/CSRF/HTTP error): batalkan update optimistis
        // agar tampilan tidak mengaku tersimpan padahal server tidak tahu.
        console.warn('Gagal sinkronisasi bookmark server:', err);
        syncAllBookmarkButtons(numericId, wasSaved);
        if (wasSaved) userBookmarkIds.add(numericId); else userBookmarkIds.delete(numericId);
        try { localStorage.setItem('sinikajian_bookmarks', JSON.stringify(Array.from(userBookmarkIds))); } catch(e) {}
        showBookmarkToast('Gagal menyinkronkan ke server, coba lagi');
      });
    }
  }

  function syncAllBookmarkButtons(numericId, saved) {
    document.querySelectorAll(`.bookmark-btn[data-kajian-id="${numericId}"]`).forEach(btn => {
      updateBookmarkBtn(btn, saved);
    });
    // Tombol bookmark di banner carousel (class spot-bm)
    document.querySelectorAll(`.spot-bm[data-kajian-id="${numericId}"]`).forEach(btn => {
      const svg = btn.querySelector('svg');
      if (svg) {
        svg.setAttribute('fill', saved ? '#FCD34D' : 'none');
        svg.setAttribute('stroke', saved ? '#FCD34D' : 'currentColor');
      }
    });
    if (currentDetailEvent && Number(currentDetailEvent.id) === numericId) {
      const modalBtn = document.getElementById('modalBookmarkBtn');
      if (modalBtn) updateBookmarkBtn(modalBtn, saved);

      const modalSaveBtn = document.getElementById('modalSaveBtn');
      const modalSaveLabel = document.getElementById('modalSaveLabel');
      const modalSaveIcon = document.getElementById('modalSaveIcon');
      if (modalSaveBtn) {
        if (saved) {
          modalSaveBtn.className = 'w-full py-3 px-2 rounded-2xl bg-amber-400 hover:bg-amber-500 text-[#1D4D4F] text-xs font-extrabold shadow-sm touch-btn flex items-center justify-center gap-1.5 transition';
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
        btnEl.className = 'w-9 h-9 rounded-full bg-[#F8FAF9] hover:bg-[#E6F4F1] border border-[#E5ECEB] text-[#607072] dark:text-[#9BA8A9] flex items-center justify-center touch-btn transition';
        const svg = btnEl.querySelector('svg');
        if (svg) svg.setAttribute('fill', 'none');
      }
    } else {
      if (saved) {
        btnEl.classList.remove('text-[#607072] dark:text-[#9BA8A9]');
        btnEl.classList.add('text-amber-500', 'bg-amber-50');
        const svg = btnEl.querySelector('svg');
        if (svg) svg.setAttribute('fill', 'currentColor');
      } else {
        btnEl.classList.add('text-[#607072] dark:text-[#9BA8A9]');
        btnEl.classList.remove('text-amber-500', 'bg-amber-50');
        const svg = btnEl.querySelector('svg');
        if (svg) svg.setAttribute('fill', 'none');
      }
    }
  }

  var toastTimeout = null;
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
  pageReady(() => {
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
    // (tidak dihapus saat logout agar tetap tampil setelah login ulang;
    //  avatar server dari Supabase jadi sumber utama)
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
    if (window.__skPrayerInterval) clearInterval(window.__skPrayerInterval);
    window.__skPrayerInterval = setInterval(updatePrayerDisplay, 60000); // Perbarui waktu sholat tiap menit
  });
</script>
@endsection
