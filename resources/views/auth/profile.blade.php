@extends('layouts.app')

@section('title', 'Profil Jamaah - Majelisku')

@section('content')
<div class="max-w-md mx-auto space-y-4 pb-28 pt-2 px-1">
  
  <!-- 1. Header Area (Figma Screen 5) -->
  <header class="flex items-center justify-between">
    <div>
      <h1 class="text-xl font-extrabold text-[#1D4D4F] tracking-tight">Profil</h1>
      <p class="text-xs text-[#607072] mt-0.5">Langkah kecil, kebaikan setiap hari.</p>
    </div>
    
    <!-- Settings / Adjustment Sliders Button -->
    <button type="button" onclick="openPreferencesModal()" class="w-9 h-9 rounded-full bg-white border border-[#E5ECEB] text-[#1D4D4F] flex items-center justify-center hover:bg-[#F8FAF9] active:scale-95 transition shadow-2xs" aria-label="Pengaturan">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
      </svg>
    </button>
  </header>

  <!-- 2. Profile Summary Card (Figma Screen 5: Soft Mint Background, Avatar, 3 Stats) -->
  <div class="rounded-3xl bg-[#EDF5F3] border border-[#D1ECE7] p-5 text-center shadow-2xs">
    
    <!-- Circular Avatar with Deep Teal & Initials or Photo -->
    <div class="relative w-14 h-14 mx-auto">
      <img id="userProfileAvatarImg" src="{{ $user->avatar ?? '' }}" alt="{{ $user->name ?? 'User' }}" class="w-14 h-14 rounded-full object-cover shadow-sm ring-4 ring-white/60 {{ !empty($user->avatar) ? '' : 'hidden' }}">
      <div id="userProfileAvatarInitial" class="w-14 h-14 rounded-full bg-[#1D4D4F] text-white flex items-center justify-center font-extrabold text-base shadow-sm ring-4 ring-white/60 {{ !empty($user->avatar) ? 'hidden' : '' }}">
        {{ strtoupper(substr($user->name ?? 'Ahmad Fauzan', 0, 2)) }}
      </div>
    </div>

    <!-- User Information -->
    <h2 class="text-base font-extrabold text-[#1C2224] mt-2.5">
      {{ $user->name ?? 'Ahmad Fauzan' }}
    </h2>
    <p class="text-xs text-[#607072] mt-0.5">
      {{ $user->email ?? 'ahmad.fauzan@contoh.id' }}
    </p>

    <div class="flex items-center justify-center gap-1 text-xs text-[#607072] mt-1.5">
      <svg class="w-3.5 h-3.5 text-[#1D4D4F]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
      </svg>
      <span id="userProfileLocationBadge">Mendeteksi Lokasi...</span>
    </div>

    <hr class="border-[#D1ECE7] my-4">

    <!-- 3-Column Metrics Row: Disimpan, Agenda saya, Majelis diikuti (Figma Screen 5 Dinamis) -->
    <div class="grid grid-cols-3 divide-x divide-[#D1ECE7] text-center">
      <div class="px-2">
        <span id="profileSavedCount" class="text-lg font-black text-[#1D4D4F] block leading-tight">{{ $savedCount ?? 0 }}</span>
        <span class="text-[11px] font-medium text-[#607072] block mt-0.5">Disimpan</span>
      </div>
      <div class="px-2">
        <span id="profileAgendaCount" class="text-lg font-black text-[#1D4D4F] block leading-tight">{{ $agendaCount ?? 0 }}</span>
        <span class="text-[11px] font-medium text-[#607072] block mt-0.5">Agenda saya</span>
      </div>
      <div class="px-2">
        <span id="profileFollowedCount" class="text-lg font-black text-[#1D4D4F] block leading-tight">{{ $majelisCount ?? 0 }}</span>
        <span class="text-[11px] font-medium text-[#607072] block mt-0.5">Majelis diikuti</span>
      </div>
    </div>

  </div>

  <!-- 3. Agenda Terdekat Section (Figma Screen 5 Dinamis) -->
  <div class="space-y-2">
    <div class="flex items-center justify-between px-1">
      <h3 class="text-sm font-bold text-[#1C2224]">Agenda terdekat</h3>
      <a href="{{ route('kajian.saved') }}" class="text-xs font-bold text-[#1D4D4F] hover:underline">
        Lihat semua
      </a>
    </div>

    @php
      $allAgendaKajians = collect();
      if(isset($attendances)) {
        foreach($attendances as $att) {
          if($att->kajian) $allAgendaKajians->push($att->kajian);
        }
      }
      if(isset($savedKajians)) {
        foreach($savedKajians as $sk) {
          $allAgendaKajians->push($sk);
        }
      }
      $allAgendaKajians = $allAgendaKajians->unique('id')->sortBy('event_datetime')->take(3);
    @endphp

    @forelse($allAgendaKajians as $agenda)
      @php
        $dt = $agenda->event_datetime ? \Carbon\Carbon::parse($agenda->event_datetime) : \Carbon\Carbon::now();
        $dateNum = $dt->format('d');
        $dateMonth = strtoupper($dt->translatedFormat('M'));
      @endphp
      <a href="{{ route('kajian.show', $agenda->id) }}" class="block p-3 rounded-2xl bg-white border border-[#E5ECEB] hover:border-[#1D4D4F]/40 shadow-2xs transition group">
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
              <span>Tersimpan di Agenda</span>
            </div>
          </div>

          <div class="text-[#607072] group-hover:text-[#1D4D4F] transition pr-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
          </div>
        </div>
      </a>
    @empty
      <div id="emptyAgendaCard" class="p-4 rounded-2xl bg-white border border-[#E5ECEB] text-center space-y-2">
        <p class="text-xs text-[#607072]">Belum ada agenda kajian yang tersimpan.</p>
        <a href="{{ route('kajian.index') }}" class="inline-block px-4 py-2 rounded-xl bg-[#1D4D4F] text-white text-xs font-bold shadow-2xs hover:bg-[#153A3B] transition">
          Jelajahi Kajian
        </a>
      </div>
    @endforelse
  </div>

  <!-- 4. Akun & Preferensi Grouped Card (Figma Screen 5) -->
  <div class="space-y-2">
    <div class="px-1">
      <h3 class="text-sm font-bold text-[#1C2224]">Akun & preferensi</h3>
    </div>

    <div class="rounded-3xl bg-white border border-[#E5ECEB] divide-y divide-[#E5ECEB] overflow-hidden shadow-2xs">
      
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

      <!-- Item 4: Privasi & Keamanan (Modal Informasi Keamanan & Enkripsi) -->
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

      <!-- Item 5: Bantuan & Tentang Majelisku (Kirim Pesan Langsung ke Email Owner) -->
      @php
        $ownerEmail = 'rizkidwisandy@gmail.com';
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

  <!-- 5. Session Actions (Masuk / Keluar) -->
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
      <p class="text-xs font-mono font-bold text-[#1C2224] break-all select-all">
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
      }
    }
    const radiusText = document.getElementById('userProfileLocationRadius');
    if (radiusText) {
      const radius = localStorage.getItem('sk_search_radius') || '5 km';
      radiusText.innerText = `${geo.name || 'Lokasi GPS'} • ${radius}`;
    }
  }

  // Dengarkan event pembaruan lokasi dari Geo-Engine
  window.addEventListener('sk:location-updated', function(e) {
    if (e.detail) {
      updateProfileLocationDisplay(e.detail);
    }
  });

  // Sinkronisasi otomatis dari localStorage saat halaman siap
  document.addEventListener('DOMContentLoaded', function() {
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

    // 3. Sinkronisasi hitungan metrik dari localStorage
    try {
      const localBookmarks = JSON.parse(localStorage.getItem('sinikajian_bookmarks') || '[]');
      const localFollows = JSON.parse(localStorage.getItem('sinikajian_followed_majelis') || '[]');
      const savedEl = document.getElementById('profileSavedCount');
      const followedEl = document.getElementById('profileFollowedCount');
      if (savedEl && localBookmarks.length > Number(savedEl.innerText || 0)) {
        savedEl.innerText = localBookmarks.length;
      }
      if (followedEl && localFollows.length > Number(followedEl.innerText || 0)) {
        followedEl.innerText = localFollows.length;
      }
    } catch(e) {}
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
  window.addEventListener('click', function(e) {
    const helpModal = document.getElementById('helpModal');
    const privacyModal = document.getElementById('privacyModal');
    const locationModal = document.getElementById('locationModal');
    if (e.target === helpModal) closeHelpModal();
    if (e.target === privacyModal) closePrivacyModal();
    if (e.target === locationModal) closeLocationModal();
  });
</script>
@endsection
