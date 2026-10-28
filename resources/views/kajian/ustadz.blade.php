@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-6 space-y-4">
  <!-- Header Ustadz -->
  <div class="rounded-3xl bg-[#1D4D4F] text-white p-5 shadow-md">
    <div class="flex items-center gap-4">
      <div class="w-14 h-14 rounded-full bg-amber-400 text-[#1D4D4F] flex items-center justify-center font-black text-xl shrink-0">
        {{ mb_strtoupper(mb_substr(preg_replace('/^Ustadz\s+/i', '', $ustadzName), 0, 1)) }}
      </div>
      <div class="min-w-0 flex-1">
        <h1 class="text-lg font-black truncate">{{ $ustadzName }}</h1>
        <p class="text-xs text-white/70">{{ $kajians->count() }} kajian mendatang @if($followerCount > 0)• {{ $followerCount }} pengikut @endif</p>
      </div>
      <button id="followUstadzBtn" onclick="toggleFollowUstadzPage()" class="px-4 py-2 rounded-full text-xs font-bold shrink-0 transition {{ $isFollowing ? 'bg-amber-400 text-[#1D4D4F]' : 'bg-white/15 text-white border border-white/40 hover:bg-white/25' }}">
        {{ $isFollowing ? '✓ Mengikuti' : '+ Ikuti' }}
      </button>
    </div>
    <p class="text-[11px] text-white/60 mt-3">Ikuti ustadz ini untuk mendapat notifikasi saat ada kajian barunya.</p>
  </div>

  <!-- Daftar Kajian -->
  <div class="space-y-2">
    @forelse($kajians as $kajian)
      @php
        $dt = $kajian->event_datetime ? \Carbon\Carbon::parse($kajian->event_datetime) : null;
      @endphp
      <div class="rounded-2xl bg-white border border-[#E5ECEB] shadow-2xs overflow-hidden">
        <a href="{{ route('kajian.show', $kajian->id) }}" class="block">
          @if(!empty($kajian->poster_url))
          <img src="{{ $kajian->poster_url }}" alt="Flyer" class="w-full h-36 object-cover" loading="lazy">
          @endif
          <div class="p-3">
            <div class="flex items-start gap-3">
              @if($dt)
              <div class="w-12 h-12 rounded-xl bg-[#EDF5F3] border border-[#D1ECE7] text-[#1D4D4F] flex flex-col items-center justify-center shrink-0">
                <span class="text-base font-black leading-none">{{ $dt->format('d') }}</span>
                <span class="text-[9px] font-extrabold uppercase tracking-wider mt-0.5">{{ $dt->translatedFormat('M') }}</span>
              </div>
              @endif
              <div class="flex-1 min-w-0">
                <h4 class="text-xs sm:text-sm font-bold text-[#1C2224]">{{ $kajian->title }}</h4>
                <p class="text-[11px] text-[#607072] mt-0.5 truncate">
                  {{ $kajian->time ?? '' }} • {{ $kajian->masjid ?? '' }}
                </p>
                @if(!empty($kajian->address))
                <p class="text-[10px] text-[#9BA8A9] truncate">{{ $kajian->address }}</p>
                @endif
              </div>
            </div>
          </div>
        </a>
      </div>
    @empty
      <div class="p-6 rounded-2xl bg-white border border-[#E5ECEB] text-center">
        <p class="text-sm text-[#607072]">Belum ada kajian mendatang dari {{ $ustadzName }}.</p>
        <a href="{{ route('kajian.index') }}" class="inline-block mt-3 px-4 py-2 rounded-xl bg-[#1D4D4F] text-white text-xs font-bold">Kembali ke Beranda</a>
      </div>
    @endforelse
  </div>
</div>

@endsection

@section('scripts')
<script>
  var USTADZ_PAGE_NAME = @json($ustadzName);
  var USTADZ_PAGE_FOLLOWING = {{ $isFollowing ? 'true' : 'false' }};
  async function toggleFollowUstadzPage() {
    var btn = document.getElementById('followUstadzBtn');
    var csrf = document.querySelector('meta[name="csrf-token"]');
    try {
      var res = await fetch('/ustadz/follow', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf ? csrf.getAttribute('content') : '' },
        body: JSON.stringify({ ustadz: USTADZ_PAGE_NAME, follow: !USTADZ_PAGE_FOLLOWING })
      });
      if (!res.ok) return;
      var d = await res.json();
      USTADZ_PAGE_FOLLOWING = !!d.followed;
      btn.textContent = d.followed ? '✓ Mengikuti' : '+ Ikuti';
      btn.className = 'px-4 py-2 rounded-full text-xs font-bold shrink-0 transition ' + (d.followed ? 'bg-amber-400 text-[#1D4D4F]' : 'bg-white/15 text-white border border-white/40 hover:bg-white/25');
      // Sinkronkan bintang favorit lokal (filter beranda) dengan follow server.
      try {
        var favs = JSON.parse(localStorage.getItem('majelisku_fav_ustadz') || '[]');
        if (d.followed && !favs.includes(USTADZ_PAGE_NAME)) favs.push(USTADZ_PAGE_NAME);
        if (!d.followed) favs = favs.filter(function (n) { return n !== USTADZ_PAGE_NAME; });
        localStorage.setItem('majelisku_fav_ustadz', JSON.stringify(favs));
      } catch (e) {}
    } catch (e) {}
  }
</script>
@endsection
