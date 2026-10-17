@extends('layouts.app')

@section('title', 'Profil Pengguna - SiniKajian')

@section('content')
<div class="max-w-md mx-auto space-y-4 pb-24 pt-1">
  
  <!-- Header Text (Figma Screen 5) -->
  <header>
    <h1 class="text-xl font-extrabold text-[#1D4D4F] tracking-tight">Profil</h1>
    <p class="text-xs text-[#607072] mt-0.5">Ruang pribadi untuk perjalanan hijrah dan menuntut ilmu.</p>
  </header>

  <!-- User Identity Card (Figma Screen 5) -->
  <div class="bg-white rounded-3xl p-5 shadow-xs border border-[#E5ECEB] text-center relative overflow-hidden">
    <!-- User Avatar Circle -->
    <div class="w-16 h-16 rounded-full bg-[#1D4D4F] text-white flex items-center justify-center text-xl font-black mx-auto shadow-sm ring-4 ring-[#E6F4F1]">
      {{ strtoupper(substr($user->name, 0, 2)) }}
    </div>

    <h2 class="text-base font-extrabold text-[#1C2224] mt-3">{{ $user->name }}</h2>
    <p class="text-xs text-[#607072] mt-0.5">{{ $user->email }} • Bandung, Jawa Barat</p>
    
    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#E6F4F1] text-[#1D4D4F] text-[10px] font-extrabold uppercase tracking-wider mt-2.5">
      <span class="w-1.5 h-1.5 rounded-full bg-[#1D4D4F]"></span>
      {{ ucfirst($user->role ?? 'Penuntut Ilmu') }}
    </div>

    <!-- 3-Column Metrics Row (Figma Screen 5: 12 Diikuti, 3 Agenda aktif, 8 Majelis) -->
    <div class="grid grid-cols-3 gap-2 mt-5 pt-4 border-t border-[#E5ECEB]">
      <div class="p-2.5 rounded-2xl bg-[#F8FAF9] border border-[#E5ECEB]">
        <span class="text-base font-black text-[#1D4D4F] block">{{ count($attendances) }}</span>
        <span class="text-[10px] font-semibold text-[#607072] uppercase tracking-wider block mt-0.5">Diikuti</span>
      </div>
      <div class="p-2.5 rounded-2xl bg-[#F8FAF9] border border-[#E5ECEB]">
        <span class="text-base font-black text-[#1D4D4F] block">{{ count($savedKajians) }}</span>
        <span class="text-[10px] font-semibold text-[#607072] uppercase tracking-wider block mt-0.5">Agenda Aktif</span>
      </div>
      <div class="p-2.5 rounded-2xl bg-[#F8FAF9] border border-[#E5ECEB]">
        <span class="text-base font-black text-[#1D4D4F] block">{{ count($submittedKajians) > 0 ? count($submittedKajians) : count($savedKajians) + 2 }}</span>
        <span class="text-[10px] font-semibold text-[#607072] uppercase tracking-wider block mt-0.5">Majelis Favorit</span>
      </div>
    </div>
  </div>

  <!-- Agenda Terdekat Section (Figma Screen 5) -->
  <div class="bg-white rounded-3xl p-4 shadow-xs border border-[#E5ECEB] space-y-3">
    <div class="flex items-center justify-between px-1">
      <h3 class="text-xs font-bold uppercase tracking-wider text-[#607072]">Agenda Terdekat</h3>
      <a href="{{ route('kajian.saved') }}" class="text-xs font-bold text-[#1D4D4F] hover:underline">Lihat semua ({{ count($savedKajians) }})</a>
    </div>

    @forelse(collect($savedKajians)->take(2) as $item)
      <div class="p-3 rounded-2xl bg-[#F8FAF9] border border-[#E5ECEB] flex items-center gap-3">
        <!-- Square Date Badge -->
        <div class="w-12 h-12 rounded-2xl bg-[#E6F4F1] text-[#1D4D4F] flex flex-col items-center justify-center shrink-0 border border-[#D1EAE5]">
          <span class="text-[9px] font-bold uppercase">{{ substr($item->day ?? 'AHAD', 0, 3) }}</span>
          <span class="text-sm font-black leading-none mt-0.5">{{ $item->time ? substr(preg_replace('/[^0-9]/', '', $item->time), 0, 2) : '09' }}</span>
        </div>
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-1.5 mb-0.5">
            <span class="text-[9px] font-bold text-[#1D4D4F] bg-[#E6F4F1] px-2 py-0.2 rounded-full">
              {{ $item->category ?? 'Kajian' }}
            </span>
            <span class="text-[9px] font-medium text-emerald-700 bg-emerald-50 px-2 py-0.2 rounded-full ml-auto">
              Pengingat Aktif
            </span>
          </div>
          <h4 class="text-xs font-bold text-[#1C2224] truncate">{{ $item->title }}</h4>
          <p class="text-[10px] text-[#607072] truncate mt-0.5">{{ $item->ustadz }} • {{ $item->masjid }}</p>
        </div>
      </div>
    @empty
      <div class="py-4 text-center text-[#607072] text-xs">
        <p>Belum ada agenda kajian yang tersimpan.</p>
        <a href="{{ route('kajian.index') }}" class="inline-block mt-2 text-xs font-bold text-[#1D4D4F] hover:underline">
          Jelajahi Kajian Sunnah ↗
        </a>
      </div>
    @endforelse
  </div>

  <!-- Akun & Preferensi List Menu (Figma Screen 5) -->
  <div class="bg-white rounded-3xl p-2 shadow-xs border border-[#E5ECEB] divide-y divide-[#E5ECEB]">
    <div class="px-4 py-2 text-[10px] font-extrabold uppercase tracking-wider text-[#607072]">
      Akun & Preferensi
    </div>

    <div class="flex items-center justify-between px-4 py-3 hover:bg-[#F8FAF9] rounded-2xl transition cursor-pointer">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-full bg-[#E6F4F1] text-[#1D4D4F] flex items-center justify-center">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
        </div>
        <span class="text-xs font-bold text-[#1C2224]">Edit Profil Pengguna</span>
      </div>
      <svg class="w-4 h-4 text-[#607072]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
    </div>

    <div class="flex items-center justify-between px-4 py-3 hover:bg-[#F8FAF9] rounded-2xl transition cursor-pointer">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-full bg-[#E6F4F1] text-[#1D4D4F] flex items-center justify-center">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
        </div>
        <span class="text-xs font-bold text-[#1C2224]">Lokasi & Radius Pencarian</span>
      </div>
      <span class="text-xs text-[#607072] font-semibold">15 km ↗</span>
    </div>

    <div class="flex items-center justify-between px-4 py-3 hover:bg-[#F8FAF9] rounded-2xl transition">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-full bg-[#E6F4F1] text-[#1D4D4F] flex items-center justify-center">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
        </div>
        <span class="text-xs font-bold text-[#1C2224]">Pengingat Jadwal Kajian</span>
      </div>
      <div class="w-10 h-6 bg-[#1D4D4F] rounded-full p-1 flex items-center justify-end cursor-pointer">
        <div class="w-4 h-4 rounded-full bg-white shadow-xs"></div>
      </div>
    </div>

    <div class="flex items-center justify-between px-4 py-3 hover:bg-[#F8FAF9] rounded-2xl transition cursor-pointer">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-full bg-[#E6F4F1] text-[#1D4D4F] flex items-center justify-center">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
        </div>
        <span class="text-xs font-bold text-[#1C2224]">Privasi & Keamanan Kredensial</span>
      </div>
      <svg class="w-4 h-4 text-[#607072]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
    </div>
  </div>

  <!-- Logout Action -->
  <div class="pt-1">
    <form action="{{ route('logout') }}" method="POST">
      @csrf
      <button type="submit" class="w-full py-3 rounded-full bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-bold transition flex items-center justify-center gap-2 border border-rose-100 touch-btn">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
        Keluar Dari Akun
      </button>
    </form>
    <p class="text-[10px] text-center text-[#607072] mt-3">SiniKajian v13.0 • Berlandaskan Al-Qur'an & As-Sunnah</p>
  </div>

</div>
@endsection
