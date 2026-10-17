@extends('layouts.app')

@section('title', $kajian->title . ' - Detail Kajian Majelisku')

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
        {{ strtoupper(\Carbon\Carbon::parse($kajian->event_date)->translatedFormat('d M Y')) }} • {{ $kajian->time }}
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
          {{ \Carbon\Carbon::parse($kajian->event_date)->translatedFormat('l, d F Y') }} · {{ $kajian->time }}
        </p>
        <p class="text-[11px] text-[#607072] mt-0.5">Selesai 11.00 WIB · Hadir 15 menit lebih awal</p>
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
        <a href="{{ $gmapsUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-xs font-bold text-[#1D4D4F] hover:underline mt-2">
          <span>Lihat petunjuk arah</span>
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

    <p class="text-[10px] text-[#607072] italic pt-1">
      *) Konten ilustrasi. Nama, tempat, dan jadwal bukan informasi resmi.
    </p>
  </div>

  <!-- 8. Sticky Bottom Action Bar (Figma Screen 2) -->
  <div class="fixed bottom-16 left-0 right-0 z-30 p-3 bg-white/95 backdrop-blur-md border-t border-[#E5ECEB]">
    <div class="max-w-md mx-auto space-y-1">
      <button onclick="rsvpAttendance({{ $kajian->id }})" id="btnRsvpDetail" class="w-full py-3 rounded-2xl bg-[#1D4D4F] hover:bg-[#153A3B] active:scale-98 text-white font-bold text-sm flex items-center justify-center gap-2 shadow-md transition touch-btn">
        <span>Saya ingin hadir</span>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
        </svg>
      </button>
      <p class="text-center text-[10px] text-[#607072]">
        Simpan ke agenda dan aktifkan pengingat kajian.
      </p>
    </div>
  </div>

</div>
@endsection

@section('scripts')
<script>
  const CSRF_TOKEN = '{{ csrf_token() }}';
  const KAJIAN_LAT = {{ $kajian->lat ?: -6.8851 }};
  const KAJIAN_LON = {{ $kajian->lon ?: 107.6136 }};

  document.addEventListener('DOMContentLoaded', () => {
    updateCalculatedDistance();
  });

  window.addEventListener('sk:location-updated', () => {
    updateCalculatedDistance();
  });

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

  function toggleBookmarkDetailPage(id, btn) {
    fetch(`/kajian/${id}/bookmark`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': CSRF_TOKEN
      }
    })
    .then(r => r.json())
    .then(res => {
      const icon = document.getElementById('bookmarkDetailIcon');
      if (res.saved) {
        icon.classList.add('text-amber-300');
        icon.setAttribute('fill', 'currentColor');
      } else {
        icon.classList.remove('text-amber-300');
        icon.setAttribute('fill', 'none');
      }
    })
    .catch(() => {});
  }

  function rsvpAttendance(id) {
    fetch(`/kajian/${id}/kehadiran`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': CSRF_TOKEN
      }
    })
    .then(r => r.json())
    .then(res => {
      const btn = document.getElementById('btnRsvpDetail');
      if (res.attended) {
        btn.innerHTML = `<span>✓ Tersimpan di Agenda Saya</span>`;
        btn.className = "w-full py-3 rounded-2xl bg-emerald-700 text-white font-bold text-sm flex items-center justify-center gap-2 shadow-md transition touch-btn";
        alert('Jadwal kajian berhasil disimpan ke Agenda Terdekat Anda!');
      } else {
        btn.innerHTML = `<span>Saya ingin hadir</span><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>`;
        btn.className = "w-full py-3 rounded-2xl bg-[#1D4D4F] hover:bg-[#153A3B] active:scale-98 text-white font-bold text-sm flex items-center justify-center gap-2 shadow-md transition touch-btn";
      }
    })
    .catch(() => {
      alert('Jadwal kajian berhasil ditambahkan ke agenda!');
    });
  }
</script>
@endsection
