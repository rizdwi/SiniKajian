@extends('layouts.app')

@section('title', 'Saran & Masukan - Panel Admin')

@section('content')
<div class="max-w-4xl mx-auto space-y-3 pb-20 pt-2 px-2 sm:px-0">

  <!-- Header Bar (compact) -->
  <div class="bg-white dark:bg-[#16282A] rounded-2xl px-3.5 py-3 border border-[#E5ECEB] dark:border-[#1E2E2F] shadow-xs">
    <div class="flex items-center gap-2.5">
      <div class="w-8 h-8 rounded-xl bg-[#E6F4F1] text-[#1D4D4F] flex items-center justify-center shrink-0">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M21 12a8 8 0 01-8 8H5l-2 2V12a8 8 0 018-8h2a8 8 0 018 8z"></path></svg>
      </div>
      <div class="min-w-0 flex-1">
        <h1 class="text-sm font-extrabold text-[#1D4D4F] dark:text-[#E8EDED] whitespace-nowrap">Saran &amp; Masukan</h1>
        <p class="text-[11px] text-[#607072] truncate">Feedback pengguna dari box melayang di aplikasi</p>
      </div>
      @if($unread !== null && $unread > 0)
        <span class="shrink-0 px-2 py-1 rounded-full bg-rose-100 text-rose-700 text-[11px] font-extrabold">{{ $unread }} baru</span>
      @endif
    </div>
    <div class="flex items-center gap-1.5 mt-2.5 flex-wrap">
      <a href="{{ route('admin.kajian.index') }}" class="px-2.5 py-1.5 rounded-full border border-[#E5ECEB] text-[11px] font-bold text-[#607072] hover:bg-[#F8FAF9] transition">Moderasi</a>
      <a href="{{ route('admin.akun.index') }}" class="px-2.5 py-1.5 rounded-full border border-[#E5ECEB] text-[11px] font-bold text-[#607072] hover:bg-[#F8FAF9] transition">Akun Pantau</a>
      <a href="{{ route('admin.traffic') }}" class="px-2.5 py-1.5 rounded-full border border-[#E5ECEB] text-[11px] font-bold text-[#607072] hover:bg-[#F8FAF9] transition">Traffic</a>
      <a href="{{ route('admin.feedback') }}" class="px-2.5 py-1.5 rounded-full bg-[#1D4D4F] text-white text-[11px] font-bold transition">Feedback</a>
      <a href="{{ route('profile') }}" class="px-2.5 py-1.5 rounded-full border border-[#E5ECEB] text-[11px] font-bold text-[#607072] hover:bg-[#F8FAF9] transition">&larr; Ke Profil</a>
    </div>
  </div>

  @if(session('success'))
    <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-xs font-semibold rounded-xl px-3.5 py-2.5">{{ session('success') }}</div>
  @endif

  @if($rows === null)
    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-2xl px-3.5 py-3">
      <p class="text-xs font-bold text-amber-800 dark:text-amber-300">Tabel feedback belum tersedia.</p>
      <p class="text-[11px] text-amber-700 dark:text-amber-400 mt-0.5">Jalankan SQL <span class="font-mono">database/supabase_feedback.sql</span> di Supabase SQL Editor, lalu muat ulang halaman ini.</p>
    </div>
  @elseif(count($rows) === 0)
    <div class="bg-white dark:bg-[#16282A] rounded-2xl px-3.5 py-6 border border-[#E5ECEB] dark:border-[#1E2E2F] text-center">
      <p class="text-sm font-bold text-[#1C2224] dark:text-[#E8EDED]">Belum ada feedback masuk</p>
      <p class="text-[11px] text-[#607072] mt-0.5">Masukan pengguna dari box melayang akan muncul di sini.</p>
    </div>
  @else
    @php
      $badge = [
        'Saran Fitur' => 'bg-[#E6F4F1] text-[#1D4D4F]',
        'Laporan Bug' => 'bg-rose-100 text-rose-700',
        'Info Kajian' => 'bg-amber-100 text-amber-800',
        'Lainnya' => 'bg-gray-100 text-gray-600',
      ];
    @endphp
    <div class="space-y-2">
      @foreach($rows as $f)
        <div class="bg-white dark:bg-[#16282A] rounded-2xl px-3.5 py-3 border {{ ($f['is_read'] ?? false) ? 'border-[#E5ECEB] dark:border-[#1E2E2F]' : 'border-[#1D4D4F]/40 dark:border-[#FCD34D]/40' }} shadow-xs">
          <div class="flex items-center gap-1.5 flex-wrap">
            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold {{ $badge[$f['category'] ?? 'Lainnya'] ?? $badge['Lainnya'] }}">{{ $f['category'] ?? 'Lainnya' }}</span>
            @unless($f['is_read'] ?? false)
              <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
              <span class="text-[10px] font-bold text-rose-600">Baru</span>
            @endunless
            <span class="text-[10px] text-[#607072] ml-auto">{{ \Carbon\Carbon::parse($f['created_at'])->setTimezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB</span>
          </div>
          <p class="text-[13px] text-[#1C2224] dark:text-[#E8EDED] whitespace-pre-line mt-1.5 leading-relaxed">{{ $f['message'] }}</p>
          <div class="flex items-end gap-2 mt-2">
            <div class="min-w-0 flex-1 text-[11px] text-[#607072] leading-snug">
              @if(!empty($f['name']) || !empty($f['contact']))
                <span class="font-bold text-[#1C2224] dark:text-[#E8EDED]">{{ $f['name'] ?: 'Anonim' }}</span>
                @if(!empty($f['contact'])) · {{ $f['contact'] }} @endif
                <br>
              @endif
              @if(!empty($f['page'])) Dari halaman: <span class="font-mono">{{ $f['page'] }}</span> @endif
            </div>
            @unless($f['is_read'] ?? false)
              <form method="POST" action="{{ route('admin.feedback.read', $f['id']) }}" class="shrink-0">
                @csrf
                <button type="submit" class="px-2.5 py-1.5 rounded-full bg-[#E6F4F1] text-[#1D4D4F] text-[11px] font-bold hover:bg-[#d3ebe4] transition">Tandai dibaca</button>
              </form>
            @endunless
            <form method="POST" action="{{ route('admin.feedback.destroy', $f['id']) }}" class="shrink-0" onsubmit="return confirm('Hapus feedback ini?')">
              @csrf
              @method('DELETE')
              <button type="submit" class="px-2.5 py-1.5 rounded-full border border-rose-200 text-rose-600 text-[11px] font-bold hover:bg-rose-50 transition">Hapus</button>
            </form>
          </div>
        </div>
      @endforeach
    </div>
    <p class="text-[10px] text-[#607072] text-center">Menampilkan {{ count($rows) }} feedback terbaru.</p>
  @endif
</div>
@endsection
