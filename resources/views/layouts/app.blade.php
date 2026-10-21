<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
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
  
  <meta name="description" content="@yield('meta_description', 'Aplikasi pencari majelis ilmu kajian sunnah terdekat, jadwal sholat astronomis presisi, dan penunjuk arah kiblat.')">
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
  <link rel="stylesheet" href="{{ asset('css/app.css') }}?v=20261020a">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  @stack('styles')

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
          <div style="font-size:20px; font-weight:700; letter-spacing:1px; font-family:monospace;">7182045556</div>
          <div style="font-size:11px; opacity:.7; margin-top:4px;">a.n. Yayasan Insan Muslim</div>
        </div>
        <div style="font-size:11px; opacity:.7; margin-top:10px;">"Perumpamaan orang yang menginfakkan hartanya di jalan Allah seperti sebutir biji yang menumbuhkan tujuh tangkai" (QS. Al-Baqarah: 261)</div>
      </div>
    </div>
    @endauth
  </main>

  <!-- Bottom Navigation Bar Native PWA (4 Destinasi sesuai Desain Figma) -->
  <nav class="fixed bottom-0 left-0 right-0 z-40 bg-white/95 dark:bg-[#0F1B1C]/95 backdrop-blur-md border-t border-[#E5ECEB] dark:border-[#1E2E2F] safe-bottom shadow-lg" aria-label="Navigasi Utama">
    <div class="max-w-md mx-auto grid grid-cols-4 h-16">
      
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

      <!-- Tab 3: Arah Kiblat -->
      <a href="{{ route('kajian.qibla') }}" class="flex flex-col items-center justify-center py-1 touch-btn {{ request()->routeIs('kajian.qibla') ? 'text-[#1D4D4F] font-bold' : 'text-[#607072] hover:text-[#1D4D4F]' }}" aria-label="Arah Kiblat">
        <svg class="w-6 h-6 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <circle cx="12" cy="12" r="9" stroke-width="{{ request()->routeIs('kajian.qibla') ? '2' : '1.8' }}"></circle>
          <polygon points="12 6 15 12 12 10 9 12 12 6" fill="{{ request()->routeIs('kajian.qibla') ? '#1D4D4F' : '#607072' }}"></polygon>
        </svg>
        <span class="text-[11px] font-medium tracking-tight">Kiblat</span>
      </a>

      <!-- Tab 4: Profil -->
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
    window.addEventListener('online', updateOnlineStatus);
    window.addEventListener('offline', updateOnlineStatus);
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
  <script src="{{ asset('js/geo-engine.js') }}" defer></script>

  @yield('scripts')

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
    let deferredPrompt = null;
    window.addEventListener('beforeinstallprompt', (e) => {
      e.preventDefault();
      deferredPrompt = e;
      if (!localStorage.getItem('pwaDismissed')) {
        const banner = document.getElementById('pwaInstallBanner');
        if (banner) banner.style.display = 'flex';
      }
    });
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
    window.addEventListener('appinstalled', () => {
      document.getElementById('pwaInstallBanner').style.display = 'none';
    });
  </script>
  <script>
    // Prefetch navigasi (upgrade kecepatan 2026-10-20): muat lebih dulu
    // halaman tujuan saat jari/mouse menyentuh tautan, dan tujuan nav
    // bawah saat browser menganggur. Service worker hanya menyajikan
    // hasil prefetch bila <15 detik DAN tidak ada aksi simpan/hapus/
    // login diantaranya — data tidak akan pernah basi karenanya.
    (function () {
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
  <script>
    // Beacon traffic first-party untuk menu admin Traffic: hanya path
    // halaman, tanpa cookie / data pribadi. Gagal = diam (tak penting).
    (function () {
      try {
        var p = location.pathname;
        if (p.indexOf('/admin') === 0 || p.indexOf('/api') === 0) return;
        var body = JSON.stringify({ path: p });
        if (navigator.sendBeacon) {
          navigator.sendBeacon('/api/traffic/hit', new Blob([body], { type: 'application/json' }));
        } else {
          fetch('/api/traffic/hit', { method: 'POST', body: body, headers: { 'Content-Type': 'application/json' }, keepalive: true }).catch(function () {});
        }
      } catch (e) {}
    })();
  </script>
  @stack('scripts')
</body>
</html>
