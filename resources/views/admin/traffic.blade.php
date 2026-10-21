@extends('layouts.app')

@section('title', 'Traffic & Pengguna - Panel Admin')

@section('content')
<div class="max-w-4xl mx-auto space-y-6 pb-24 pt-2 px-2 sm:px-0">

  <!-- Header Bar -->
  <div class="flex items-center justify-between bg-white rounded-3xl p-5 border border-[#E5ECEB] shadow-xs">
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 rounded-2xl bg-[#E6F4F1] text-[#1D4D4F] flex items-center justify-center font-bold">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
      </div>
      <div>
        <h1 class="text-base font-extrabold text-[#1D4D4F]">Traffic &amp; Pengguna</h1>
        <p class="text-xs text-[#607072]">Pantau kunjungan, pertumbuhan pengguna, dan keterlibatan jamaah.</p>
      </div>
    </div>
    <div class="flex items-center gap-2">
      <a href="{{ route('admin.kajian.index') }}" class="px-3.5 py-2 rounded-full border border-[#E5ECEB] text-xs font-bold text-[#607072] hover:bg-[#F8FAF9] transition">Moderasi</a>
      <a href="{{ route('admin.akun.index') }}" class="px-3.5 py-2 rounded-full border border-[#E5ECEB] text-xs font-bold text-[#607072] hover:bg-[#F8FAF9] transition">Akun Pantau</a>
      <a href="{{ route('profile') }}" class="px-3.5 py-2 rounded-full border border-[#E5ECEB] text-xs font-bold text-[#607072] hover:bg-[#F8FAF9] transition">&larr; Ke Profil</a>
    </div>
  </div>

  @php $fmt = fn($v) => $v === null ? '—' : number_format($v); @endphp

  <!-- Kartu Statistik Utama -->
  <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
    <div class="bg-white rounded-3xl p-4 border border-[#E5ECEB] shadow-xs">
      <p class="text-[11px] font-bold text-[#607072] uppercase tracking-wide">Total Pengguna</p>
      <p class="text-2xl font-extrabold text-[#1C2224] mt-1">{{ $fmt($stats['users_total']) }}</p>
      <p class="text-[11px] text-[#607072] mt-1">+{{ $fmt($stats['users_today']) }} hari ini · +{{ $fmt($stats['users_7d']) }} 7 hari · +{{ $fmt($stats['users_30d']) }} 30 hari</p>
    </div>
    <div class="bg-white rounded-3xl p-4 border border-[#E5ECEB] shadow-xs">
      <p class="text-[11px] font-bold text-[#607072] uppercase tracking-wide">Kunjungan Halaman</p>
      @if($traffic)
        <p class="text-2xl font-extrabold text-[#1C2224] mt-1">{{ $fmt($traffic['today']) }}</p>
        <p class="text-[11px] text-[#607072] mt-1">hari ini · {{ $fmt($traffic['7d']) }} 7 hari · {{ $fmt($traffic['30d']) }} 30 hari</p>
      @else
        <p class="text-2xl font-extrabold text-[#1C2224] mt-1">—</p>
        <p class="text-[11px] text-amber-700 mt-1">Pencatatan belum aktif (lihat bawah)</p>
      @endif
    </div>
    <div class="bg-white rounded-3xl p-4 border border-[#E5ECEB] shadow-xs">
      <p class="text-[11px] font-bold text-[#607072] uppercase tracking-wide">Kajian Aktif</p>
      <p class="text-2xl font-extrabold text-[#1C2224] mt-1">{{ $fmt($stats['kajian_active']) }}</p>
      <p class="text-[11px] text-[#607072] mt-1">{{ $fmt($stats['kajian_cancelled']) }} tercatat dibatalkan</p>
    </div>
    <div class="bg-white rounded-3xl p-4 border border-[#E5ECEB] shadow-xs">
      <p class="text-[11px] font-bold text-[#607072] uppercase tracking-wide">Langganan Push</p>
      <p class="text-2xl font-extrabold text-[#1C2224] mt-1">{{ $fmt($stats['push_subs']) }}</p>
      <p class="text-[11px] text-[#607072] mt-1">perangkat menerima notifikasi</p>
    </div>
    <div class="bg-white rounded-3xl p-4 border border-[#E5ECEB] shadow-xs">
      <p class="text-[11px] font-bold text-[#607072] uppercase tracking-wide">Bookmark</p>
      <p class="text-2xl font-extrabold text-[#1C2224] mt-1">{{ $fmt($stats['bookmarks']) }}</p>
      <p class="text-[11px] text-[#607072] mt-1">kajian disimpan pengguna</p>
    </div>
    <div class="bg-white rounded-3xl p-4 border border-[#E5ECEB] shadow-xs">
      <p class="text-[11px] font-bold text-[#607072] uppercase tracking-wide">Kehadiran</p>
      <p class="text-2xl font-extrabold text-[#1C2224] mt-1">{{ $fmt($stats['attendances']) }}</p>
      <p class="text-[11px] text-[#607072] mt-1">catatan hadir jamaah</p>
    </div>
    <div class="bg-white rounded-3xl p-4 border border-[#E5ECEB] shadow-xs">
      <p class="text-[11px] font-bold text-[#607072] uppercase tracking-wide">Pengikut Majelis</p>
      <p class="text-2xl font-extrabold text-[#1C2224] mt-1">{{ $fmt($stats['follows']) }}</p>
      <p class="text-[11px] text-[#607072] mt-1">relasi ikuti majelis/masjid</p>
    </div>
    <div class="bg-white rounded-3xl p-4 border border-[#E5ECEB] shadow-xs">
      <p class="text-[11px] font-bold text-[#607072] uppercase tracking-wide">Pengguna Baru</p>
      <p class="text-2xl font-extrabold text-[#1C2224] mt-1">{{ $fmt($stats['users_7d']) }}</p>
      <p class="text-[11px] text-[#607072] mt-1">dalam 7 hari terakhir</p>
    </div>
  </div>

  @if($traffic)
    <!-- Grafik Kunjungan 14 Hari -->
    <div class="bg-white rounded-3xl p-5 border border-[#E5ECEB] shadow-xs space-y-4">
      <h2 class="text-sm font-extrabold text-[#1C2224]">Kunjungan 14 Hari Terakhir</h2>
      @php $maxDay = max(1, max($traffic['per_day'])); @endphp
      <div class="flex items-end gap-1.5 h-32">
        @foreach($traffic['per_day'] as $day => $count)
          <div class="flex-1 flex flex-col items-center gap-1 min-w-0">
            <span class="text-[10px] font-bold text-[#607072]">{{ $count }}</span>
            <div class="w-full rounded-t-lg bg-[#1D4D4F]" style="height: {{ max(4, round($count / $maxDay * 88)) }}px"></div>
            <span class="text-[9px] text-[#607072] truncate w-full text-center">{{ \Carbon\Carbon::parse($day)->format('d/m') }}</span>
          </div>
        @endforeach
      </div>
    </div>

    <!-- Halaman Terpopuler -->
    <div class="bg-white rounded-3xl p-5 border border-[#E5ECEB] shadow-xs space-y-3">
      <h2 class="text-sm font-extrabold text-[#1C2224]">Halaman Terpopuler (7 hari)</h2>
      @forelse($traffic['top_pages'] as $path => $count)
        <div class="flex items-center justify-between text-xs py-1.5 border-b border-[#F1F5F4] last:border-0">
          <span class="font-bold text-[#1C2224] truncate">{{ $path === '/' ? '/ (beranda)' : $path }}</span>
          <span class="px-2 py-0.5 rounded-full bg-[#E6F4F1] text-[#1D4D4F] font-bold">{{ number_format($count) }}×</span>
        </div>
      @empty
        <p class="text-xs text-[#607072]">Belum ada kunjungan tercatat pada periode ini.</p>
      @endforelse
    </div>
  @else
    <!-- SQL untuk mengaktifkan pencatatan traffic -->
    <div class="bg-amber-50 rounded-3xl p-5 border border-amber-200 space-y-3">
      <h2 class="text-sm font-extrabold text-amber-900">Pencatatan traffic belum aktif</h2>
      <p class="text-xs text-amber-800">Jalankan SQL berikut di Supabase SQL Editor (proyek MajelisKu), lalu muat ulang halaman ini:</p>
      <pre class="text-[11px] bg-white border border-amber-200 rounded-2xl p-3 overflow-x-auto text-[#1C2224]">CREATE TABLE IF NOT EXISTS traffic_events (
    id BIGSERIAL PRIMARY KEY,
    path TEXT NOT NULL,
    created_at TIMESTAMPTZ DEFAULT now()
);
CREATE INDEX IF NOT EXISTS idx_traffic_events_created
    ON traffic_events (created_at);</pre>
    </div>
  @endif

  <!-- Masjid dengan Kajian Terbanyak -->
  <div class="bg-white rounded-3xl p-5 border border-[#E5ECEB] shadow-xs space-y-3">
    <h2 class="text-sm font-extrabold text-[#1C2224]">Masjid Paling Aktif</h2>
    @forelse($topMasjid as $masjid => $count)
      <div class="flex items-center justify-between text-xs py-1.5 border-b border-[#F1F5F4] last:border-0">
        <span class="font-bold text-[#1C2224] truncate">{{ $masjid }}</span>
        <span class="px-2 py-0.5 rounded-full bg-[#E6F4F1] text-[#1D4D4F] font-bold">{{ $count }} kajian</span>
      </div>
    @empty
      <p class="text-xs text-[#607072]">Belum ada data kajian aktif.</p>
    @endforelse
  </div>

  <!-- Pengguna Terbaru -->
  <div class="bg-white rounded-3xl p-5 border border-[#E5ECEB] shadow-xs space-y-3">
    <h2 class="text-sm font-extrabold text-[#1C2224]">Pengguna Terbaru</h2>
    @forelse($recentUsers as $u)
      <div class="flex items-center justify-between text-xs py-1.5 border-b border-[#F1F5F4] last:border-0 gap-3">
        <div class="min-w-0">
          <p class="font-bold text-[#1C2224] truncate">{{ $u['name'] ?? '—' }}</p>
          <p class="text-[#607072] truncate">{{ $u['email'] ?? '' }}</p>
        </div>
        <div class="text-right shrink-0">
          <p class="text-[#607072]">{{ !empty($u['provider']) ? ucfirst($u['provider']) : 'Email' }}</p>
          <p class="text-[#607072]">{{ !empty($u['created_at']) ? \Carbon\Carbon::parse($u['created_at'])->setTimezone('Asia/Jakarta')->format('d M Y') : '' }}</p>
        </div>
      </div>
    @empty
      <p class="text-xs text-[#607072]">Belum ada data pengguna.</p>
    @endforelse
  </div>

</div>
@endsection
