@extends('layouts.app')

@section('title', 'Akun Pantau Scraping - Panel Admin')

@section('content')
<div class="max-w-4xl mx-auto space-y-3 pb-20 pt-2 px-2 sm:px-0">

  <!-- Header Bar (compact) -->
  <div class="bg-white rounded-2xl px-3.5 py-3 border border-[#E5ECEB] shadow-xs">
    <div class="flex items-center gap-2.5">
      <div class="w-8 h-8 rounded-xl bg-[#E6F4F1] text-[#1D4D4F] flex items-center justify-center shrink-0">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
      </div>
      <div class="min-w-0 flex-1">
        <h1 class="text-sm font-extrabold text-[#1D4D4F] whitespace-nowrap">Akun Pantau Scraping</h1>
        <p class="text-[11px] text-[#607072] truncate">Akun sumber scraper + blacklist — perubahan langsung berlaku</p>
      </div>
    </div>
    <div class="flex items-center gap-1.5 mt-2.5">
      <a href="{{ route('admin.kajian.index') }}" class="px-2.5 py-1.5 rounded-full border border-[#E5ECEB] text-[11px] font-bold text-[#607072] hover:bg-[#F8FAF9] transition">Moderasi</a>
      <a href="{{ route('admin.traffic') }}" class="px-2.5 py-1.5 rounded-full border border-[#E5ECEB] text-[11px] font-bold text-[#607072] hover:bg-[#F8FAF9] transition">Traffic</a>
      <a href="{{ route('admin.feedback') }}" class="px-2.5 py-1.5 rounded-full border border-[#E5ECEB] text-[11px] font-bold text-[#607072] hover:bg-[#F8FAF9] transition">Feedback</a>
    </div>
  </div>

  @if(session('success'))
    <div class="p-3 rounded-xl bg-[#E6F4F1] border border-[#D1EAE5] text-[#1D4D4F] text-[11px] font-bold">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="p-3 rounded-xl bg-red-50 border border-red-200 text-red-800 text-[11px] font-bold">{{ session('error') }}</div>
  @endif

  @if(!$tableReady)
    <div class="bg-amber-50 rounded-2xl p-3.5 border border-amber-200 space-y-2">
      <h2 class="text-xs font-extrabold text-amber-900">Tabel akun pantau belum dibuat</h2>
      <p class="text-[11px] text-amber-800">Jalankan SQL berikut di Supabase SQL Editor (proyek MajelisKu), lalu muat ulang halaman ini:</p>
      <pre class="text-[10px] bg-white border border-amber-200 rounded-xl p-2.5 overflow-x-auto text-[#1C2224]">CREATE TABLE IF NOT EXISTS monitor_accounts (
    id BIGSERIAL PRIMARY KEY,
    list_type TEXT NOT NULL DEFAULT 'monitor',
    platform TEXT,
    username TEXT NOT NULL,
    url TEXT,
    region TEXT,
    note TEXT,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ DEFAULT now()
);
CREATE UNIQUE INDEX IF NOT EXISTS monitor_accounts_unique
    ON monitor_accounts (list_type, platform, username);</pre>
    </div>
  @else

  <!-- Form Tambah Akun (compact) -->
  <form method="POST" action="{{ route('admin.akun.store') }}" class="bg-white rounded-2xl p-3.5 border border-[#E5ECEB] shadow-xs space-y-2">
    @csrf
    <h2 class="text-xs font-extrabold text-[#1C2224]">Tambah Akun</h2>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5">
      <select name="list_type" class="text-[11px] border border-[#E5ECEB] rounded-lg px-2 py-2 bg-white">
        <option value="monitor">Dipantau</option>
        <option value="blacklist">Blacklist</option>
      </select>
      <select name="platform" class="text-[11px] border border-[#E5ECEB] rounded-lg px-2 py-2 bg-white">
        <option value="instagram">Instagram</option>
        <option value="facebook">Facebook</option>
        <option value="threads">Threads</option>
        <option value="">— (nama/pola)</option>
      </select>
      <input name="username" required placeholder="username / nama" class="text-[11px] border border-[#E5ECEB] rounded-lg px-2 py-2">
      <input name="region" placeholder="Wilayah (mis. Karawang)" class="text-[11px] border border-[#E5ECEB] rounded-lg px-2 py-2">
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
      <input name="url" placeholder="URL profil (kosong = dibuat otomatis)" class="text-[11px] border border-[#E5ECEB] rounded-lg px-2 py-2">
      <input name="note" placeholder="Catatan" class="text-[11px] border border-[#E5ECEB] rounded-lg px-2 py-2">
    </div>
    <button class="px-3.5 py-2 rounded-full bg-[#1D4D4F] text-white text-[11px] font-bold hover:opacity-90 transition">+ Tambah</button>
  </form>

  @php
    $badgeCls = ['instagram' => 'bg-pink-50 text-pink-700 border-pink-200', 'facebook' => 'bg-blue-50 text-blue-700 border-blue-200', 'threads' => 'bg-neutral-100 text-neutral-700 border-neutral-300'];
    $badgeShort = ['instagram' => 'IG', 'facebook' => 'FB', 'threads' => 'TH'];
  @endphp

  <!-- Daftar Akun Dipantau (baris dua tingkat, aman di HP) -->
  <div class="bg-white rounded-2xl p-3.5 border border-[#E5ECEB] shadow-xs space-y-1">
    <div class="flex items-center justify-between pb-1.5 border-b border-[#E5ECEB]">
      <h2 class="text-xs font-extrabold text-[#1C2224]">Akun Dipantau</h2>
      <span class="px-2 py-0.5 rounded-full bg-[#E6F4F1] text-[#1D4D4F] text-[10px] font-bold border border-[#D1EAE5]">{{ count($monitors) }} akun · {{ collect($monitors)->where('is_active', true)->count() }} aktif</span>
    </div>
    @forelse($monitors as $a)
      <div class="py-2 border-b border-[#F1F5F4] last:border-0 {{ empty($a['is_active']) ? 'opacity-50' : '' }}">
        <div class="flex items-center gap-2">
          <span class="px-1.5 py-0.5 rounded text-[9px] font-bold border {{ $badgeCls[$a['platform'] ?? ''] ?? 'bg-neutral-100 text-neutral-600 border-neutral-200' }} shrink-0">{{ $badgeShort[$a['platform'] ?? ''] ?? '—' }}</span>
          @if(!empty($a['url']))<a href="{{ $a['url'] }}" target="_blank" rel="noopener" class="text-xs font-bold text-[#1C2224] truncate flex-1 min-w-0 hover:underline">{{ '@' . $a['username'] }}</a>@else<span class="text-xs font-bold text-[#1C2224] truncate flex-1 min-w-0">{{ $a['username'] }}</span>@endif
          @php $n = $contrib[strtolower($a['username'])] ?? 0; @endphp
          @if($n >= 3)<span class="px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-800 border border-amber-300 text-[9px] font-bold shrink-0">🏅 Produktif · {{ $n }} kajian</span>@elseif($n >= 1)<span class="px-1.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[9px] font-bold shrink-0">✓ Aktif · {{ $n }} kajian</span>@endif
        </div>
        <div class="flex items-center gap-1.5 mt-1">
          <p class="flex-1 min-w-0 text-[10px] text-[#607072] truncate">{{ $a['region'] ?? '' }}{{ !empty($a['note']) ? ' · ' . $a['note'] : '' }}</p>
          <a href="{{ route('admin.akun.edit', $a['id']) }}" class="px-2 py-1 rounded-full border border-[#E5ECEB] text-[10px] font-bold text-[#607072] hover:bg-[#F8FAF9] transition shrink-0">Edit</a>
          <form method="POST" action="{{ route('admin.akun.toggle', $a['id']) }}" class="shrink-0">@csrf
            <button class="px-2 py-1 rounded-full border text-[10px] font-bold transition {{ !empty($a['is_active']) ? 'border-amber-200 bg-amber-50 text-amber-800' : 'border-[#D1EAE5] bg-[#E6F4F1] text-[#1D4D4F]' }}">{{ !empty($a['is_active']) ? 'Nonaktifkan' : 'Aktifkan' }}</button>
          </form>
          <form method="POST" action="{{ route('admin.akun.destroy', $a['id']) }}" onsubmit="return confirm('Hapus akun ini dari daftar pantau?')" class="shrink-0">@csrf @method('DELETE')
            <button class="px-2 py-1 rounded-full border border-red-200 bg-red-50 text-red-700 text-[10px] font-bold hover:bg-red-100 transition">Hapus</button>
          </form>
        </div>
      </div>
    @empty
      <p class="text-[11px] text-[#607072] py-3 text-center">Belum ada akun dipantau.</p>
    @endforelse
  </div>

  <!-- Daftar Blacklist (baris dua tingkat) -->
  <div class="bg-white rounded-2xl p-3.5 border border-[#E5ECEB] shadow-xs space-y-1">
    <div class="flex items-center justify-between pb-1.5 border-b border-[#E5ECEB]">
      <h2 class="text-xs font-extrabold text-[#1C2224]">Blacklist (Ditolak Permanen)</h2>
      <span class="px-2 py-0.5 rounded-full bg-red-50 text-red-700 text-[10px] font-bold border border-red-200">{{ count($blacklists) }} entri</span>
    </div>
    @forelse($blacklists as $a)
      <div class="py-2 border-b border-[#F1F5F4] last:border-0 {{ empty($a['is_active']) ? 'opacity-50' : '' }}">
        <div class="flex items-center gap-2">
          <span class="px-1.5 py-0.5 rounded text-[9px] font-bold border bg-red-50 text-red-700 border-red-200 shrink-0">{{ $badgeShort[$a['platform'] ?? ''] ?? 'NAMA' }}</span>
          <span class="text-xs font-bold text-[#1C2224] truncate flex-1 min-w-0">{{ $a['username'] }}</span>
        </div>
        <div class="flex items-center gap-1.5 mt-1">
          <p class="flex-1 min-w-0 text-[10px] text-[#607072] truncate">{{ !empty($a['note']) ? $a['note'] : '' }}</p>
          <a href="{{ route('admin.akun.edit', $a['id']) }}" class="px-2 py-1 rounded-full border border-[#E5ECEB] text-[10px] font-bold text-[#607072] hover:bg-[#F8FAF9] transition shrink-0">Edit</a>
          <form method="POST" action="{{ route('admin.akun.destroy', $a['id']) }}" onsubmit="return confirm('Hapus entri blacklist ini? Kajian dari sumber ini bisa masuk lagi.')" class="shrink-0">@csrf @method('DELETE')
            <button class="px-2 py-1 rounded-full border border-red-200 bg-red-50 text-red-700 text-[10px] font-bold hover:bg-red-100 transition">Hapus</button>
          </form>
        </div>
      </div>
    @empty
      <p class="text-[11px] text-[#607072] py-3 text-center">Blacklist kosong.</p>
    @endforelse
  </div>

  @endif
</div>
@endsection
