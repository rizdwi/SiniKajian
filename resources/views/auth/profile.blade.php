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
    @if(!empty($user->avatar))
      <img src="{{ $user->avatar }}" alt="{{ $user->name }}" class="w-14 h-14 rounded-full object-cover mx-auto shadow-sm ring-4 ring-white/60">
    @else
      <div class="w-14 h-14 rounded-full bg-[#1D4D4F] text-white flex items-center justify-center font-extrabold text-base mx-auto shadow-sm ring-4 ring-white/60">
        {{ strtoupper(substr($user->name ?? 'Ahmad Fauzan', 0, 2)) }}
      </div>
    @endif

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
      <span>Bandung, Jawa Barat</span>
    </div>

    <hr class="border-[#D1ECE7] my-4">

    <!-- 3-Column Metrics Row: Disimpan, Agenda saya, Majelis diikuti (Figma Screen 5 Dinamis) -->
    <div class="grid grid-cols-3 divide-x divide-[#D1ECE7] text-center">
      <div class="px-2">
        <span class="text-lg font-black text-[#1D4D4F] block leading-tight">{{ $savedCount ?? 0 }}</span>
        <span class="text-[11px] font-medium text-[#607072] block mt-0.5">Disimpan</span>
      </div>
      <div class="px-2">
        <span class="text-lg font-black text-[#1D4D4F] block leading-tight">{{ $agendaCount ?? 0 }}</span>
        <span class="text-[11px] font-medium text-[#607072] block mt-0.5">Agenda saya</span>
      </div>
      <div class="px-2">
        <span class="text-lg font-black text-[#1D4D4F] block leading-tight">{{ $majelisCount ?? 0 }}</span>
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
            <span class="text-[11px] text-[#607072] block truncate">Dago, Bandung • 3 km</span>
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

      <!-- Item 4: Privasi & Keamanan -->
      <a href="{{ route('login') }}" class="flex items-center justify-between p-3.5 hover:bg-[#F8FAF9] transition">
        <div class="flex items-center gap-3 min-w-0">
          <div class="w-9 h-9 rounded-xl bg-[#EDF5F3] text-[#1D4D4F] flex items-center justify-center shrink-0 border border-[#D1ECE7]">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
            </svg>
          </div>
          <div class="min-w-0">
            <span class="text-xs sm:text-sm font-bold text-[#1C2224] block">Privasi & keamanan</span>
          </div>
        </div>
        <svg class="w-4 h-4 text-[#607072] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
        </svg>
      </a>

      <!-- Item 5: Bantuan & Tentang Majelisku -->
      <a href="{{ route('kajian.index') }}" class="flex items-center justify-between p-3.5 hover:bg-[#F8FAF9] transition">
        <div class="flex items-center gap-3 min-w-0">
          <div class="w-9 h-9 rounded-xl bg-[#EDF5F3] text-[#1D4D4F] flex items-center justify-center shrink-0 border border-[#D1ECE7]">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
          </div>
          <div class="min-w-0">
            <span class="text-xs sm:text-sm font-bold text-[#1C2224] block">Bantuan & tentang Majelisku</span>
          </div>
        </div>
        <svg class="w-4 h-4 text-[#607072] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
        </svg>
      </a>

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
@endsection
