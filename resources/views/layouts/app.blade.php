<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <script>
    // Dark mode: terapkan sebelum render untuk hindari flash
    (function() {
      try {
        var t = localStorage.getItem('majelisku-theme');
        if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
          document.documentElement.classList.add('dark');
        }
      } catch (e) {}
    })();
    function toggleTheme() {
      var el = document.documentElement;
      var dark = el.classList.toggle('dark');
      try { localStorage.setItem('majelisku-theme', dark ? 'dark' : 'light'); } catch (e) {}
      return dark;
    }
  </script>
  <title>@yield('title', 'Majelisku | Temukan Majelis Ilmu Terdekat')</title>
  
  <meta name="description" content="@yield('meta_description', 'Jadwal kajian Sunnah terdekat dan paling update, lengkap dengan jadwal sholat, pengingat adzan, dan penunjuk arah kiblat. Ikuti ustadz favoritmu.')">
  <meta name="theme-color" content="#1D4D4F">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="Majelisku">

  <!-- OpenGraph / WhatsApp / Telegram Card -->
  <meta property="og:type" content="@yield('og_type', 'website')">
  <meta property="og:url" content="{{ url()->current() }}">
  <meta property="og:title" content="@yield('og_title', 'Majelisku | Temukan Majelis Ilmu Terdekat')">
  <meta property="og:description" content="@yield('og_description', 'Temukan jadwal majelis ilmu sunnah, waktu sholat akurat, dan kompas kiblat.')">
  <meta property="og:image" content="@yield('og_image', asset('images/app-icon.svg'))">
  <meta property="og:locale" content="id_ID">
  <meta property="og:site_name" content="Majelisku">

  <!-- Twitter Card -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="@yield('og_title', 'Majelisku | Temukan Majelis Ilmu Terdekat')">
  <meta name="twitter:description" content="@yield('og_description', 'Temukan jadwal majelis ilmu sunnah, waktu sholat akurat, dan kompas kiblat.')">
  <meta name="twitter:image" content="@yield('og_image', asset('images/app-icon.svg'))">

  <!-- Favicon & App Icons -->
  <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32-v2.png') }}">
  <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
  <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
  <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-icon-v2.png') }}">
  <link rel="manifest" href="{{ asset('manifest.json') }}">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <!-- Tailwind CSS: build statis (public/css/app.css, dikompilasi dari
       tailwind.config.js) menggantikan runtime CDN — browser tak lagi
       mengompilasi utility di setiap halaman (upgrade kecepatan 2026-10-20) -->
  <link rel="stylesheet" href="{{ asset('css/app.css') }}?v=20261029a">
  <!-- Font self-hosted (2026-10-25): Plus Jakarta Sans dari /fonts, bukan
       Google Fonts — menghapus rantai render-blocking ke fonts.googleapis.com
       (Lighthouse: hemat ~750 md di 4G lambat). Preload agar FCP tidak menunggu. -->
  <link rel="preload" href="{{ asset('fonts/plus-jakarta-sans-latin.woff2') }}" as="font" type="font/woff2" crossorigin>
  @stack('styles')

  <!-- Turbo Drive 8.0.23 (bridging JS, 2026-10-26): navigasi antar halaman tanpa
       muat ulang penuh. Self-hosted di /js (bukan CDN). UMD build jalan otomatis. -->
  <script src="{{ asset('js/turbo.es2017-umd.js') }}?v=8.0.23" defer></script>
  <!-- Turbo snapshot cache DIMATIKAN: data (bookmark/follow/daftar) harus selalu segar
       dari server — pelajaran 2026-10-20, data basi adalah sumber utama keluhan user.
       Tombol kembali tetap lewat Turbo (tanpa muat ulang penuh), hanya ambil ulang data. -->
  <meta name="turbo-cache-control" content="no-cache">
  <script>
    // pageReady: pengganti DOMContentLoaded yang juga jalan di navigasi Turbo.
    // Load penuh: tunggu DOMContentLoaded bila dokumen masih loading.
    // Navigasi Turbo: skrip halaman jalan ulang saat DOM sudah lengkap -> jalankan langsung.
    window.pageReady = function(fn) {
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', fn);
      } else {
        fn();
      }
    };
  </script>

  <style>
    * {
      -webkit-tap-highlight-color: transparent;
      box-sizing: border-box;
    }
    
    html, body {
      max-width: 100vw;
      overflow-x: hidden;
      overscroll-behavior-y: auto;
      -webkit-overflow-scrolling: touch;
      user-select: none;
      -webkit-user-select: none;
    }
    
    input, textarea, select {
      user-select: auto;
      -webkit-user-select: auto;
    }

    .no-scrollbar::-webkit-scrollbar {
      display: none;
      width: 0;
      height: 0;
    }
    .no-scrollbar {
      -ms-overflow-style: none;
      scrollbar-width: none;
    }

    .touch-btn {
      transition: transform 0.12s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.12s ease;
    }
    .touch-btn:active {
      transform: scale(0.96);
      opacity: 0.88;
    }

    .safe-top {
      padding-top: env(safe-area-inset-top, 0px);
    }
    .safe-bottom {
      padding-bottom: env(safe-area-inset-bottom, 16px);
    }

    /* Bottom nav: paksa compositing layer sendiri agar tidak "nyangkut"
       di posisi lama saat toolbar browser HP (Chrome Android / Safari iOS)
       mengembang-menciut (insiden 2026-10-28: nav mengambang ~70px di atas
       bawah viewport di HP user, padahal di desktop menempel normal). */
    nav[aria-label="Navigasi Utama"] {
      transform: translateZ(0);
      -webkit-transform: translateZ(0);
    }
    .safe-top {
      padding-top: env(safe-area-inset-top, 0px);
    }
  </style>
  @yield('styles')
</head>
<body class="bg-[#F8FAF9] dark:bg-[#0F1B1C] text-[#1C2224] dark:text-[#E8EDED] font-sans antialiased min-h-screen flex flex-col justify-between selection:bg-[#E6F4F1] safe-top">

  <!-- Status Bar Offline Notice -->
  <div id="offlineNotice" class="hidden bg-amber-600 text-white text-xs font-semibold py-1.5 px-4 text-center sticky top-0 z-50">
    Mode Offline aktif: Anda menggunakan data tersimpan di perangkat.
  </div>

  <!-- Flash Notification Alert -->
  @if(session('success'))
  <div id="flashAlert" class="fixed top-4 left-1/2 transform -translate-x-1/2 z-50 w-11/12 max-w-md bg-emerald-800 text-white text-sm font-medium py-3 px-4 rounded-xl shadow-lg border border-emerald-700 flex items-center justify-between transition-all duration-300">
    <div class="flex items-center space-x-2">
      <svg class="w-5 h-5 text-emerald-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
      </svg>
      <span>{{ session('success') }}</span>
    </div>
    <button onclick="document.getElementById('flashAlert').remove()" class="text-emerald-200 hover:text-white p-1" aria-label="Tutup notifikasi">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
      </svg>
    </button>
  </div>
  @endif

  @if($errors->any())
  <div id="flashError" class="fixed top-4 left-1/2 transform -translate-x-1/2 z-50 w-11/12 max-w-md bg-rose-800 text-white text-sm font-medium py-3 px-4 rounded-xl shadow-lg border border-rose-700 flex items-center justify-between transition-all duration-300">
    <div class="flex items-center space-x-2">
      <svg class="w-5 h-5 text-rose-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
      </svg>
      <span>{{ $errors->first() }}</span>
    </div>
    <button onclick="document.getElementById('flashError').remove()" class="text-rose-200 hover:text-white p-1" aria-label="Tutup notifikasi">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
      </svg>
    </button>
  </div>
  @endif

  <!-- Konten Utama Halaman -->
  <main class="flex-1 pb-24">
    @yield('content')

    <!-- Banner Infaq: hanya untuk user yang sudah login (perintah user 2026-10-20) -->
    @auth
    <div class="max-w-md mx-auto px-4 mt-6 mb-4">
      <div style="background:linear-gradient(135deg,#1D4D4F 0%,#2A6B6E 100%); border-radius:16px; padding:20px; color:#fff; text-align:center; box-shadow:0 4px 16px rgba(29,77,79,.2);">
        <div style="font-size:24px; margin-bottom:8px;">🤲</div>
        <div style="font-weight:700; font-size:16px; margin-bottom:6px;">Dukung Majelisku Tetap Berjalan</div>
        <div style="font-size:13px; opacity:.9; margin-bottom:14px; line-height:1.5;">Infaq Anda membantu kami menjaga layanan tetap gratis dan terus berkembang menyebarkan info kajian sunnah.</div>
        <div style="background:rgba(255,255,255,.15); border-radius:10px; padding:12px; margin-bottom:12px;">
          <div style="font-size:12px; opacity:.8; margin-bottom:4px;">Bank Syariah Indonesia (BSI)</div>
          <div style="font-size:20px; font-weight:700; letter-spacing:1px;">7182045556</div>
          <div style="font-size:11px; opacity:.7; margin-top:4px;">a.n. Yayasan Insan Muslim</div>
        </div>
        <div style="font-size:11px; opacity:.7; margin-top:10px;">"Perumpamaan orang yang menginfakkan hartanya di jalan Allah seperti sebutir biji yang menumbuhkan tujuh tangkai" (QS. Al-Baqarah: 261)</div>
      </div>
    </div>
    @endauth
  </main>

  <!-- Tombol dark mode melayang (desain mockup 2026-10-29): di kanan bawah,
       tepat di atas bottom nav. Pakai toggleTheme() yang sudah ada di <head>;
       ikon disinkronkan tiap render (Turbo) lewat syncThemeFab(). -->
  <button type="button" id="themeFab" onclick="toggleTheme();syncThemeFab();" aria-label="Ganti tema gelap atau terang" class="fixed right-4 z-40 w-11 h-11 rounded-full bg-white dark:bg-[#162526] border border-[#E5ECEB] dark:border-[#1E2E2F] shadow-lg flex items-center justify-center text-base touch-btn" style="bottom:calc(84px + env(safe-area-inset-bottom, 0px));">
    <span id="themeFabMoon">🌙</span>
    <span id="themeFabSun" class="hidden">☀️</span>
  </button>
  <script>
    function syncThemeFab() {
      var dark = document.documentElement.classList.contains('dark');
      var moon = document.getElementById('themeFabMoon');
      var sun = document.getElementById('themeFabSun');
      if (moon) moon.classList.toggle('hidden', dark);
      if (sun) sun.classList.toggle('hidden', !dark);
    }
    syncThemeFab();
  </script>

  <!-- Bottom Navigation Bar Native PWA (5 Destinasi: Sholat ditambahkan 2026-10-28, Segmen No.2) -->
  <nav class="fixed bottom-0 left-0 right-0 z-40 bg-white/95 dark:bg-[#0F1B1C]/95 backdrop-blur-md border-t border-[#E5ECEB] dark:border-[#1E2E2F] safe-bottom shadow-lg" aria-label="Navigasi Utama">
    <div class="max-w-md mx-auto grid grid-cols-5 h-16">
      
      <!-- Tab 1: Dashboard / Beranda -->
      <a href="{{ route('kajian.index') }}" class="flex flex-col items-center justify-center py-1 touch-btn {{ request()->routeIs('kajian.index') ? 'text-[#1D4D4F] font-bold' : 'text-[#607072] hover:text-[#1D4D4F]' }}" aria-label="Dashboard">
        <svg class="w-6 h-6 mb-0.5" fill="{{ request()->routeIs('kajian.index') ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ request()->routeIs('kajian.index') ? '2' : '1.8' }}" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
        </svg>
        <span class="text-[11px] font-medium tracking-tight">Dashboard</span>
      </a>

      <!-- Tab 2: Majelis Terdekat -->
      <a href="{{ route('kajian.map') }}" class="flex flex-col items-center justify-center py-1 touch-btn {{ request()->routeIs('kajian.map') ? 'text-[#1D4D4F] font-bold' : 'text-[#607072] hover:text-[#1D4D4F]' }}" aria-label="Majelis Terdekat">
        <svg class="w-6 h-6 mb-0.5" fill="{{ request()->routeIs('kajian.map') ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ request()->routeIs('kajian.map') ? '2' : '1.8' }}" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ request()->routeIs('kajian.map') ? '2' : '1.8' }}" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
        </svg>
        <span class="text-[11px] font-medium tracking-tight">Terdekat</span>
      </a>

      <!-- Tab 3: Jadwal Sholat -->
      <a href="{{ route('kajian.sholat') }}" class="flex flex-col items-center justify-center py-1 touch-btn {{ request()->routeIs('kajian.sholat') ? 'text-[#1D4D4F] font-bold' : 'text-[#607072] hover:text-[#1D4D4F]' }}" aria-label="Jadwal Sholat">
        <svg class="w-6 h-6 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ request()->routeIs('kajian.sholat') ? '2' : '1.8' }}" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
        </svg>
        <span class="text-[11px] font-medium tracking-tight">Sholat</span>
      </a>

      <!-- Tab 4: Arah Kiblat -->
      <a href="{{ route('kajian.qibla') }}" class="flex flex-col items-center justify-center py-1 touch-btn {{ request()->routeIs('kajian.qibla') ? 'text-[#1D4D4F] font-bold' : 'text-[#607072] hover:text-[#1D4D4F]' }}" aria-label="Arah Kiblat">
        <svg class="w-6 h-6 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <circle cx="12" cy="12" r="9" stroke-width="{{ request()->routeIs('kajian.qibla') ? '2' : '1.8' }}"></circle>
          <polygon points="12 6 15 12 12 10 9 12 12 6" fill="{{ request()->routeIs('kajian.qibla') ? '#1D4D4F' : '#607072' }}"></polygon>
        </svg>
        <span class="text-[11px] font-medium tracking-tight">Kiblat</span>
      </a>

      <!-- Tab 5: Profil -->
      <a href="{{ route('profile') }}" class="flex flex-col items-center justify-center py-1 touch-btn {{ request()->routeIs('profile') || request()->routeIs('kajian.saved') ? 'text-[#1D4D4F] font-bold' : 'text-[#607072] hover:text-[#1D4D4F]' }}" aria-label="Profil">
        <svg class="w-6 h-6 mb-0.5" fill="{{ request()->routeIs('profile') || request()->routeIs('kajian.saved') ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ request()->routeIs('profile') ? '2' : '1.8' }}" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
        </svg>
        <span class="text-[11px] font-medium tracking-tight">Profil</span>
      </a>

    </div>
  </nav>

  <!-- PWA Service Worker Registration -->
  <script>
    // Turbo: skrip body jalan ulang tiap navigasi — daftarkan listener window
    // (load/online/offline) hanya sekali agar tidak menumpuk.
    if (!window.__skLayoutListeners) {
      window.__skLayoutListeners = true;
      if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
          navigator.serviceWorker.register('{{ asset("sw.js") }}')
            .then(function(reg) {
              console.log('SiniKajian PWA ServiceWorker aktif:', reg.scope);
            })
            .catch(function(err) {
              console.warn('SiniKajian PWA ServiceWorker error:', err);
            });
        });
      }
      window.addEventListener('online', updateOnlineStatus);
      window.addEventListener('offline', updateOnlineStatus);

      // Bottom nav: paksa reflow saat ukuran visual viewport berubah
      // (toolbar HP mengembang/menciut) agar nav fixed tidak tertinggal
      // di posisi lama. Hanya pada resize — bukan tiap scroll — supaya
      // tidak memaksa layout berulang saat pengguna menggulir.
      (function () {
        var nav = document.querySelector('nav[aria-label="Navigasi Utama"]');
        if (!nav) return;
        var reflowNav = function () { void nav.offsetHeight; };
        if (window.visualViewport) {
          window.visualViewport.addEventListener('resize', reflowNav);
        }
        window.addEventListener('resize', reflowNav);
      })();
    }

    // Monitor Network Connectivity
    function updateOnlineStatus() {
      const notice = document.getElementById('offlineNotice');
      if (notice) {
        if (!navigator.onLine) {
          notice.classList.remove('hidden');
        } else {
          notice.classList.add('hidden');
        }
      }
    }
    updateOnlineStatus();

    // Auto dismiss flash alerts after 5 seconds
    setTimeout(function() {
      const flash = document.getElementById('flashAlert');
      if (flash) flash.remove();
      const flashErr = document.getElementById('flashError');
      if (flashErr) flashErr.remove();
    }, 5000);
  </script>

  <!-- Zero-Click Auto Geolocation & Sensor Engine -->
  <script src="{{ asset('js/geo-engine.js') }}?v=20261025a" defer></script>

  @yield('scripts')

  <!-- Feedback / Saran (floating, di atas tombol dark/light) -->
  <button type="button" onclick="toggleFeedback()" aria-label="Kirim saran dan masukan" title="Saran & Masukan"
    class="fixed bottom-[10.75rem] right-4 z-40 w-11 h-11 rounded-full bg-[#1D4D4F] border border-[#1D4D4F] shadow-lg flex items-center justify-center text-white touch-btn">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M21 12a8 8 0 01-8 8H5l-2 2V12a8 8 0 018-8h2a8 8 0 018 8z"></path>
    </svg>
  </button>

  <div id="feedbackPanel" class="hidden fixed right-4 bottom-[14rem] z-50 w-[calc(100vw-2rem)] max-w-[320px] bg-white dark:bg-[#16282A] border border-[#E5ECEB] dark:border-[#2A3F40] rounded-2xl shadow-2xl overflow-hidden">
    <div class="flex items-center gap-2 px-4 py-3 bg-[#1D4D4F] text-white">
      <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M21 12a8 8 0 01-8 8H5l-2 2V12a8 8 0 018-8h2a8 8 0 018 8z"></path>
      </svg>
      <div class="flex-1 min-w-0">
        <p class="text-sm font-extrabold leading-tight">Saran &amp; Masukan</p>
        <p class="text-[11px] opacity-80 leading-tight">Bantu kami mengembangkan Majelisku</p>
      </div>
      <button type="button" onclick="toggleFeedback(false)" aria-label="Tutup panel saran" class="shrink-0 w-7 h-7 rounded-full bg-white/15 flex items-center justify-center touch-btn">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
      </button>
    </div>
    <form id="feedbackForm" class="px-4 py-3 space-y-2.5" onsubmit="return submitFeedback(event)">
      <div>
        <label for="fbCategory" class="block text-[11px] font-bold text-[#607072] mb-1">Kategori</label>
        <select id="fbCategory" class="w-full rounded-xl border border-[#E5ECEB] dark:border-[#2A3F40] bg-[#F8FAF9] dark:bg-[#0F1B1C] px-3 py-2 text-sm text-[#1C2224] dark:text-[#E8EDED] focus:outline-none focus:ring-2 focus:ring-[#1D4D4F]/40">
          <option>Saran Fitur</option>
          <option>Laporan Bug</option>
          <option>Info Kajian</option>
          <option selected>Lainnya</option>
        </select>
      </div>
      <div>
        <label for="fbMessage" class="block text-[11px] font-bold text-[#607072] mb-1">Pesan <span class="text-rose-500">*</span></label>
        <textarea id="fbMessage" rows="3" maxlength="1000" required placeholder="Tulis saran, laporan error, atau info kajian di sini…" class="w-full rounded-xl border border-[#E5ECEB] dark:border-[#2A3F40] bg-[#F8FAF9] dark:bg-[#0F1B1C] px-3 py-2 text-sm text-[#1C2224] dark:text-[#E8EDED] focus:outline-none focus:ring-2 focus:ring-[#1D4D4F]/40 resize-none"></textarea>
      </div>
      <div class="grid grid-cols-2 gap-2">
        <input id="fbName" type="text" maxlength="60" placeholder="Nama (opsional)" class="w-full rounded-xl border border-[#E5ECEB] dark:border-[#2A3F40] bg-[#F8FAF9] dark:bg-[#0F1B1C] px-3 py-2 text-sm text-[#1C2224] dark:text-[#E8EDED] focus:outline-none focus:ring-2 focus:ring-[#1D4D4F]/40">
        <input id="fbContact" type="text" maxlength="80" placeholder="WA/email (opsional)" class="w-full rounded-xl border border-[#E5ECEB] dark:border-[#2A3F40] bg-[#F8FAF9] dark:bg-[#0F1B1C] px-3 py-2 text-sm text-[#1C2224] dark:text-[#E8EDED] focus:outline-none focus:ring-2 focus:ring-[#1D4D4F]/40">
      </div>
      <input type="text" id="fbWebsite" name="website" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">
      <p id="fbError" class="hidden text-[11px] font-semibold text-rose-600"></p>
      <button id="fbSubmit" type="submit" class="w-full py-2.5 rounded-xl bg-[#1D4D4F] text-white text-sm font-bold touch-btn">Kirim Masukan</button>
      <p class="text-[10px] text-[#607072] text-center">Tanpa perlu login. Kontak hanya untuk tindak lanjut.</p>
    </form>
    <div id="feedbackThanks" class="hidden px-4 py-6 text-center">
      <div class="w-11 h-11 mx-auto rounded-full bg-[#E6F4F1] text-[#1D4D4F] flex items-center justify-center">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
      </div>
      <p class="text-sm font-extrabold text-[#1C2224] dark:text-[#E8EDED] mt-2">Jazakumullah khairan!</p>
      <p class="text-[11px] text-[#607072] mt-0.5">Masukanmu sudah kami terima dan akan kami baca.</p>
    </div>
  </div>
  <script>
    function toggleFeedback(force) {
      var p = document.getElementById('feedbackPanel');
      if (!p) return;
      var show = (typeof force === 'boolean') ? force : p.classList.contains('hidden');
      p.classList.toggle('hidden', !show);
      if (show) {
        var m = document.getElementById('fbMessage');
        if (m) setTimeout(function() { m.focus(); }, 60);
      }
    }
    function submitFeedback(e) {
      e.preventDefault();
      var btn = document.getElementById('fbSubmit');
      var err = document.getElementById('fbError');
      err.classList.add('hidden');
      btn.disabled = true;
      btn.textContent = 'Mengirim…';
      fetch('/api/feedback', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({
          category: document.getElementById('fbCategory').value,
          name: document.getElementById('fbName').value,
          contact: document.getElementById('fbContact').value,
          message: document.getElementById('fbMessage').value,
          page: window.location.pathname,
          website: document.getElementById('fbWebsite').value
        })
      }).then(function(r) {
        return r.json().catch(function() { return {}; }).then(function(j) { return { status: r.status, j: j }; });
      }).then(function(res) {
        if (res.j && res.j.ok) {
          document.getElementById('feedbackForm').classList.add('hidden');
          document.getElementById('feedbackThanks').classList.remove('hidden');
          setTimeout(function() {
            toggleFeedback(false);
            setTimeout(function() {
              document.getElementById('feedbackForm').reset();
              document.getElementById('feedbackForm').classList.remove('hidden');
              document.getElementById('feedbackThanks').classList.add('hidden');
              btn.disabled = false;
              btn.textContent = 'Kirim Masukan';
            }, 300);
          }, 2600);
        } else if (res.status === 429) {
          throw new Error('Terlalu sering mengirim — tunggu sebentar lalu coba lagi ya.');
        } else {
          throw new Error((res.j && res.j.error) || 'Gagal mengirim. Coba lagi ya.');
        }
      }).catch(function(ex) {
        err.textContent = ex.message || 'Gagal mengirim. Coba lagi ya.';
        err.classList.remove('hidden');
        btn.disabled = false;
        btn.textContent = 'Kirim Masukan';
      });
      return false;
    }
  </script>

  <!-- Dark Mode Toggle (floating) -->
  <button onclick="toggleTheme()" aria-label="Mode gelap/terang"
    class="fixed bottom-28 right-4 z-40 w-11 h-11 rounded-full bg-white dark:bg-[#1E2E2F] border border-[#E5ECEB] dark:border-[#2A3F40] shadow-lg flex items-center justify-center text-[#1D4D4F] dark:text-[#FCD34D] touch-btn">
    <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
    </svg>
    <svg class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.002 9.002 0 0012 21a9.002 9.002 0 008.354-5.646z"></path>
    </svg>
  </button>

  <!-- PWA Install Prompt -->
  <div id="pwaInstallBanner" style="display:none; position:fixed; bottom:20px; left:16px; right:16px; z-index:9999; background:#1D4D4F; color:#fff; border-radius:16px; padding:16px; box-shadow:0 8px 24px rgba(0,0,0,.25); align-items:center; gap:12px;">
    <div style="flex:1;">
      <div style="font-weight:700; font-size:15px;">Install Majelisku</div>
      <div style="font-size:13px; opacity:.85;">Akses lebih cepat langsung dari layar utama HP kamu.</div>
    </div>
    <button id="pwaInstallBtn" style="background:#F4A300; color:#1D4D4F; border:none; border-radius:10px; padding:10px 18px; font-weight:700; cursor:pointer;">Install</button>
    <button id="pwaDismissBtn" style="background:transparent; color:#fff; border:none; font-size:20px; cursor:pointer; padding:4px 8px;">×</button>
  </div>
  <script>
    // Turbo: skrip layout jalan ulang tiap navigasi — var (bukan let) agar
    // tak SyntaxError saat dieksekusi ulang; nilai dipertahankan antar kunjungan.
    var deferredPrompt = window.deferredPrompt || null;
    // Listener window sekali saja (handler baca DOM segar tiap kejadian).
    if (!window.__skPwaListeners) {
      window.__skPwaListeners = true;
      window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        if (!localStorage.getItem('pwaDismissed')) {
          const banner = document.getElementById('pwaInstallBanner');
          if (banner) banner.style.display = 'flex';
        }
      });
      window.addEventListener('appinstalled', () => {
        document.getElementById('pwaInstallBanner').style.display = 'none';
      });
    }
    document.getElementById('pwaInstallBtn')?.addEventListener('click', async () => {
      if (!deferredPrompt) return;
      deferredPrompt.prompt();
      await deferredPrompt.userChoice;
      deferredPrompt = null;
      document.getElementById('pwaInstallBanner').style.display = 'none';
    });
    document.getElementById('pwaDismissBtn')?.addEventListener('click', () => {
      document.getElementById('pwaInstallBanner').style.display = 'none';
      localStorage.setItem('pwaDismissed', '1');
    });
  </script>
  <script>
    // Prefetch navigasi (upgrade kecepatan 2026-10-20): muat lebih dulu
    // halaman tujuan saat jari/mouse menyentuh tautan, dan tujuan nav
    // bawah saat browser menganggur. Service worker hanya menyajikan
    // hasil prefetch bila <15 detik DAN tidak ada aksi simpan/hapus/
    // login diantaranya — data tidak akan pernah basi karenanya.
    (function () {
      // Turbo: skrip layout jalan ulang tiap navigasi — pasang listener
      // sekali saja, kalau tidak tiap kunjungan menambah duplikat.
      if (window.__skPrefetchInit) return;
      window.__skPrefetchInit = true;
      const done = new Set();
      function prefetch(href) {
        try {
          const u = new URL(href, location.origin);
          if (u.origin !== location.origin || u.pathname === location.pathname) return;
          if (u.search) return;
          if (/^\/(masuk|keluar|auth|login|logout)/.test(u.pathname)) return;
          const key = u.pathname;
          if (done.has(key)) return;
          done.add(key);
          fetch(u.pathname, { headers: { 'X-Prefetch': '1' }, credentials: 'same-origin' }).catch(() => {});
        } catch (e) {}
      }
      document.addEventListener('touchstart', (e) => {
        const a = e.target.closest('a[href]');
        if (a) prefetch(a.getAttribute('href'));
      }, { passive: true });
      document.addEventListener('mouseover', (e) => {
        const a = e.target.closest('a[href]');
        if (a) prefetch(a.getAttribute('href'));
      });
      // AUDIT 2026-10-21 (FE-H4): prefetch massal semua tujuan nav 1,5 dtk
      // setelah SETIAP load dihapus — membakar ratusan KB kuota per halaman
      // untuk halaman yang mungkin tak pernah dibuka. Prefetch kini hanya
      // dari niat nyata user (sentuh/hover tautan) di atas.
    })();
  </script>
  @php
    // Kunjungan akun pemilik/uji yang dikecualikan TIDAK dicatat ke
    // statistik traffic (perintah user 2026-10-21). Berlaku saat login
    // dengan akun tersebut; kunjungan tamu tetap dihitung normal.
    $trafficExcluded = auth()->check() && collect([strtolower((string) config('app.admin_email'))])
        ->merge(array_map('strtolower', (array) config('app.traffic_exclude_emails', [])))
        ->filter()->unique()
        ->contains(strtolower((string) auth()->user()->email));
  @endphp
  @unless($trafficExcluded)
  <script>
    // Beacon traffic first-party untuk menu admin Traffic: hanya path
    // halaman. Tamu -> endpoint anonim. User login -> endpoint sesi
    // (identitas diambil server dari sesi, bukan dari halaman ini) agar
    // admin bisa melihat aktivitas kunjungan per user (perintah user
    // 2026-10-23). Gagal = diam (tak penting).
    (function () {
      try {
        var p = location.pathname;
        if (p.indexOf('/admin') === 0 || p.indexOf('/api') === 0) return;
        var url = @json(auth()->check() ? '/traffic/hit-user' : '/api/traffic/hit');
        var body = JSON.stringify({ path: p });
        if (navigator.sendBeacon) {
          navigator.sendBeacon(url, new Blob([body], { type: 'application/json' }));
        } else {
          fetch(url, { method: 'POST', body: body, headers: { 'Content-Type': 'application/json' }, keepalive: true }).catch(function () {});
        }
      } catch (e) {}
    })();
  </script>
  @endunless
  @stack('scripts')
</body>
</html>
