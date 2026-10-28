@extends('layouts.app')

@section('title', 'Profil Jamaah - Majelisku')

@section('content')
<div class="max-w-md mx-auto space-y-4 pb-28 pt-2 px-1">

@if(!$user)
  <!-- Guest state: belum login -->
  <div class="rounded-3xl bg-[#EDF5F3] border border-[#D1ECE7] p-8 text-center">
    <div class="w-16 h-16 mx-auto rounded-full bg-[#1D4D4F] text-white flex items-center justify-center mb-4">
      <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
      </svg>
    </div>
    <h2 class="text-lg font-extrabold text-[#1C2224]">Belum Masuk</h2>
    <p class="text-sm text-[#607072] mt-2 mb-6">Masuk untuk menyimpan kajian favorit, mencatat kehadiran, dan mengelola profil Anda.</p>
    <div class="flex gap-3 justify-center">
      <a href="{{ route('login') }}" class="bg-[#1D4D4F] text-white font-bold px-6 py-3 rounded-xl text-sm">Masuk ke Akun</a>
      <a href="{{ route('register') }}" class="bg-white border border-[#1D4D4F] text-[#1D4D4F] font-bold px-6 py-3 rounded-xl text-sm">Daftar</a>
    </div>
  </div>
@else
  
  <!-- Hero Agenda Saya (desain mockup 2026-10-29) -->
  <div class="relative rounded-[28px] bg-gradient-to-br from-[#1D4D4F] to-[#0F292A] text-white p-5 shadow-md overflow-hidden">
    <div class="absolute inset-0 opacity-10 pointer-events-none" style="background-image:radial-gradient(circle at 85% 15%, rgba(255,255,255,0.5) 0, transparent 45%), radial-gradient(circle at 90% 85%, rgba(252,211,77,0.5) 0, transparent 40%);"></div>
    <div class="relative flex items-start justify-between gap-3">
      <div class="min-w-0">
        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-white/20 text-white text-[11px] font-bold">📅 Agenda Saya</span>
        <h1 class="text-[22px] leading-snug font-extrabold mt-2.5">Siap Menuntut Ilmu Hari Ini?</h1>
        @if(($agendaCount ?? 0) > 0)
        <p class="text-xs text-white/80 mt-1.5 leading-relaxed">Kamu punya {{ $agendaCount }} agenda kajian. Semoga Allah mudahkan langkahmu menuntut ilmu.</p>
        @else
        <p class="text-xs text-white/80 mt-1.5 leading-relaxed">Belum ada agenda kajian yang tersimpan. Temukan majelis ilmu di sekitarmu sekarang.</p>
        @endif
        <a href="{{ route('kajian.index') }}" class="inline-flex items-center gap-1 mt-3.5 px-4 py-2 rounded-full bg-white text-[#1D4D4F] text-xs font-extrabold shadow">Jelajahi Kajian →</a>
      </div>
      <div class="text-[56px] leading-none shrink-0 opacity-90">📖</div>
    </div>
  </div>

  <!-- 3. Agenda Terdekat Section (Figma Screen 5 Dinamis) -->
  <div class="space-y-2">
    @if(($agendaCount ?? 0) > 0)
    <div class="flex items-center justify-between px-1">
      <h3 class="text-sm font-bold text-[#1C2224]">Agenda terdekat</h3>
      <a href="{{ route('kajian.saved') }}" class="text-xs font-bold text-[#1D4D4F] hover:underline">
        Lihat semua
      </a>
    </div>
    @endif

    <div id="agendaListContainer" class="space-y-2">
    @php
      $allAgendaKajians = collect();
      // Sumber masuknya tiap kajian ke agenda, agar label di kartu jujur:
      // dulu SEMUA kartu berlabel "Tersimpan di Agenda" termasuk yang
      // masuk karena catatan kehadiran — user mengira simpannya gagal
      // terhapus (keluhan 2026-10-20). Prioritas label: hadir > simpan >
      // ikuti (urutan push di bawah, penulis pertama menang).
      $agendaSources = [];
      if(isset($attendances)) {
        foreach($attendances as $att) {
          if($att->kajian) {
            $allAgendaKajians->push($att->kajian);
            $agendaSources[$att->kajian->id] = 'Dicatat hadir';
          }
        }
      }
      if(isset($savedKajians)) {
        foreach($savedKajians as $sk) {
          $allAgendaKajians->push($sk);
          if(!isset($agendaSources[$sk->id])) $agendaSources[$sk->id] = 'Tersimpan di Agenda';
        }
      }
      if(isset($followedKajians)) {
        foreach($followedKajians as $fk) {
          $allAgendaKajians->push($fk);
          if(!isset($agendaSources[$fk->id])) $agendaSources[$fk->id] = 'Dari majelis diikuti';
        }
      }
      $allAgendaKajians = $allAgendaKajians->unique('id')->sortBy('event_datetime')->take(3);
    @endphp

    @if($allAgendaKajians->isNotEmpty())
    @foreach($allAgendaKajians as $agenda)
      @php
        $dt = $agenda->event_datetime ? \Carbon\Carbon::parse($agenda->event_datetime) : \Carbon\Carbon::now();
        $dateNum = $dt->format('d');
        $dateMonth = strtoupper($dt->translatedFormat('M'));
      @endphp
      <div class="rounded-2xl bg-white border border-[#E5ECEB] shadow-2xs overflow-hidden">
        <button type="button" onclick="toggleAgendaDetail({{ $agenda->id }})" class="block w-full p-3 text-left hover:bg-[#F8FAF9] transition group">
          <div class="flex items-center gap-3">
            <!-- Vertical Date Block -->
            <div class="w-12 h-12 rounded-xl bg-[#EDF5F3] border border-[#D1ECE7] text-[#1D4D4F] flex flex-col items-center justify-center shrink-0">
              <span class="text-base font-black leading-none">{{ $dateNum }}</span>
              <span class="text-[9px] font-extrabold uppercase tracking-wider mt-0.5">{{ $dateMonth }}</span>
            </div>

            <div class="flex-1 min-w-0">
              <h4 class="text-xs sm:text-sm font-bold text-[#1C2224] truncate group-hover:text-[#1D4D4F] transition">
                {{ $agenda->title }}
              </h4>
              <p class="text-[11px] text-[#607072] mt-0.5 truncate">
                {{ $agenda->time }} • {{ $agenda->masjid }}
              </p>
              <div class="inline-flex items-center gap-1 text-[9px] font-bold text-[#1D4D4F] bg-[#E8F5F2] px-2 py-0.5 rounded-full mt-1">
                <span class="w-1.5 h-1.5 rounded-full bg-[#1D4D4F]"></span>
                <span>{{ $agendaSources[$agenda->id] ?? 'Tersimpan di Agenda' }}</span>
              </div>
            </div>

            <div class="text-[#607072] group-hover:text-[#1D4D4F] transition pr-1">
              <svg id="agendaChevron_{{ $agenda->id }}" class="w-4 h-4 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
              </svg>
            </div>
          </div>
        </button>
        <!-- Detail ekspansi (terbuka langsung tanpa pindah halaman) -->
        <div id="agendaDetail_{{ $agenda->id }}" class="hidden border-t border-[#E5ECEB] p-3 space-y-2 bg-[#F8FAF9]">
          @if(!empty($agenda->poster_url))
          <img src="{{ $agenda->poster_url }}" alt="Flyer" class="w-full rounded-xl object-cover max-h-64" loading="lazy">
          @endif
          <div class="space-y-1 text-[11px] text-[#40514F]">
            <p><span class="font-bold text-[#1C2224]">Ustadz:</span> {{ $agenda->ustadz ?? '-' }}</p>
            <p><span class="font-bold text-[#1C2224]">Tanggal:</span> {{ $dt->translatedFormat('l, d F Y') }}</p>
            <p><span class="font-bold text-[#1C2224]">Waktu:</span> {{ $agenda->time ?? '-' }}</p>
            <p><span class="font-bold text-[#1C2224]">Masjid:</span> {{ $agenda->masjid ?? '-' }}</p>
            @if(!empty($agenda->address))
            <p><span class="font-bold text-[#1C2224]">Alamat:</span> {{ $agenda->address }}</p>
            @endif
            @if(!empty($agenda->category))
            <p><span class="font-bold text-[#1C2224]">Kategori:</span> {{ $agenda->category }}</p>
            @endif
          </div>
          <div class="flex gap-2 pt-1">
            @if(!empty($agenda->lat) && !empty($agenda->lon))
            <a href="https://www.google.com/maps/search/?api=1&query={{ $agenda->lat }},{{ $agenda->lon }}" target="_blank" rel="noopener" class="flex-1 text-center px-3 py-2 rounded-xl bg-[#1D4D4F] text-white text-[11px] font-bold hover:bg-[#153A3B] transition">Petunjuk Arah</a>
            @endif
            <a href="{{ route('kajian.show', $agenda->id) }}" class="flex-1 text-center px-3 py-2 rounded-xl bg-white border border-[#E5ECEB] text-[#1D4D4F] text-[11px] font-bold hover:border-[#1D4D4F]/40 transition">Halaman Detail</a>
          </div>
        </div>
      </div>
    @endforeach
    @endif
    </div>

    <!-- Ustadz Favorit (di dalam Agenda) -->
    <div class="space-y-2 pt-1">
      <div class="px-1 flex items-center justify-between">
        <h3 class="text-sm font-bold text-[#1C2224]">Ustadz Favorit</h3>
        <span id="favUstadzCount" class="text-[11px] font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full">0</span>
      </div>
      <div id="favUstadzList" class="space-y-2 max-h-56 overflow-y-auto pr-1 fav-scroll"></div>
      <div id="emptyFavUstadz" class="p-4 rounded-2xl bg-white border border-[#E5ECEB] text-center">
        <p class="text-xs text-[#607072]">Belum ada ustadz favorit. Tandai bintang di detail kajian untuk menambah.</p>
      </div>
      @if(!empty($topUstadz))
      <div class="pt-1">
        <p class="px-1 text-[11px] font-bold text-[#607072] uppercase tracking-wide mb-1.5">Sering muncul di kajian</p>
        <div class="flex flex-wrap gap-1.5">
          @foreach($topUstadz as $tu)
          <button onclick="addFavUstadz('{{ addslashes($tu) }}')" class="fav-chip px-2.5 py-1.5 rounded-full bg-white border border-[#E5ECEB] text-[11px] font-bold text-[#1D4D4F] hover:border-amber-500 hover:bg-amber-50 transition" data-ustadz="{{ $tu }}">+ {{ $tu }}</button>
          @endforeach
        </div>
      </div>
      @endif
    </div>
  </div>

  <!-- 4. Akun & Preferensi Grouped Card (Figma Screen 5) -->
  <div class="space-y-2">
    <div class="px-1">
      <h3 class="text-sm font-bold text-[#1C2224]">Akun & preferensi</h3>
    </div>

    <div class="rounded-3xl bg-white border border-[#E5ECEB] divide-y divide-[#E5ECEB] overflow-hidden shadow-2xs">
      
      @auth
        @if(Auth::user()->isAdmin())
          <!-- Item Admin: Panel Moderasi Kajian -->
          <a href="{{ route('admin.kajian.index') }}" class="flex items-center justify-between p-3.5 hover:bg-[#F8FAF9] transition bg-[#E6F4F1]/30">
            <div class="flex items-center gap-3 min-w-0">
              <div class="w-9 h-9 rounded-xl bg-[#E6F4F1] text-[#1D4D4F] flex items-center justify-center shrink-0 border border-[#D1EAE5]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
              </div>
              <div class="min-w-0">
                <span class="text-xs sm:text-sm font-bold text-[#1D4D4F] block">Panel Moderasi Kajian</span>
                <span class="text-[11px] text-[#607072] block truncate">Kelola & verifikasi jadwal pengajuan</span>
              </div>
            </div>
            <svg class="w-4 h-4 text-[#1D4D4F] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
          </a>
        @endif
      @endauth

      <!-- Item 1: Edit Profil -->
      <a href="{{ route('profile.edit') }}" class="flex items-center justify-between p-3.5 hover:bg-[#F8FAF9] transition">
        <div class="flex items-center gap-3 min-w-0">
          <div class="w-9 h-9 rounded-xl bg-[#EDF5F3] text-[#1D4D4F] flex items-center justify-center shrink-0 border border-[#D1ECE7]">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
            </svg>
          </div>
          <div class="min-w-0">
            <span class="text-xs sm:text-sm font-bold text-[#1C2224] block">Edit profil</span>
            <span class="text-[11px] text-[#607072] block truncate">Nama dan informasi pribadi</span>
          </div>
        </div>
        <svg class="w-4 h-4 text-[#607072] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
        </svg>
      </a>

      <!-- Item 2: Lokasi & Radius Pencarian -->
      <button type="button" onclick="openLocationModal()" class="w-full flex items-center justify-between p-3.5 hover:bg-[#F8FAF9] transition text-left">
        <div class="flex items-center gap-3 min-w-0">
          <div class="w-9 h-9 rounded-xl bg-[#EDF5F3] text-[#1D4D4F] flex items-center justify-center shrink-0 border border-[#D1ECE7]">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
          </div>
          <div class="min-w-0">
            <span class="text-xs sm:text-sm font-bold text-[#1C2224] block">Lokasi & radius pencarian</span>
            <span id="userProfileLocationRadius" class="text-[11px] text-[#607072] block truncate">Mendeteksi Lokasi...</span>
          </div>
        </div>
        <svg class="w-4 h-4 text-[#607072] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
        </svg>
      </button>

      <!-- Item 3: Pengingat Kajian dengan Toggle Switch -->
      <div class="flex items-center justify-between p-3.5 hover:bg-[#F8FAF9] transition">
        <div class="flex items-center gap-3 min-w-0">
          <div class="w-9 h-9 rounded-xl bg-[#EDF5F3] text-[#1D4D4F] flex items-center justify-center shrink-0 border border-[#D1ECE7]">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
            </svg>
          </div>
          <div class="min-w-0">
            <span class="text-xs sm:text-sm font-bold text-[#1C2224] block">Pengingat kajian</span>
            <span class="text-[11px] text-[#607072] block truncate">30 menit sebelum dimulai</span>
          </div>
        </div>
        
        <!-- Toggle Switch ON (Teal) -->
        <label class="relative inline-flex items-center cursor-pointer shrink-0">
          <input type="checkbox" checked class="sr-only peer" id="reminderToggle">
          <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#1D4D4F]"></div>
        </label>
      </div>

    </div>
  </div>

  <!-- Akses & Pengaturan (desain mockup 2026-10-29; grid dibuat sedikit lebih kecil dari mockup) -->
  <div class="space-y-2">
    <div class="px-1">
      <h3 class="text-sm font-bold text-[#1C2224]">Akses & Pengaturan</h3>
    </div>
    <div class="grid grid-cols-2 gap-3">
      @auth
        @if(Auth::user()->isAdmin())
      <a href="{{ route('admin.kajian.index') }}" class="relative rounded-3xl bg-[#1D4D4F] text-white p-3.5 shadow-2xs overflow-hidden">
        <span class="absolute top-2.5 right-2.5 px-2 py-0.5 rounded-full bg-amber-400 text-[#1D4D4F] text-[9px] font-extrabold">Perlu Verifikasi</span>
        <div class="text-2xl leading-none">📱</div>
        <h4 class="text-sm font-bold mt-2">Panel Moderasi</h4>
        <p class="text-[11px] text-white/75 mt-0.5">Kelola & verifikasi jadwal</p>
        <span class="absolute bottom-2.5 right-2.5 text-white/80 text-sm">→</span>
      </a>
        @endif
      @endauth
      <a href="{{ route('profile.edit') }}" class="relative rounded-3xl bg-[#2E7A7D] text-white p-3.5 shadow-2xs overflow-hidden">
        <div class="text-2xl leading-none">🪪</div>
        <h4 class="text-sm font-bold mt-2">Edit Profil</h4>
        <p class="text-[11px] text-white/75 mt-0.5">Perbarui data & foto</p>
        <span class="absolute bottom-2.5 right-2.5 text-white/80 text-sm">→</span>
      </a>
      <button type="button" onclick="openLocationModal()" class="relative rounded-3xl bg-[#3A7CA5] text-white p-3.5 shadow-2xs overflow-hidden text-left">
        <div class="text-2xl leading-none">📍</div>
        <h4 class="text-sm font-bold mt-2">Lokasi & Radius</h4>
        <p id="gridLocationRadius" class="text-[11px] text-white/75 mt-0.5 truncate">Mendeteksi Lokasi...</p>
        <span class="absolute bottom-2.5 right-2.5 text-white/80 text-sm">→</span>
      </button>
      <div class="relative rounded-3xl bg-[#2E7D5B] text-white p-3.5 shadow-2xs overflow-hidden">
        <div class="text-2xl leading-none">🔔</div>
        <h4 class="text-sm font-bold mt-2">Pengingat</h4>
        <p class="text-[11px] text-white/75 mt-0.5">30 menit sebelum mulai</p>
        <label class="absolute bottom-2.5 right-2.5 inline-flex items-center cursor-pointer">
          <input type="checkbox" checked class="sr-only peer" id="reminderToggleMirror">
          <div class="w-9 h-5 bg-white/30 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-white/50 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-white/40"></div>
        </label>
      </div>
    </div>
  </div>

  <!-- Bantuan & Privasi -->
  <div class="space-y-2">
    <div class="px-1">
      <h3 class="text-sm font-bold text-[#1C2224]">Bantuan & Privasi</h3>
    </div>
    <div class="rounded-3xl bg-white border border-[#E5ECEB] divide-y divide-[#E5ECEB] overflow-hidden shadow-2xs">
      <!-- Privasi & Keamanan (Modal Informasi Keamanan & Enkripsi) -->
      <button type="button" onclick="openPrivacyModal()" class="w-full flex items-center justify-between p-3.5 hover:bg-[#F8FAF9] text-left transition">
        <div class="flex items-center gap-3 min-w-0">
          <div class="w-9 h-9 rounded-xl bg-[#EDF5F3] text-[#1D4D4F] flex items-center justify-center shrink-0 border border-[#D1ECE7]">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
            </svg>
          </div>
          <div class="min-w-0">
            <span class="text-xs sm:text-sm font-bold text-[#1C2224] block">Privasi & keamanan</span>
            <span class="text-[11px] text-[#607072] block truncate">Enkripsi kredensial & privasi data</span>
          </div>
        </div>
        <svg class="w-4 h-4 text-[#607072] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
        </svg>
      </button>

      <!-- Bantuan & Tentang Majelisku (Kirim Pesan Langsung ke Email Owner) -->
      @php
        $ownerEmail = config('app.admin_email');
        $userName = Auth::check() ? Auth::user()->name : 'Jamaah Pengguna Majelisku';
        $userEmail = Auth::check() ? Auth::user()->email : 'Belum login';
        $mailSubject = rawurlencode('Bantuan Aplikasi Majelisku');
        $mailBody = rawurlencode("Assalamu'alaikum Owner Majelisku,\n\nSaya pengguna aplikasi Majelisku ingin menyampaikan kendala / pertanyaan / masukan:\n\n- Nama: {$userName}\n- Email Akun: {$userEmail}\n- Detail Pesan:\n");
        $mailUrl = "mailto:{$ownerEmail}?subject={$mailSubject}&body={$mailBody}";
      @endphp
      <button type="button" onclick="openHelpModal()" class="w-full flex items-center justify-between p-3.5 hover:bg-[#F8FAF9] text-left transition">
        <div class="flex items-center gap-3 min-w-0">
          <div class="w-9 h-9 rounded-xl bg-[#EDF5F3] text-[#1D4D4F] flex items-center justify-center shrink-0 border border-[#D1ECE7]">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
          </div>
          <div class="min-w-0">
            <span class="text-xs sm:text-sm font-bold text-[#1C2224] block">Bantuan & tentang Majelisku</span>
            <span class="text-[11px] text-[#607072] block truncate">Kirim pesan langsung ke email owner</span>
          </div>
        </div>
        <svg class="w-4 h-4 text-[#607072] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
        </svg>
      </button>
    </div>
  </div>

  <!-- 5. Session Actions (Masuk / Keluar) -->  <!-- 5. Session Actions (Masuk / Keluar) -->
  <div class="pt-2">
    @auth
      <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit" class="w-full py-3 rounded-2xl bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-bold transition flex items-center justify-center gap-2 border border-rose-100 touch-btn">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
          </svg>
          <span>Keluar Dari Akun</span>
        </button>
      </form>
    @else
      <div class="flex items-center gap-2">
        <a href="{{ route('login') }}" class="flex-1 py-3 rounded-2xl bg-[#1D4D4F] hover:bg-[#153A3B] text-white text-xs font-bold text-center transition shadow-2xs touch-btn">
          Masuk ke Akun
        </a>
        <a href="{{ route('register') }}" class="flex-1 py-3 rounded-2xl bg-white border border-[#E5ECEB] hover:bg-[#F8FAF9] text-[#1D4D4F] text-xs font-bold text-center transition shadow-2xs touch-btn">
          Daftar Akun Baru
        </a>
      </div>
    @endauth

    <p class="text-[10px] text-center text-[#607072] mt-4">
      Majelisku • Menemani langkah dalam ketaatan
    </p>
  </div>

</div>

<!-- Modal Bantuan & Kontak Owner -->
<div id="helpModal" class="fixed inset-0 z-50 hidden bg-black/50 backdrop-blur-xs flex items-end sm:items-center justify-center p-3 transition-opacity duration-300">
  <div class="bg-white rounded-3xl w-full max-w-md p-5 border border-[#E5ECEB] shadow-2xl space-y-4 animate-in fade-in zoom-in-95">
    
    <div class="flex items-start justify-between">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-2xl bg-[#EDF5F3] text-[#1D4D4F] flex items-center justify-center border border-[#D1ECE7]">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
          </svg>
        </div>
        <div>
          <h3 class="text-base font-extrabold text-[#1D4D4F]">Bantuan & Kontak Owner</h3>
          <p class="text-xs text-[#607072]">Hubungi langsung pemilik aplikasi Majelisku</p>
        </div>
      </div>
      <button onclick="closeHelpModal()" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200 flex items-center justify-center transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
      </button>
    </div>

    <!-- Informasi Email Owner -->
    <div class="p-3.5 rounded-2xl bg-[#F0F7F6] border border-[#D1ECE7] space-y-2">
      <div class="flex items-center justify-between">
        <span class="text-[11px] font-bold text-[#1D4D4F] uppercase tracking-wider">Email Resmi Pengembang</span>
        <span class="text-[10px] font-semibold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-full">Owner</span>
      </div>
      <p class="text-xs font-bold text-[#1C2224] break-all select-all">
        {{ $ownerEmail }}
      </p>
      <p class="text-[11px] text-[#607072] leading-relaxed">
        Punya pertanyaan seputar jadwal kajian, laporan kendala teknis, atau saran pengembangan? Pesan Anda akan langsung diterima oleh owner Majelisku.
      </p>
    </div>

    <!-- Aksi Kirim Email & Salin -->
    <div class="space-y-2 pt-1">
      <a href="{{ $mailUrl }}" class="w-full py-3.5 rounded-full bg-[#1D4D4F] hover:bg-[#153A3B] text-white font-bold text-xs sm:text-sm flex items-center justify-center gap-2 shadow-md transition touch-btn">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
        </svg>
        <span>Kirim Pesan ke Email Sekarang</span>
      </a>

      <button type="button" onclick="copyOwnerEmail('{{ $ownerEmail }}')" id="btnCopyEmail" class="w-full py-3 rounded-full bg-white border border-[#E5ECEB] hover:bg-[#F8FAF9] text-[#1D4D4F] font-bold text-xs flex items-center justify-center gap-2 transition touch-btn">
        <svg class="w-4 h-4 text-[#607072]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path>
        </svg>
        <span id="copyEmailText">Salin Alamat Email</span>
      </button>
    </div>

    <!-- Ringkasan Versi Aplikasi -->
    <div class="border-t border-[#E5ECEB] pt-3 text-center">
      <span class="text-[10px] text-[#607072]">Majelisku Progressive Web App • Versi 1.2.0</span>
    </div>

  </div>
</div>

<!-- Modal Privasi & Keamanan -->
<div id="privacyModal" class="fixed inset-0 z-50 hidden bg-black/50 backdrop-blur-xs flex items-end sm:items-center justify-center p-3 transition-opacity duration-300">
  <div class="bg-white rounded-3xl w-full max-w-md p-5 border border-[#E5ECEB] shadow-2xl space-y-4 animate-in fade-in zoom-in-95">
    
    <div class="flex items-start justify-between">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-2xl bg-[#EDF5F3] text-[#1D4D4F] flex items-center justify-center border border-[#D1ECE7]">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
          </svg>
        </div>
        <div>
          <h3 class="text-base font-extrabold text-[#1D4D4F]">Privasi & Keamanan</h3>
          <p class="text-xs text-[#607072]">Komitmen keamanan data jamaah Majelisku</p>
        </div>
      </div>
      <button onclick="closePrivacyModal()" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200 flex items-center justify-center transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
      </button>
    </div>

    <div class="space-y-2.5 text-xs text-[#607072]">
      <div class="p-3 rounded-2xl bg-[#F0F7F6] border border-[#D1ECE7] flex items-start gap-2.5">
        <span class="text-sm">🔒</span>
        <div>
          <strong class="text-[#1C2224] block">Kredensial Terenkripsi</strong>
          Kata sandi Anda dilindungi menggunakan hashing satu arah Bcrypt (12 rounds) standar industri dan tidak pernah disimpan dalam bentuk teks biasa.
        </div>
      </div>

      <div class="p-3 rounded-2xl bg-[#F0F7F6] border border-[#D1ECE7] flex items-start gap-2.5">
        <span class="text-sm">📍</span>
        <div>
          <strong class="text-[#1C2224] block">Privasi Lokasi GPS</strong>
          Koordinat GPS Anda hanya diproses di browser/perangkat Anda sendiri untuk mencari kajian terdekat dan menghitung kiblat. Lokasi tidak pernah dilacak atau disimpan di server.
        </div>
      </div>

      <div class="p-3 rounded-2xl bg-[#F0F7F6] border border-[#D1ECE7] flex items-start gap-2.5">
        <span class="text-sm">🛡️</span>
        <div>
          <strong class="text-[#1C2224] block">Perlindungan Akun</strong>
          Seluruh komunikasi dilindungi dengan SSL HTTPS dan token perlindungan CSRF untuk mencegah akses tidak sah.
        </div>
      </div>
    </div>

    <button type="button" onclick="closePrivacyModal()" class="w-full py-3 rounded-full bg-[#1D4D4F] hover:bg-[#153A3B] text-white font-bold text-xs transition touch-btn">
      Saya Mengerti
    </button>

  </div>
</div>

<!-- Modal: Ubah Lokasi & Radius Pencarian -->
<div id="locationModal" class="hidden fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/60 backdrop-blur-sm p-0 sm:p-4">
  <div class="bg-white w-full max-w-sm rounded-t-3xl sm:rounded-2xl p-5 space-y-4 shadow-xl">
    <div class="flex items-center justify-between border-b border-[#E5ECEB] pb-2">
      <h3 class="text-sm font-bold text-[#1C2224]">Pilih Lokasi & Radius</h3>
      <button onclick="closeLocationModal()" class="text-[#607072] hover:text-[#1C2224] p-1" aria-label="Tutup">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
      </button>
    </div>

    <button onclick="useGpsLocation(); closeLocationModal();" class="w-full flex items-center justify-between p-3 bg-[#E6F4F1] border border-[#D1ECE7] rounded-xl text-[#1D4D4F] text-xs font-bold hover:bg-[#D1EAE5] transition">
      <div class="flex items-center space-x-2">
        <svg class="w-4 h-4 text-[#1D4D4F]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
        </svg>
        <span>Gunakan Titik GPS Akurat Perangkat</span>
      </div>
      <span class="text-[11px] underline">Deteksi</span>
    </button>

    <div class="space-y-1.5 text-xs">
      <p class="text-[11px] font-bold text-[#607072] uppercase tracking-wider">Kota Pilihan Lainnya</p>
      <div class="grid grid-cols-2 gap-2">
        <button onclick="setCityLocation('Jakarta Selatan', -6.2615, 106.8106)" class="p-2.5 text-left bg-white border border-[#E5ECEB] hover:border-[#1D4D4F] rounded-xl font-medium text-[#1C2224] text-xs transition">Jakarta Selatan</button>
        <button onclick="setCityLocation('Jakarta Pusat', -6.1805, 106.8284)" class="p-2.5 text-left bg-white border border-[#E5ECEB] hover:border-[#1D4D4F] rounded-xl font-medium text-[#1C2224] text-xs transition">Jakarta Pusat</button>
        <button onclick="setCityLocation('Bandung Kota', -6.9175, 107.6191)" class="p-2.5 text-left bg-white border border-[#E5ECEB] hover:border-[#1D4D4F] rounded-xl font-medium text-[#1C2224] text-xs transition">Bandung</button>
        <button onclick="setCityLocation('Bekasi', -6.2383, 106.9756)" class="p-2.5 text-left bg-white border border-[#E5ECEB] hover:border-[#1D4D4F] rounded-xl font-medium text-[#1C2224] text-xs transition">Bekasi</button>
        <button onclick="setCityLocation('Bogor', -6.5971, 106.8060)" class="p-2.5 text-left bg-white border border-[#E5ECEB] hover:border-[#1D4D4F] rounded-xl font-medium text-[#1C2224] text-xs transition">Bogor</button>
        <button onclick="setCityLocation('Surabaya', -7.2575, 112.7521)" class="p-2.5 text-left bg-white border border-[#E5ECEB] hover:border-[#1D4D4F] rounded-xl font-medium text-[#1C2224] text-xs transition">Surabaya</button>
      </div>
    </div>
  </div>
</div>

<script>
  function openHelpModal() {
    const modal = document.getElementById('helpModal');
    if (modal) modal.classList.remove('hidden');
  }

  function closeHelpModal() {
    const modal = document.getElementById('helpModal');
    if (modal) modal.classList.add('hidden');
  }

  function openPrivacyModal() {
    const modal = document.getElementById('privacyModal');
    if (modal) modal.classList.remove('hidden');
  }

  function closePrivacyModal() {
    const modal = document.getElementById('privacyModal');
    if (modal) modal.classList.add('hidden');
  }

  function openLocationModal() {
    const modal = document.getElementById('locationModal');
    if (modal) modal.classList.remove('hidden');
  }

  function closeLocationModal() {
    const modal = document.getElementById('locationModal');
    if (modal) modal.classList.add('hidden');
  }

  function useGpsLocation() {
    if (window.SiniKajianGeo) {
      window.SiniKajianGeo.requestLiveGps();
    } else if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition((pos) => {
        updateProfileLocationDisplay({
          lat: pos.coords.latitude,
          lon: pos.coords.longitude,
          name: 'Lokasi GPS Perangkat',
          city: 'Lokasi GPS'
        });
      }, (err) => {
        alert('Gagal mendeteksi lokasi GPS.');
      }, { enableHighAccuracy: true });
    }
  }

  function setCityLocation(name, lat, lon) {
    if (window.SiniKajianGeo) {
      window.SiniKajianGeo.setManualCity(name, lat, lon);
    }
    updateProfileLocationDisplay({ name: name, city: name });
    closeLocationModal();
  }

  function updateProfileLocationDisplay(geo) {
    if (!geo) return;
    const badge = document.getElementById('userProfileLocationBadge');
    if (badge) {
      if (geo.city && geo.province) {
        badge.innerText = `${geo.city}, ${geo.province}`;
      } else if (geo.name) {
        badge.innerText = geo.name;
      } else {
        badge.innerText = 'Mendeteksi Lokasi...';
      }
    }
    const radiusText = document.getElementById('userProfileLocationRadius');
    if (radiusText) {
      const radius = localStorage.getItem('sk_search_radius') || '5 km';
      radiusText.innerText = `${geo.name || 'Lokasi GPS'} • ${radius}`;
    }
    const gridRadius = document.getElementById('gridLocationRadius');
    if (gridRadius) {
      const radiusGrid = localStorage.getItem('sk_search_radius') || '5 km';
      gridRadius.innerText = `${geo.name || 'Lokasi GPS'} • ${radiusGrid}`;
    }
  }

  // Dengarkan event pembaruan lokasi dari Geo-Engine
  // Turbo: ganti listener lama (bukan tambah).
  if (window.__skProfileLocHandler) window.removeEventListener('sk:location-updated', window.__skProfileLocHandler);
  window.__skProfileLocHandler = function(e) {
    if (e.detail) {
      updateProfileLocationDisplay(e.detail);
    }
  };
  window.addEventListener('sk:location-updated', window.__skProfileLocHandler);

  // Sinkronisasi otomatis dari localStorage saat halaman siap
  pageReady(function() {
    // 1. Sinkronisasi Avatar lokal jika server belum merender
    try {
      const localAvatar = localStorage.getItem('sinikajian_user_avatar');
      const img = document.getElementById('userProfileAvatarImg');
      const initial = document.getElementById('userProfileAvatarInitial');
      if (localAvatar && localAvatar.startsWith('data:image')) {
        if (img) {
          img.src = localAvatar;
          img.classList.remove('hidden');
        }
        if (initial) {
          initial.classList.add('hidden');
        }
      }
    } catch(e) {}

    // 2. Sinkronisasi Lokasi GPS dari cache
    try {
      const cached = JSON.parse(localStorage.getItem('sk_geo_state') || 'null');
      if (cached && (cached.name || cached.city)) {
        updateProfileLocationDisplay(cached);
      }
    } catch(e) {}

    // 3. Rekonsiliasi localStorage DARI server (server = sumber kebenaran).
    //    Pola lama di blok ini — menaikkan angka metrik memakai max() dari
    //    localStorage, menghidupkan agenda dari localStorage, lalu
    //    menyinkronkan ID lokal NAIK ke server via /api/bookmarks/sync —
    //    adalah sumber angka phantom di profil (2 Disimpan / 2 Majelis
    //    diikuti padahal user tidak menyimpan apa pun). Sudah dihapus:
    //    untuk user login, localStorage selalu ditimpa data server.
    @auth
    try {
      localStorage.setItem('sinikajian_bookmarks', JSON.stringify(@json($savedKajianIds ?? [])));
      localStorage.setItem('sinikajian_followed_majelis', JSON.stringify(@json($followedMajelisNames ?? [])));
    } catch(e) {}
    @endauth
  });

  function copyOwnerEmail(email) {
    if (navigator.clipboard) {
      navigator.clipboard.writeText(email).then(() => {
        const text = document.getElementById('copyEmailText');
        if (text) {
          text.innerText = '✓ Email Berhasil Disalin!';
          setTimeout(() => {
            text.innerText = 'Salin Alamat Email';
          }, 2500);
        }
      });
    } else {
      prompt('Salin email owner di bawah ini:', email);
    }
  }

  // Tutup modal jika klik di luar area konten
  // Turbo: ganti listener lama (bukan tambah).
  if (window.__skProfileClickHandler) window.removeEventListener('click', window.__skProfileClickHandler);
  window.__skProfileClickHandler = function(e) {
    const helpModal = document.getElementById('helpModal');
    const privacyModal = document.getElementById('privacyModal');
    const locationModal = document.getElementById('locationModal');
    if (e.target === helpModal) closeHelpModal();
    if (e.target === privacyModal) closePrivacyModal();
    if (e.target === locationModal) closeLocationModal();
  };
  window.addEventListener('click', window.__skProfileClickHandler);

  // Toggle detail agenda (ekspansi langsung, tanpa pindah halaman)
  window.toggleAgendaDetail = function(id) {
    const el = document.getElementById('agendaDetail_' + id);
    const chev = document.getElementById('agendaChevron_' + id);
    if (!el) return;
    const willOpen = el.classList.contains('hidden');
    el.classList.toggle('hidden');
    if (chev) chev.style.transform = willOpen ? 'rotate(180deg)' : '';
  };

  // Web Push Notification untuk pengingat kajian
  var VAPID_PUBLIC_KEY = '{{ config("services.vapid.public_key") }}';
  var reminderToggle = document.getElementById('reminderToggle');

  function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    return Uint8Array.from([...rawData].map(c => c.charCodeAt(0)));
  }

  async function subscribePush() {
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
      // iOS: Push hanya jalan dari PWA yang di-install (Add to Home Screen)
      if (isIOS) {
        alert('Di iPhone, notifikasi hanya bisa aktif dari aplikasi yang dipasang.\n\nCara: buka majelisku.web.id di Safari → Bagikan → "Tambah ke Layar Utama" → buka dari ikonnya, lalu aktifkan Pengingat Kajian di sini.');
      } else {
        alert('Browser tidak mendukung notifikasi push.');
      }
      return false;
    }
    // iOS tapi dibuka dari browser biasa (bukan dari ikon Home Screen)
    if (isIOS && !isStandalone) {
      alert('Di iPhone, notifikasi hanya bisa aktif dari aplikasi yang dipasang.\n\nCara: buka majelisku.web.id di Safari → Bagikan → "Tambah ke Layar Utama" → buka dari ikonnya, lalu aktifkan Pengingat Kajian di sini.');
      return false;
    }
    try {
      const permission = await Notification.requestPermission();
      if (permission !== 'granted') {
        alert('Izin notifikasi ditolak.');
        return false;
      }
      const reg = await navigator.serviceWorker.ready;
      let sub = await reg.pushManager.getSubscription();
      if (!sub) {
        sub = await reg.pushManager.subscribe({
          userVisibleOnly: true,
          applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY)
        });
      }
      const res = await fetch('/api/push/subscribe', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify(sub.toJSON())
      });
      return res.ok;
    } catch (e) {
      console.error('Push subscribe gagal:', e);
      return false;
    }
  }

  async function unsubscribePush() {
    try {
      const reg = await navigator.serviceWorker.ready;
      const sub = await reg.pushManager.getSubscription();
      if (sub) {
        await fetch('/api/push/unsubscribe', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
          body: JSON.stringify({ endpoint: sub.endpoint })
        });
        await sub.unsubscribe();
      }
      return true;
    } catch (e) {
      console.error('Push unsubscribe gagal:', e);
      return false;
    }
  }

  if (reminderToggle) {
    // Cek status langganan saat ini
    (async () => {
      if ('serviceWorker' in navigator && 'PushManager' in window) {
        const reg = await navigator.serviceWorker.ready;
        const sub = await reg.pushManager.getSubscription();
        reminderToggle.checked = !!sub;
        syncReminderMirror();
      }
    })();

    reminderToggle.addEventListener('change', async () => {
      if (reminderToggle.checked) {
        const ok = await subscribePush();
        if (!ok) reminderToggle.checked = false;
        else alert('Pengingat kajian aktif! Anda akan diberi tahu 30 menit sebelum kajian dimulai.');
      } else {
        await unsubscribePush();
      }
      syncReminderMirror();
    });
  }


  // Cermin toggle di kartu Pengingat grid (desain mockup 2026-10-29):
  // satu sumber kebenaran tetap #reminderToggle; cermin hanya memicu
  // klik yang sama dan mengikuti hasil akhirnya (termasuk rollback gagal).
  function syncReminderMirror() {
    const mirror = document.getElementById('reminderToggleMirror');
    if (mirror && reminderToggle) mirror.checked = reminderToggle.checked;
  }
  const reminderMirror = document.getElementById('reminderToggleMirror');
  if (reminderMirror) {
    reminderMirror.addEventListener('change', () => {
      if (!reminderToggle) return;
      reminderToggle.checked = reminderMirror.checked;
      reminderToggle.dispatchEvent(new Event('change'));
    });
  }

  // Render daftar ustadz favorit
  window.renderFavUstadz = function() {
    const listEl = document.getElementById('favUstadzList');
    const emptyEl = document.getElementById('emptyFavUstadz');
    const countEl = document.getElementById('favUstadzCount');
    if (!listEl) return;
    let favs = [];
    try { favs = JSON.parse(localStorage.getItem('majelisku_fav_ustadz') || '[]'); } catch (e) {}
    if (countEl) countEl.innerText = favs.length;
    if (!favs.length) {
      listEl.innerHTML = '';
      if (emptyEl) emptyEl.classList.remove('hidden');
      // Tampilkan kembali semua chip saran
      document.querySelectorAll('.fav-chip').forEach(chip => chip.style.display = '');
      return;
    }
    if (emptyEl) emptyEl.classList.add('hidden');
    listEl.innerHTML = favs.map(name => {
      const safe = name.replace(/'/g, "\\'");
      const initial = name.replace(/^Ustadz\s+/i, '').charAt(0) || 'U';
      return `
      <div class="py-2 px-3 rounded-xl bg-white border border-[#E5ECEB] flex items-center gap-2.5">
        <div class="w-7 h-7 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-[10px] shrink-0">${initial}</div>
        <div class="flex-1 min-w-0">
          <p class="text-xs font-bold text-[#1C2224] truncate">${name.replace(/</g, '&lt;')}</p>
          <a href="/ustadz/${encodeURIComponent(name)}" class="text-[10px] text-[#1D4D4F] font-medium hover:underline">Lihat kajiannya →</a>
        </div>
        <button onclick="removeFavUstadz('${safe}')" class="p-1.5 rounded-full text-[#607072] hover:text-red-600 transition shrink-0" aria-label="Hapus favorit">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
        </button>
      </div>`;
    }).join('');
    // Sembunyikan chip saran yang sudah jadi favorit
    document.querySelectorAll('.fav-chip').forEach(chip => {
      chip.style.display = favs.includes(chip.getAttribute('data-ustadz')) ? 'none' : '';
    });
  };
  window.renderFavUstadz();

  function removeFavUstadz(name) {
    let favs = [];
    try { favs = JSON.parse(localStorage.getItem('majelisku_fav_ustadz') || '[]'); } catch (e) {}
    favs = favs.filter(n => n !== name);
    try { localStorage.setItem('majelisku_fav_ustadz', JSON.stringify(favs)); } catch (e) {}
    window.renderFavUstadz();
  }

  function addFavUstadz(name) {
    let favs = [];
    try { favs = JSON.parse(localStorage.getItem('majelisku_fav_ustadz') || '[]'); } catch (e) {}
    if (!favs.includes(name)) {
      favs.push(name);
      try { localStorage.setItem('majelisku_fav_ustadz', JSON.stringify(favs)); } catch (e) {}
    }
    window.renderFavUstadz();
  }

  // Sembunyikan chip ustadz yang sudah jadi favorit (render awal ditangani renderFavUstadz)
</script>
@endif
@endsection
