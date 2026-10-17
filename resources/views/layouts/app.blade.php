<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <title>@yield('title', 'SiniKajian | Temukan Majelis Ilmu Terdekat')</title>
  
  <meta name="description" content="Aplikasi pencari majelis ilmu kajian sunnah terdekat, jadwal sholat astronomis presisi, dan penunjuk arah kiblat.">
  <meta name="theme-color" content="#0C5C40">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="SiniKajian">
  <link rel="manifest" href="{{ asset('manifest.json') }}">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <!-- Tailwind CSS CDN & Font Plus Jakarta Sans + Amiri -->
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  
  <!-- Leaflet CSS & JS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

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
            primary: '#0C5C40',
            secondary: '#2C654C',
            tertiary: '#B45309',
            neutral: '#FAF8F3',
            surface: '#FFFFFF',
            'card-border': '#E2E8F0',
            'text-primary': '#0F172A',
            'text-secondary': '#475569',
            'text-inverse': '#FFFFFF',
            'gold-accent': '#FCD34D',
            brand: {
              50: '#EFF6F1',
              100: '#DCEBE2',
              200: '#B9D7C6',
              300: '#8DBDA3',
              400: '#5D9C7D',
              500: '#3D7F60',
              600: '#2C654C',
              700: '#24523E',
              800: '#1E4233',
              900: '#0C5C40',
              950: '#073B29'
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
<body class="bg-[#FAF8F3] text-slate-900 font-sans antialiased min-h-screen flex flex-col justify-between selection:bg-emerald-200">

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
  </main>

  <!-- Bottom Navigation Bar Native PWA (5 Destinasi) -->
  <nav class="fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur-md border-t border-slate-200 safe-bottom shadow-lg" aria-label="Navigasi Utama">
    <div class="max-w-md mx-auto grid grid-cols-5 h-16">
      
      <!-- Tab 1: Kajian -->
      <a href="{{ route('kajian.index') }}" class="flex flex-col items-center justify-center py-1 touch-btn {{ request()->routeIs('kajian.index') ? 'text-emerald-800 font-bold' : 'text-slate-500 hover:text-emerald-700' }}" aria-label="Jadwal Kajian">
        <svg class="w-6 h-6 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ request()->routeIs('kajian.index') ? '2.4' : '1.8' }}" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
        </svg>
        <span class="text-[11px]">Kajian</span>
      </a>

      <!-- Tab 2: Peta -->
      <a href="{{ route('kajian.map') }}" class="flex flex-col items-center justify-center py-1 touch-btn {{ request()->routeIs('kajian.map') ? 'text-emerald-800 font-bold' : 'text-slate-500 hover:text-emerald-700' }}" aria-label="Peta Kajian">
        <svg class="w-6 h-6 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ request()->routeIs('kajian.map') ? '2.4' : '1.8' }}" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
        </svg>
        <span class="text-[11px]">Peta</span>
      </a>

      <!-- Tab 3: Kiblat -->
      <a href="{{ route('kajian.qibla') }}" class="flex flex-col items-center justify-center py-1 touch-btn {{ request()->routeIs('kajian.qibla') ? 'text-emerald-800 font-bold' : 'text-slate-500 hover:text-emerald-700' }}" aria-label="Arah Kiblat">
        <svg class="w-6 h-6 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ request()->routeIs('kajian.qibla') ? '2.4' : '1.8' }}" d="M12 2v2m0 16v2m10-10h-2M4 12H2m15.071-7.071l-1.414 1.414M8.343 15.657l-1.414 1.414m12.142 0l-1.414-1.414M8.343 8.343L6.929 6.929M12 8a4 4 0 100 8 4 4 0 000-8z"></path>
        </svg>
        <span class="text-[11px]">Kiblat</span>
      </a>

      <!-- Tab 4: Tersimpan -->
      <a href="{{ route('kajian.saved') }}" class="flex flex-col items-center justify-center py-1 touch-btn {{ request()->routeIs('kajian.saved') ? 'text-emerald-800 font-bold' : 'text-slate-500 hover:text-emerald-700' }}" aria-label="Kajian Tersimpan">
        <svg class="w-6 h-6 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ request()->routeIs('kajian.saved') ? '2.4' : '1.8' }}" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
        </svg>
        <span class="text-[11px]">Tersimpan</span>
      </a>

      <!-- Tab 5: Akun -->
      @auth
      <a href="{{ route('profile') }}" class="flex flex-col items-center justify-center py-1 touch-btn {{ request()->routeIs('profile') ? 'text-emerald-800 font-bold' : 'text-slate-500 hover:text-emerald-700' }}" aria-label="Profil Akun">
        <div class="w-6 h-6 mb-0.5 rounded-full bg-emerald-700 text-white text-[11px] font-bold flex items-center justify-center">
          {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
        </div>
        <span class="text-[11px]">Akun</span>
      </a>
      @else
      <a href="{{ route('login') }}" class="flex flex-col items-center justify-center py-1 touch-btn {{ request()->routeIs('login') || request()->routeIs('register') ? 'text-emerald-800 font-bold' : 'text-slate-500 hover:text-emerald-700' }}" aria-label="Masuk Akun">
        <svg class="w-6 h-6 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ request()->routeIs('login') || request()->routeIs('register') ? '2.4' : '1.8' }}" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
        </svg>
        <span class="text-[11px]">Masuk</span>
      </a>
      @endauth

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

  @yield('scripts')
</body>
</html>
