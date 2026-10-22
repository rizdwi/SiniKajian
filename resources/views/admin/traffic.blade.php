@extends('layouts.app')

@section('title', 'Traffic & Pengguna - Panel Admin')

@section('content')
<div class="max-w-4xl mx-auto space-y-3 pb-20 pt-2 px-2 sm:px-0">

  <!-- Header Bar (compact) -->
  <div class="bg-white rounded-2xl px-3.5 py-3 border border-[#E5ECEB] shadow-xs">
    <div class="flex items-center gap-2.5">
      <div class="w-8 h-8 rounded-xl bg-[#E6F4F1] text-[#1D4D4F] flex items-center justify-center shrink-0">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
      </div>
      <div class="min-w-0 flex-1">
        <h1 class="text-sm font-extrabold text-[#1D4D4F] whitespace-nowrap">Traffic &amp; Pengguna</h1>
        <p class="text-[11px] text-[#607072] truncate">Kunjungan, pertumbuhan pengguna &amp; keterlibatan jamaah</p>
      </div>
    </div>
    <div class="flex items-center gap-1.5 mt-2.5">
      <a href="{{ route('admin.kajian.index') }}" class="px-2.5 py-1.5 rounded-full border border-[#E5ECEB] text-[11px] font-bold text-[#607072] hover:bg-[#F8FAF9] transition">Moderasi</a>
      <a href="{{ route('admin.akun.index') }}" class="px-2.5 py-1.5 rounded-full border border-[#E5ECEB] text-[11px] font-bold text-[#607072] hover:bg-[#F8FAF9] transition">Akun Pantau</a>
      <a href="{{ route('admin.feedback') }}" class="px-2.5 py-1.5 rounded-full border border-[#E5ECEB] text-[11px] font-bold text-[#607072] hover:bg-[#F8FAF9] transition">Feedback</a>
      <a href="{{ route('profile') }}" class="px-2.5 py-1.5 rounded-full border border-[#E5ECEB] text-[11px] font-bold text-[#607072] hover:bg-[#F8FAF9] transition">&larr; Ke Profil</a>
    </div>
  </div>

  @php $fmt = fn($v) => $v === null ? '—' : number_format($v); @endphp

  <!-- Kartu Statistik Utama (compact) -->
  <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
    <div class="bg-white rounded-2xl p-3 border border-[#E5ECEB] shadow-xs">
      <p class="text-[10px] font-bold text-[#607072] uppercase tracking-wide">Total Pengguna</p>
      <p class="text-xl font-extrabold text-[#1C2224] leading-tight">{{ $fmt($stats['users_total']) }}</p>
      <p class="text-[10px] text-[#607072] leading-snug">+{{ $fmt($stats['users_today']) }} hr ini · +{{ $fmt($stats['users_7d']) }} 7hr · +{{ $fmt($stats['users_30d']) }} 30hr</p>
    </div>
    <div class="bg-white rounded-2xl p-3 border border-[#E5ECEB] shadow-xs">
      <p class="text-[10px] font-bold text-[#607072] uppercase tracking-wide">Kunjungan Halaman</p>
      @if($traffic)
        <p class="text-xl font-extrabold text-[#1C2224] leading-tight">{{ $fmt($traffic['today']) }}</p>
        <p class="text-[10px] text-[#607072] leading-snug">hari ini · {{ $fmt($traffic['7d']) }} 7hr · {{ $fmt($traffic['30d']) }} 30hr</p>
      @else
        <p class="text-xl font-extrabold text-[#1C2224] leading-tight">—</p>
        <p class="text-[10px] text-amber-700 leading-snug">Pencatatan belum aktif (lihat bawah)</p>
      @endif
    </div>
    <div class="bg-white rounded-2xl p-3 border border-[#E5ECEB] shadow-xs">
      <p class="text-[10px] font-bold text-[#607072] uppercase tracking-wide">Kajian Aktif</p>
      <p class="text-xl font-extrabold text-[#1C2224] leading-tight">{{ $fmt($stats['kajian_active']) }}</p>
      <p class="text-[10px] text-[#607072] leading-snug">{{ $fmt($stats['kajian_cancelled']) }} tercatat dibatalkan</p>
    </div>
    <div class="bg-white rounded-2xl p-3 border border-[#E5ECEB] shadow-xs">
      <p class="text-[10px] font-bold text-[#607072] uppercase tracking-wide">Langganan Push</p>
      <p class="text-xl font-extrabold text-[#1C2224] leading-tight">{{ $fmt($stats['push_subs']) }}</p>
      <p class="text-[10px] text-[#607072] leading-snug">perangkat menerima notifikasi</p>
    </div>
    <div class="bg-white rounded-2xl p-3 border border-[#E5ECEB] shadow-xs">
      <p class="text-[10px] font-bold text-[#607072] uppercase tracking-wide">Bookmark</p>
      <p class="text-xl font-extrabold text-[#1C2224] leading-tight">{{ $fmt($stats['bookmarks']) }}</p>
      <p class="text-[10px] text-[#607072] leading-snug">kajian disimpan pengguna</p>
    </div>
    <div class="bg-white rounded-2xl p-3 border border-[#E5ECEB] shadow-xs">
      <p class="text-[10px] font-bold text-[#607072] uppercase tracking-wide">Kehadiran</p>
      <p class="text-xl font-extrabold text-[#1C2224] leading-tight">{{ $fmt($stats['attendances']) }}</p>
      <p class="text-[10px] text-[#607072] leading-snug">catatan hadir jamaah</p>
    </div>
    <div class="bg-white rounded-2xl p-3 border border-[#E5ECEB] shadow-xs">
      <p class="text-[10px] font-bold text-[#607072] uppercase tracking-wide">Pengikut Majelis</p>
      <p class="text-xl font-extrabold text-[#1C2224] leading-tight">{{ $fmt($stats['follows']) }}</p>
      <p class="text-[10px] text-[#607072] leading-snug">relasi ikuti majelis/masjid</p>
    </div>
    <div class="bg-white rounded-2xl p-3 border border-[#E5ECEB] shadow-xs">
      <p class="text-[10px] font-bold text-[#607072] uppercase tracking-wide">Pengguna Baru</p>
      <p class="text-xl font-extrabold text-[#1C2224] leading-tight">{{ $fmt($stats['users_7d']) }}</p>
      <p class="text-[10px] text-[#607072] leading-snug">dalam 7 hari terakhir</p>
    </div>
  </div>

  @if($traffic)
    <!-- Grafik Kunjungan 14 Hari (compact) -->
    <div class="bg-white rounded-2xl p-3.5 border border-[#E5ECEB] shadow-xs space-y-2">
      <h2 class="text-xs font-extrabold text-[#1C2224]">Kunjungan 14 Hari Terakhir</h2>
      @php $maxDay = max(1, max($traffic['per_day'])); @endphp
      <div class="flex items-end gap-1 h-24">
        @foreach($traffic['per_day'] as $day => $count)
          <div class="flex-1 flex flex-col items-center gap-0.5 min-w-0">
            <span class="text-[9px] font-bold text-[#607072]">{{ $count }}</span>
            <div class="w-full rounded-t bg-[#1D4D4F]" style="height: {{ max(3, round($count / $maxDay * 56)) }}px"></div>
            <span class="text-[8px] text-[#607072] truncate w-full text-center">{{ \Carbon\Carbon::parse($day)->format('d/m') }}</span>
          </div>
        @endforeach
      </div>
    </div>

    <!-- Halaman Terpopuler (compact) -->
    <div class="bg-white rounded-2xl p-3.5 border border-[#E5ECEB] shadow-xs space-y-1">
      <h2 class="text-xs font-extrabold text-[#1C2224] pb-1">Halaman Terpopuler (7 hari)</h2>
      @forelse($traffic['top_pages'] as $path => $count)
        <div class="flex items-center justify-between text-[11px] py-1 border-b border-[#F1F5F4] last:border-0">
          <span class="font-bold text-[#1C2224] truncate">{{ $path === '/' ? '/ (beranda)' : $path }}</span>
          <span class="px-1.5 py-0.5 rounded-full bg-[#E6F4F1] text-[#1D4D4F] text-[10px] font-bold shrink-0">{{ number_format($count) }}×</span>
        </div>
      @empty
        <p class="text-[11px] text-[#607072]">Belum ada kunjungan tercatat pada periode ini.</p>
      @endforelse
    </div>
  @else
    <!-- SQL untuk mengaktifkan pencatatan traffic -->
    <div class="bg-amber-50 rounded-2xl p-3.5 border border-amber-200 space-y-2">
      <h2 class="text-xs font-extrabold text-amber-900">Pencatatan traffic belum aktif</h2>
      <p class="text-[11px] text-amber-800">Jalankan SQL berikut di Supabase SQL Editor (proyek MajelisKu), lalu muat ulang halaman ini:</p>
      <pre class="text-[10px] bg-white border border-amber-200 rounded-xl p-2.5 overflow-x-auto text-[#1C2224]">CREATE TABLE IF NOT EXISTS traffic_events (
    id BIGSERIAL PRIMARY KEY,
    path TEXT NOT NULL,
    created_at TIMESTAMPTZ DEFAULT now()
);
CREATE INDEX IF NOT EXISTS idx_traffic_events_created
    ON traffic_events (created_at);</pre>
    </div>
  @endif

  <!-- Masjid dengan Kajian Terbanyak (compact) -->
  <div class="bg-white rounded-2xl p-3.5 border border-[#E5ECEB] shadow-xs space-y-1">
    <h2 class="text-xs font-extrabold text-[#1C2224] pb-1">Masjid Paling Aktif</h2>
    @forelse($topMasjid as $masjid => $count)
      <div class="flex items-center justify-between text-[11px] py-1 border-b border-[#F1F5F4] last:border-0">
        <span class="font-bold text-[#1C2224] truncate">{{ $masjid }}</span>
        <span class="px-1.5 py-0.5 rounded-full bg-[#E6F4F1] text-[#1D4D4F] text-[10px] font-bold shrink-0">{{ $count }} kajian</span>
      </div>
    @empty
      <p class="text-[11px] text-[#607072]">Belum ada data kajian aktif.</p>
    @endforelse
  </div>

  <!-- Pengguna Terbaru (compact) -->
  <div class="bg-white rounded-2xl p-3.5 border border-[#E5ECEB] shadow-xs space-y-1">
    <h2 class="text-xs font-extrabold text-[#1C2224] pb-1">Pengguna Terbaru</h2>
    @forelse($recentUsers as $u)
      <div class="flex items-center justify-between text-[11px] py-1 border-b border-[#F1F5F4] last:border-0 gap-3">
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
      <p class="text-[11px] text-[#607072]">Belum ada data pengguna.</p>
    @endforelse
  </div>

  <!-- Aktivitas Kunjungan Pengguna -->
  <div class="bg-white rounded-2xl p-3.5 border border-[#E5ECEB] shadow-xs space-y-1">
    <h2 class="text-xs font-extrabold text-[#1C2224] pb-1">Aktivitas Kunjungan Pengguna</h2>
    @if($userActivity === null)
      <p class="text-[11px] text-[#607072]">Data pengguna belum tersedia.</p>
    @else
      @forelse($userActivity as $a)
        <div class="flex items-center justify-between text-[11px] py-1 border-b border-[#F1F5F4] last:border-0 gap-3">
          <div class="min-w-0">
            <p class="font-bold text-[#1C2224] truncate">{{ $a['name'] !== '' ? $a['name'] : '—' }}</p>
            <p class="text-[#607072] truncate">{{ $a['email'] }}</p>
            <p class="text-[#607072] truncate">Login terakhir: {{ !empty($a['last_login_at']) ? \Carbon\Carbon::parse($a['last_login_at'])->setTimezone('Asia/Jakarta')->format('d M Y H:i') . ' WIB' : '—' }}</p>
          </div>
          <div class="text-right shrink-0">
            <p class="text-[#1C2224] font-bold">{{ !empty($a['last_seen_at']) ? \Carbon\Carbon::parse($a['last_seen_at'])->setTimezone('Asia/Jakarta')->format('d M H:i') : '—' }}</p>
            <p class="text-[#607072]">{{ $a['tracked'] && $a['hits7'] !== null ? $a['hits7'] . ' kunjung 7hr · ' . $a['hits30'] . ' /30hr' : 'belum terlacak' }}</p>
            <p class="text-[#607072] truncate">{{ $a['last_path'] ? 'terakhir: ' . $a['last_path'] : '' }}</p>
          </div>
        </div>
      @empty
        <p class="text-[11px] text-[#607072]">Belum ada pengguna terdaftar.</p>
      @endforelse
      <p class="text-[10px] text-[#607072] pt-1">Kunjungan dihitung dari pageview user yang sedang login (mulai tercatat sejak fitur ini aktif).</p>
    @endif
  </div>

</div>
@endsection
