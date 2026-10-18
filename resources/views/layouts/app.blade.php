<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
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
  <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
  <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
  <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
  <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
  <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
  <link rel="manifest" href="{{ asset('manifest.json') }}">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <!-- Tailwind CSS CDN & Font Plus Jakarta Sans + Amiri -->
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  
  <!-- Google Maps JS API -->
  <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDTqyoEtgTz9LCk5kQYGA486DR-NAgw-lA&loading=async" async defer></script>

  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          fontFamily: {
            sans: ['"Plus Jakarta Sans"', '-apple-system', 'BlinkMacSystemFont', 'sans-serif'],
            arabic: ['Amiri', 'serif']
          },
          colors: {
            primary: '#1D4D4F',
            secondary: '#235B5E',
            mint: '#E6F4F1',
            'mint-dark': '#D1EAE5',
            neutral: '#F8FAF9',
            surface: '#FFFFFF',
            'card-border': '#E5ECEB',
            'text-primary': '#1C2224',
            'text-secondary': '#607072',
            'text-inverse': '#FFFFFF',
            'gold-accent': '#FCD34D',
            brand: {
              50: '#F0F7F6',
              100: '#E6F4F1',
              200: '#D1EAE5',
              300: '#A4D3CC',
              400: '#5FABA2',
              500: '#2E7A7D',
              600: '#235B5E',
              700: '#1D4D4F',
              800: '#153A3B',
              900: '#0F292A',
              950: '#0A1B1C'
            }
          }
        }
      }
    }
  </script>

  <style>
    * {
      -webkit-tap-highlight-color: transparent;
      box-sizing: border-box;
    }
    
    html, body {
      max-width: 100vw;
      overflow-x: hidden;
      overscroll-behavior-y: none;
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
  </style>
  @yield('styles')
</head>
<body class="bg-[#F8FAF9] text-[#1C2224] font-sans antialiased min-h-screen flex flex-col justify-between selection:bg-[#E6F4F1]">

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

    <!-- Banner Infaq -->
    <div class="max-w-md mx-auto px-4 mt-6 mb-4">
      <div style="background:linear-gradient(135deg,#1D4D4F 0%,#2A6B6E 100%); border-radius:16px; padding:20px; color:#fff; text-align:center; box-shadow:0 4px 16px rgba(29,77,79,.2);">
        <div style="font-size:24px; margin-bottom:8px;">🤲</div>
        <div style="font-weight:700; font-size:16px; margin-bottom:6px;">Dukung Majelisku Tetap Berjalan</div>
        <div style="font-size:13px; opacity:.9; margin-bottom:14px; line-height:1.5;">Infaq Anda membantu kami menjaga layanan tetap gratis dan terus berkembang menyebarkan info kajian sunnah.</div>
        <a href="https://wa.me/6281380622197?text=Assalamu%27alaikum%2C%20saya%20ingin%20berinfaq%20untuk%20Majelisku" target="_blank" style="display:inline-block; background:#F4A300; color:#1D4D4F; font-weight:700; padding:10px 28px; border-radius:10px; text-decoration:none; font-size:14px;">Berinfaq Sekarang</a>
        <div style="font-size:11px; opacity:.7; margin-top:10px;">"Perumpamaan orang yang menginfakkan hartanya di jalan Allah seperti sebutir biji yang menumbuhkan tujuh tangkai" (QS. Al-Baqarah: 261)</div>
      </div>
    </div>
  </main>

  <!-- Bottom Navigation Bar Native PWA (4 Destinasi sesuai Desain Figma) -->
  <nav class="fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur-md border-t border-[#E5ECEB] safe-bottom shadow-lg" aria-label="Navigasi Utama">
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
  <script src="{{ asset('js/geo-engine.js') }}"></script>

  @yield('scripts')

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
</body>
</html>
