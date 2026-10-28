@extends('layouts.app')

@section('title', $kajian->title . ' - ' . $kajian->ustadz . ' | Majelisku')
@section('meta_description', 'Kajian ' . $kajian->title . ' bersama ' . $kajian->ustadz . ' di ' . $kajian->masjid . '. Hari ' . $kajian->day . ', ' . $kajian->time . '.')
@section('og_type', 'article')
@section('og_title', $kajian->title . ' - ' . $kajian->ustadz)
@section('og_description', 'Kajian di ' . $kajian->masjid . ' (' . $kajian->day . ', ' . $kajian->time . '). ' . \Illuminate\Support\Str::limit(strip_tags($kajian->description ?: 'Mari hadiri majelis ilmu syar\'i terdekat bersama keluarga dan sahabat.'), 140))
@section('og_image', $kajian->poster_url ?: asset('images/app-icon.svg'))

@section('content')
<div class="max-w-md mx-auto space-y-4 pb-28 pt-2 px-1">

  <!-- 1. Top Navigation Bar (Figma Screen 2) -->
  <header class="flex items-center justify-between py-1">
    <a href="{{ route('kajian.index') }}" class="w-10 h-10 rounded-full bg-white border border-[#E5ECEB] text-[#1D4D4F] flex items-center justify-center hover:bg-[#F8FAF9] active:scale-95 transition shadow-2xs" aria-label="Kembali">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M15 19l-7-7 7-7"></path>
      </svg>
    </a>
    
    <h1 class="text-base font-bold text-[#1D4D4F]">Detail Kajian</h1>
    
    <button onclick="shareKajian()" class="w-10 h-10 rounded-full bg-white border border-[#E5ECEB] text-[#1D4D4F] flex items-center justify-center hover:bg-[#F8FAF9] active:scale-95 transition shadow-2xs" aria-label="Bagikan">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
      </svg>
    </button>
  </header>

  <!-- 2. Hero Image Banner Card (Figma Screen 2) -->
  <div class="relative w-full rounded-3xl overflow-hidden shadow-sm border border-[#E5ECEB] bg-slate-100 aspect-[4/3] flex flex-col justify-between p-3.5">
    <!-- Background Image -->
    @php
      $heroImg = $kajian->poster_url ?: ($kajian->flyer_url ?: 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=800&q=80');
    @endphp
    <img src="{{ $heroImg }}" alt="{{ $kajian->title }}" class="absolute inset-0 w-full h-full object-cover">
    
    <!-- Top Overlay Elements -->
    <div class="relative z-10 flex items-center justify-between w-full">
      <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/95 backdrop-blur-md shadow-xs text-[10px] font-extrabold tracking-wider uppercase text-[#1D4D4F]">
        <span>✨</span>
        <span>KAJIAN PILIHAN</span>
      </div>
      
      <button onclick="toggleBookmarkDetailPage({{ $kajian->id }}, this)" class="w-9 h-9 rounded-full bg-black/40 backdrop-blur-md text-white flex items-center justify-center hover:bg-black/60 active:scale-95 transition" aria-label="Simpan kajian">
        <svg id="bookmarkDetailIcon" class="w-4 h-4 {{ $isBookmarked ? 'text-amber-300' : 'text-white' }}" fill="{{ $isBookmarked ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
        </svg>
      </button>
    </div>

    <!-- Bottom In-Image Teal Banner (Figma Screen 2) -->
    <div class="relative z-10 bg-[#1D4D4F] text-white p-3.5 rounded-2xl shadow-md space-y-1">
      <p class="text-[10px] font-bold tracking-wider text-[#A4D3CC] uppercase">
        {{ strtoupper(\Carbon\Carbon::parse($kajian->event_datetime)->translatedFormat('d M Y')) }} • {{ $kajian->time }}
      </p>
      <h2 class="text-sm sm:text-base font-extrabold leading-tight text-white">
        {{ $kajian->title }}
      </h2>
      <p class="text-[11px] text-white/90 flex items-center gap-1 truncate pt-0.5">
        <svg class="w-3.5 h-3.5 text-[#A4D3CC] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
        </svg>
        <span class="truncate">{{ $kajian->masjid }} • {{ $kajian->address }}</span>
      </p>
    </div>
  </div>

  <!-- 3. Category Tags / Badges Row (Figma Screen 2) -->
  <div class="flex items-center gap-2 overflow-x-auto no-scrollbar py-0.5">
    <span class="px-3 py-1 rounded-full bg-[#E8F5F2] text-[#1D4D4F] text-xs font-semibold whitespace-nowrap">
      {{ $kajian->category ?? 'Akhlak & hati' }}
    </span>
    <span class="px-3 py-1 rounded-full bg-[#E8F5F2] text-[#1D4D4F] text-xs font-semibold whitespace-nowrap">
      {{ $kajian->audience ? 'Terbuka untuk ' . strtolower($kajian->audience) : 'Terbuka untuk umum' }}
    </span>
    <span class="px-3 py-1 rounded-full bg-[#E8F5F2] text-[#1D4D4F] text-xs font-semibold whitespace-nowrap">
      Gratis
    </span>
  </div>

  <!-- 4. Primary Event Title (Figma Screen 2) -->
  <div>
    <h1 class="text-xl sm:text-2xl font-extrabold text-[#1D4D4F] tracking-tight leading-snug">
      {{ $kajian->title }}
    </h1>
  </div>

  <!-- 5. Structured Information Card (Figma Screen 2) -->
  <div class="rounded-2xl bg-[#F4F7F6] border border-[#E5ECEB] p-4 space-y-4 shadow-2xs">
    
    <!-- Item 1: Tanggal & Waktu -->
    <div class="flex items-start gap-3">
      <div class="w-10 h-10 rounded-xl bg-[#E8F5F2] text-[#1D4D4F] flex items-center justify-center shrink-0 border border-[#D1ECE7]">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
        </svg>
      </div>
      <div class="min-w-0">
        <span class="text-[10px] font-extrabold tracking-wider text-[#607072] uppercase block">TANGGAL & WAKTU</span>
        <p class="text-xs sm:text-sm font-bold text-[#1C2224] mt-0.5">
          {{ $kajian->day ? $kajian->day . ', ' : '' }}{{ \Carbon\Carbon::parse($kajian->event_datetime)->translatedFormat('d F Y') }} · {{ $kajian->time }}
        </p>
        <p class="text-[11px] text-[#607072] mt-0.5">Hadir 15 menit lebih awal</p>
      </div>
    </div>

    <hr class="border-[#E5ECEB]">

    <!-- Item 2: Tempat Kajian -->
    <div class="flex items-start gap-3">
      <div class="w-10 h-10 rounded-xl bg-[#E8F5F2] text-[#1D4D4F] flex items-center justify-center shrink-0 border border-[#D1ECE7]">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
        </svg>
      </div>
      <div class="min-w-0 flex-1">
        <span class="text-[10px] font-extrabold tracking-wider text-[#607072] uppercase block">TEMPAT KAJIAN</span>
        <p class="text-xs sm:text-sm font-bold text-[#1C2224] mt-0.5">
          {{ $kajian->masjid }}
        </p>
        <p class="text-[11px] text-[#607072] mt-0.5">
          {{ $kajian->address }}
        </p>
        
        @php
          $gmapsUrl = $kajian->lat && $kajian->lon 
            ? "https://www.google.com/maps/dir/?api=1&destination={$kajian->lat},{$kajian->lon}" 
            : "https://www.google.com/maps/search/?api=1&query=" . urlencode($kajian->masjid . ' ' . $kajian->address);
        @endphp

        @if($kajian->lat && $kajian->lon)
          <!-- Mini Mapbox Preview -->
          <div class="mt-2.5 relative w-full h-32 rounded-xl overflow-hidden border border-[#D1ECE7] shadow-2xs group">
            <div id="miniMap" class="w-full h-full"></div>
            <a href="{{ route('kajian.map') }}?selected={{ $kajian->id }}&lat={{ $kajian->lat }}&lon={{ $kajian->lon }}" class="absolute bottom-2 right-2 z-10 px-2.5 py-1 rounded-lg bg-[#1D4D4F] text-white text-[10px] font-bold shadow-md hover:bg-[#143839] transition-all flex items-center gap-1">
              <span>Buka Peta Penuh</span>
              <span>↗</span>
            </a>
          </div>
        @endif

        <a href="{{ $gmapsUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-xs font-bold text-[#1D4D4F] hover:underline mt-2">
          <span>Petunjuk arah navigasi</span>
          <span id="detailDistanceBadge" class="text-[11px] font-normal text-[#607072]">· 850 m</span>
          <span>↗</span>
        </a>
      </div>
    </div>

  </div>

  <!-- 6. Speaker / Presenter Section (Figma Screen 2) -->
  <div class="rounded-2xl bg-white border border-[#E5ECEB] p-3.5 flex items-center gap-3 shadow-2xs">
    <div class="w-12 h-12 rounded-full bg-[#E8F5F2] text-[#1D4D4F] flex items-center justify-center shrink-0 border border-[#D1ECE7]">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
      </svg>
    </div>
    <div class="min-w-0">
      <span class="text-[10px] font-medium text-[#607072] block">Bersama pemateri</span>
      <h3 class="text-sm font-bold text-[#1C2224] truncate">{{ $kajian->ustadz }}</h3>
      <p class="text-[11px] text-[#607072]">Pemateri kajian sunnah</p>
    </div>
  </div>

  <!-- 7. Content & Agenda Checklist ("Apa yang akan dibahas?") (Figma Screen 2) -->
  <div class="rounded-2xl bg-white border border-[#E5ECEB] p-4 space-y-3 shadow-2xs">
    <h3 class="text-sm font-bold text-[#1C2224]">Apa yang akan dibahas?</h3>
    <p class="text-xs text-[#607072] leading-relaxed">
      {{ $kajian->description ?: 'Belajar merawat hati di tengah kesibukan, agar langkah setiap hari terasa lebih tenang dan bermakna.' }}
    </p>

    <div class="space-y-2 pt-1">
      <div class="flex items-start gap-2.5 text-xs text-[#1C2224]">
        <div class="w-4 h-4 rounded-full bg-[#E8F5F2] text-[#1D4D4F] flex items-center justify-center font-bold text-[10px] shrink-0 mt-0.5">
          ✓
        </div>
        <span>Mengenal sebab hati mudah gelisah dan solusinya</span>
      </div>
      <div class="flex items-start gap-2.5 text-xs text-[#1C2224]">
        <div class="w-4 h-4 rounded-full bg-[#E8F5F2] text-[#1D4D4F] flex items-center justify-center font-bold text-[10px] shrink-0 mt-0.5">
          ✓
        </div>
        <span>Membangun kebiasaan zikir, doa, dan syukur harian</span>
      </div>
      <div class="flex items-start gap-2.5 text-xs text-[#1C2224]">
        <div class="w-4 h-4 rounded-full bg-[#E8F5F2] text-[#1D4D4F] flex items-center justify-center font-bold text-[10px] shrink-0 mt-0.5">
          ✓
        </div>
        <span>Tanya jawab langsung tentang problematika keseharian</span>
      </div>
    </div>

    @if($kajian->source_url)
    <div class="pt-2 border-t border-[#E5ECEB]">
      <a href="{{ $kajian->source_url }}" target="_blank" rel="noopener" class="text-xs font-bold text-[#1D4D4F] hover:underline flex items-center gap-1">
        <span>Sumber Pengumuman ({{ ucfirst($kajian->source ?? 'Medsos') }})</span>
        <span>↗</span>
      </a>
    </div>
    @endif
  </div>

  <!-- 8. Action Bar (statis di akhir konten, bukan floating — 2026-10-29) -->
  <div class="p-3 bg-white border border-[#E5ECEB] rounded-3xl shadow-2xs">
    <div class="max-w-md mx-auto space-y-1.5">
      <div class="grid grid-cols-2 gap-2">
        <!-- Tombol 1: Simpan Kajian -->
        <button onclick="toggleBookmarkDetailPage({{ $kajian->id }})" id="btnSaveDetail" class="w-full py-3 px-2 rounded-2xl border-2 border-[#1D4D4F] text-[#1D4D4F] bg-[#E6F4F1] hover:bg-[#D1EAE5] text-xs font-bold shadow-2xs touch-btn flex items-center justify-center gap-1.5 transition">
          <svg id="iconSaveDetail" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
          </svg>
          <span id="labelSaveDetail" class="truncate">Simpan Kajian</span>
        </button>

        <!-- Tombol 2: Ikuti Majelis -->
        <button onclick="toggleFollowDetailPage('{{ addslashes($kajian->masjid) }}')" id="btnFollowDetail" class="w-full py-3 px-2 rounded-2xl bg-[#1D4D4F] hover:bg-[#153A3B] text-white text-xs font-bold shadow-sm touch-btn flex items-center justify-center gap-1.5 transition">
          <svg id="iconFollowDetail" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
          </svg>
          <span id="labelFollowDetail" class="truncate">Ikuti Majelis</span>
        </button>
      </div>
      <p class="text-center text-[10px] text-[#607072]">
        Kajian tersimpan di agenda pribadi dan majelis tercatat diikuti di profil.
      </p>
    </div>
  </div>

</div>
@endsection

@section('scripts')
<script>
  var CSRF_TOKEN = '{{ csrf_token() }}';
  // Toast notifikasi halaman detail.
  // PENTING: fungsi ini sebelumnya TIDAK pernah didefinisikan di halaman
  // ini, padahal toggleBookmarkDetailPage & toggleFollowDetailPage
  // memanggilnya SEBELUM fetch ke server — ReferenceError menghentikan
  // keduanya di tengah, sehingga bookmark/follow hanya berubah di
  // localStorage dan tidak pernah sampai ke server. Itulah akar keluhan
  // "bookmark diketuk tidak menyala tapi tersimpan, tidak bisa unsave"
  // di halaman Detail Kajian.
  function showToastNotification(message) {
    let toast = document.getElementById('detailToast');
    if (!toast) {
      toast = document.createElement('div');
      toast.id = 'detailToast';
      toast.className = 'fixed left-1/2 bottom-24 z-[70] -translate-x-1/2 bg-[#1D4D4F] text-white text-xs font-bold px-4 py-2.5 rounded-full shadow-xl transition-opacity duration-300 whitespace-nowrap';
      document.body.appendChild(toast);
    }
    toast.innerText = message;
    toast.style.opacity = '1';
    clearTimeout(window.__detailToastTimeout);
    window.__detailToastTimeout = setTimeout(() => { toast.style.opacity = '0'; }, 2200);
  }

  var KAJIAN_LAT = {{ $kajian->lat ?: -6.8851 }};
  var KAJIAN_LON = {{ $kajian->lon ?: 107.6136 }};

  pageReady(() => {
    updateCalculatedDistance();
  });

  // Turbo: ganti listener lama (bukan tambah).
  if (window.__skShowLocHandler) window.removeEventListener('sk:location-updated', window.__skShowLocHandler);
  window.__skShowLocHandler = () => {
    updateCalculatedDistance();
  };
  window.addEventListener('sk:location-updated', window.__skShowLocHandler);

  function updateCalculatedDistance() {
    if (window.SiniKajianGeo) {
      const coords = window.SiniKajianGeo.getCoords();
      const dist = window.SiniKajianGeo.calculateDistance(coords.lat, coords.lon, KAJIAN_LAT, KAJIAN_LON);
      const el = document.getElementById('detailDistanceBadge');
      if (el) {
        if (dist < 1) {
          el.textContent = `· ${Math.round(dist * 1000)} m`;
        } else {
          el.textContent = `· ${dist.toFixed(1)} km`;
        }
      }
    }
  }

  function shareKajian() {
    const text = `*{{ $kajian->title }}*\nBersama: {{ $kajian->ustadz }}\nWaktu: {{ $kajian->day }}, {{ $kajian->time }}\nTempat: {{ $kajian->masjid }}\nAlamat: {{ $kajian->address }}\n\nDitemukan via Majelisku: {{ url()->current() }}`;

    if (navigator.share) {
      navigator.share({
        title: '{{ $kajian->title }}',
        text: text,
        url: window.location.href
      }).catch(() => {});
    } else {
      navigator.clipboard.writeText(text).then(() => {
        alert('Informasi kajian berhasil disalin ke clipboard.');
      });
    }
  }

  // Sinkronisasi status awal dari localStorage
  pageReady(() => {
    try {
      const currentId = {{ $kajian->id }};
      const currentMasjid = "{{ addslashes($kajian->masjid) }}";
      const isLoggedIn = {{ Auth::check() ? 'true' : 'false' }};
      const serverSaved = {{ $isBookmarked ? 'true' : 'false' }};
      let savedList = JSON.parse(localStorage.getItem('sinikajian_bookmarks') || '[]');
      const followedList = JSON.parse(localStorage.getItem('sinikajian_followed_majelis') || '[]');

      if (isLoggedIn) {
        // Server = sumber kebenaran: status awal dari server, localStorage
        // diselaraskan mengikutinya (pola lama membaca localStorage lebih
        // dulu sehingga status bisa terbalik dari server).
        if (serverSaved && !savedList.includes(currentId)) savedList.push(currentId);
        if (!serverSaved) savedList = savedList.filter(x => Number(x) !== currentId);
        try { localStorage.setItem('sinikajian_bookmarks', JSON.stringify(savedList)); } catch(e) {}
        updateSaveDetailState(serverSaved);
      } else {
        updateSaveDetailState(savedList.includes(currentId));
      }
      updateFollowDetailState(followedList.includes(currentMasjid));
    } catch(e) {}
  });

  function updateSaveDetailState(isSaved) {
    const icon = document.getElementById('bookmarkDetailIcon');
    if (icon) {
      if (isSaved) {
        icon.classList.add('text-amber-300');
        icon.setAttribute('fill', 'currentColor');
      } else {
        icon.classList.remove('text-amber-300');
        icon.setAttribute('fill', 'none');
      }
    }

    const btn = document.getElementById('btnSaveDetail');
    const lbl = document.getElementById('labelSaveDetail');
    const btnIcon = document.getElementById('iconSaveDetail');
    if (btn) {
      if (isSaved) {
        btn.className = "w-full py-3 px-2 rounded-2xl bg-amber-400 hover:bg-amber-500 text-[#1D4D4F] text-xs font-bold shadow-sm touch-btn flex items-center justify-center gap-1.5 transition";
        if (lbl) lbl.innerText = "✓ Tersimpan";
        if (btnIcon) btnIcon.setAttribute('fill', 'currentColor');
      } else {
        btn.className = "w-full py-3 px-2 rounded-2xl border-2 border-[#1D4D4F] text-[#1D4D4F] bg-[#E6F4F1] hover:bg-[#D1EAE5] text-xs font-bold shadow-2xs touch-btn flex items-center justify-center gap-1.5 transition";
        if (lbl) lbl.innerText = "Simpan Kajian";
        if (btnIcon) btnIcon.setAttribute('fill', 'none');
      }
    }
  }

  function updateFollowDetailState(isFollowed) {
    const btn = document.getElementById('btnFollowDetail');
    const lbl = document.getElementById('labelFollowDetail');
    if (btn) {
      if (isFollowed) {
        btn.className = "w-full py-3 px-2 rounded-2xl bg-[#1D4D4F] hover:bg-[#153A3B] text-emerald-300 text-xs font-bold shadow-sm touch-btn flex items-center justify-center gap-1.5 transition";
        if (lbl) lbl.innerText = "✓ Majelis Diikuti";
      } else {
        btn.className = "w-full py-3 px-2 rounded-2xl bg-[#1D4D4F] hover:bg-[#153A3B] text-white text-xs font-bold shadow-sm touch-btn flex items-center justify-center gap-1.5 transition";
        if (lbl) lbl.innerText = "Ikuti Majelis";
      }
    }
  }

  // Terapkan status bookmark ke localStorage + ikon/tombol (satu-satunya
  // jalur perubahan status di halaman ini, agar UI & penyimpanan sinkron).
  function applyBookmarkState(saved, numericId) {
    let list = [];
    try { list = JSON.parse(localStorage.getItem('sinikajian_bookmarks') || '[]'); } catch(e) {}
    if (saved) {
      if (!list.includes(numericId)) list.push(numericId);
    } else {
      list = list.filter(x => Number(x) !== numericId);
    }
    try { localStorage.setItem('sinikajian_bookmarks', JSON.stringify(list)); } catch(e) {}
    updateSaveDetailState(saved);
  }

  function toggleBookmarkDetailPage(id) {
    // Aturan user 2026-10-20: tamu yang mengetuk Simpan Kajian langsung
    // diarahkan ke halaman login — tidak ada simpan lokal khusus tamu.
    const isLoggedInBm = {{ Auth::check() ? 'true' : 'false' }};
    if (!isLoggedInBm) { window.location.href = '{{ route('login') }}'; return; }
    const numericId = Number(id);
    let savedList = [];
    try { savedList = JSON.parse(localStorage.getItem('sinikajian_bookmarks') || '[]'); } catch(e) {}

    const wasSaved = savedList.includes(numericId);
    const newSaved = !wasSaved;

    // Update optimistis
    applyBookmarkState(newSaved, numericId);
    showToastNotification(newSaved ? 'Kajian berhasil disimpan!' : 'Kajian dihapus dari tersimpan.');

    // Tamu sudah diarahkan ke login di atas; di sini pasti user login.
    {
      fetch(`/kajian/${numericId}/bookmark`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': CSRF_TOKEN,
          'Accept': 'application/json'
        }
      })
      .then(r => r.json())
      .then(data => {
        if (!data) return;
        if (data.status === 'auth_required') {
          applyBookmarkState(wasSaved, numericId);
          showToastNotification('Masuk dulu untuk menyimpan kajian');
          return;
        }
        // Vonis server menang: samakan tampilan dengannya.
        if (typeof data.saved === 'boolean' && data.saved !== newSaved) {
          applyBookmarkState(data.saved, numericId);
        }
      })
      .catch(err => {
        console.warn('Gagal sinkronisasi bookmark server:', err);
        applyBookmarkState(wasSaved, numericId);
        showToastNotification('Gagal menyinkronkan ke server, coba lagi');
      });
    }
  }

  function toggleFollowDetailPage(masjid) {
    // Tamu yang mengetuk Ikuti Majelis langsung diarahkan ke login.
    const isLoggedInFl = {{ Auth::check() ? 'true' : 'false' }};
    if (!isLoggedInFl) { window.location.href = '{{ route('login') }}'; return; }
    let list = [];
    try {
      list = JSON.parse(localStorage.getItem('sinikajian_followed_majelis') || '[]');
    } catch(e) {}

    const wasFollowed = list.includes(masjid);
    const newFollowed = !wasFollowed;

    if (newFollowed) {
      if (!list.includes(masjid)) list.push(masjid);
    } else {
      list = list.filter(m => m !== masjid);
    }

    try {
      localStorage.setItem('sinikajian_followed_majelis', JSON.stringify(list));
    } catch(e) {}

    updateFollowDetailState(newFollowed);
    showToastNotification(newFollowed ? `Mengikuti ${masjid}` : `Berhenti mengikuti ${masjid}`);

    fetch('{{ route("majelis.follow") }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': CSRF_TOKEN,
        'Accept': 'application/json'
      },
      body: JSON.stringify({ masjid: masjid })
    })
    .then(r => r.json())
    .then(data => {
      // Vonis server menang bila berbeda dari update optimistis.
      if (data && typeof data.followed === 'boolean' && data.followed !== newFollowed) {
        let l = [];
        try { l = JSON.parse(localStorage.getItem('sinikajian_followed_majelis') || '[]'); } catch(e) {}
        if (data.followed) { if (!l.includes(masjid)) l.push(masjid); }
        else { l = l.filter(m => m !== masjid); }
        try { localStorage.setItem('sinikajian_followed_majelis', JSON.stringify(l)); } catch(e) {}
        updateFollowDetailState(data.followed);
      }
    })
    .catch(err => {
      console.warn('Gagal follow majelis ke server:', err);
      let l = [];
      try { l = JSON.parse(localStorage.getItem('sinikajian_followed_majelis') || '[]'); } catch(e) {}
      if (wasFollowed) { if (!l.includes(masjid)) l.push(masjid); }
      else { l = l.filter(m => m !== masjid); }
      try { localStorage.setItem('sinikajian_followed_majelis', JSON.stringify(l)); } catch(e) {}
      updateFollowDetailState(wasFollowed);
      showToastNotification('Gagal menyinkronkan ke server, coba lagi');
    });
  }

  // Alias backwards compatibility
  function rsvpAttendance(id) {
    toggleBookmarkDetailPage(id);
  }
</script>

@if($kajian->lat && $kajian->lon)
  @push('styles')
  <link href="https://api.mapbox.com/mapbox-gl-js/v3.10.0/mapbox-gl.css" rel="stylesheet">
  <style>
    .mapboxgl-ctrl-bottom-right, .mapboxgl-ctrl-bottom-left { display: none !important; }
  </style>
  @endpush

  @push('scripts')
  <script src="https://api.mapbox.com/mapbox-gl-js/v3.10.0/mapbox-gl.js"></script>
  <script>
    pageReady(() => {
      const container = document.getElementById('miniMap');
      if (!container) return;
      // Turbo: Mapbox CDN dimuat async saat navigasi Turbo — tunggu event
      // load skripnya langsung (polling 3 detik terbukti kurang, uji
      // browser 2026-10-26); bila tag skrip tak ada, suntikkan sendiri.
      function ensureMiniMapbox(done, fail) {
        if (window.mapboxgl) { done(); return; }
        let script = document.querySelector('script[src*="mapbox-gl.js"]');
        if (!script) {
          script = document.createElement('script');
          script.src = 'https://api.mapbox.com/mapbox-gl-js/v3.10.0/mapbox-gl.js';
          document.head.appendChild(script);
        }
        let settled = false;
        const timer = setTimeout(() => { if (!settled) { settled = true; fail(); } }, 10000);
        script.addEventListener('load', () => {
          if (settled) return;
          settled = true; clearTimeout(timer);
          if (window.mapboxgl) done(); else fail();
        });
        script.addEventListener('error', () => {
          if (settled) return;
          settled = true; clearTimeout(timer); fail();
        });
      }
      const initMiniMap = () => {
        mapboxgl.accessToken = '{{ config("services.mapbox.token") }}';
        // Turbo: bebaskan peta mini lama (konteks WebGL) sebelum membuat yang baru.
        if (window.__skMiniMap) { try { window.__skMiniMap.remove(); } catch (e) {} window.__skMiniMap = null; }
        window.__skMiniMap = new mapboxgl.Map({
          container: 'miniMap',
          style: 'mapbox://styles/mapbox/streets-v12',
          center: [{{ (float)$kajian->lon }}, {{ (float)$kajian->lat }}],
          zoom: 14.8,
          interactive: false,
          attributionControl: false
        });

        const pinEl = document.createElement('div');
        pinEl.className = 'w-7 h-7 rounded-full bg-[#1D4D4F] border-2 border-white shadow-md flex items-center justify-center text-white text-xs pointer-events-none';
        pinEl.innerHTML = '🕌';
        new mapboxgl.Marker({ element: pinEl })
          .setLngLat([{{ (float)$kajian->lon }}, {{ (float)$kajian->lat }}])
          .addTo(window.__skMiniMap);
      };
      ensureMiniMapbox(initMiniMap, () => {});
    });
  </script>
  @endpush
@endif
@endsection
