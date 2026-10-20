@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-6 space-y-4">
  <!-- Header Ustadz -->
  <div class="rounded-3xl bg-[#1D4D4F] text-white p-5 shadow-md">
    <div class="flex items-center gap-4">
      <div class="w-14 h-14 rounded-full bg-amber-400 text-[#1D4D4F] flex items-center justify-center font-black text-xl shrink-0">
        {{ mb_strtoupper(mb_substr(preg_replace('/^Ustadz\s+/i', '', $ustadzName), 0, 1)) }}
      </div>
      <div class="min-w-0">
        <h1 class="text-lg font-black truncate">{{ $ustadzName }}</h1>
        <p class="text-xs text-white/70">{{ $kajians->count() }} kajian mendatang</p>
      </div>
    </div>
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
              <button type="button" onclick="event.preventDefault();event.stopPropagation();toggleUstadzBookmark({{ $kajian->id }}, this)" class="bm-btn w-8 h-8 rounded-full bg-[#F8FAF9] border border-[#E5ECEB] text-[#607072] flex items-center justify-center hover:border-[#1D4D4F]/40 transition shrink-0" data-kajian-id="{{ $kajian->id }}" aria-label="Simpan">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path></svg>
              </button>
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

<script>
(function() {
  // State bookmark dari localStorage (sinkron dengan beranda)
  let saved = new Set();
  try { saved = new Set(JSON.parse(localStorage.getItem('sinikajian_bookmarks') || '[]')); } catch(e) {}

  function paintBtn(btn, on) {
    const svg = btn.querySelector('svg');
    if (on) {
      btn.classList.add('text-[#1D4D4F]', 'border-[#1D4D4F]/40', 'bg-[#E6F4F1]');
      if (svg) { svg.setAttribute('fill', '#1D4D4F'); svg.setAttribute('stroke', '#1D4D4F'); }
    } else {
      btn.classList.remove('text-[#1D4D4F]', 'border-[#1D4D4F]/40', 'bg-[#E6F4F1]');
      if (svg) { svg.setAttribute('fill', 'none'); svg.setAttribute('stroke', 'currentColor'); }
    }
  }

  document.querySelectorAll('.bm-btn').forEach(btn => {
    paintBtn(btn, saved.has(Number(btn.dataset.kajianId)));
  });

  window.toggleUstadzBookmark = function(id, btn) {
    id = Number(id);
    const on = !saved.has(id);
    if (on) saved.add(id); else saved.delete(id);
    try { localStorage.setItem('sinikajian_bookmarks', JSON.stringify([...saved])); } catch(e) {}
    paintBtn(btn, on);

    // Sync ke server bila login
    fetch('/kajian/' + id + '/bookmark', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
      },
      body: JSON.stringify({})
    }).catch(() => {});
  };
})();
</script>
@endsection
