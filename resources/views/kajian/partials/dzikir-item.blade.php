<div class="dzikir-item rounded-2xl bg-white border border-[#E5ECEB] overflow-hidden" data-waktu="{{ $waktu }}" data-idx="{{ $idx }}" data-target="{{ $item['jumlah'] }}">
  <div class="px-4 pt-4 pb-3">
    <div class="flex items-start justify-between gap-2">
      <div class="flex items-start gap-2.5 min-w-0">
        <span class="w-8 h-8 rounded-full bg-[#1D4D4F] text-white flex items-center justify-center text-sm font-black shrink-0">{{ $idx + 1 }}</span>
        <h3 class="text-sm font-bold text-[#1C2224] pt-1">{{ $item['judul'] }}</h3>
      </div>
      <span class="shrink-0 flex items-center gap-1.5 pt-1">
        <span class="dzikir-done-badge text-[11px] font-bold text-[#1D4D4F] px-2 py-0.5 rounded-full" style="display:none;background:#E8F5F3">✓ Selesai</span>
        <span class="text-[11px] font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-full">{{ $item['jumlah_label'] }}</span>
      </span>
    </div>
    <p dir="rtl" class="text-right text-xl text-[#1C2224] mt-3" style="line-height:2.1">{!! nl2br(e($item['arab'])) !!}</p>
    <p class="text-sm text-[#607072] mt-2" style="font-style:italic">{{ $item['latin'] }}</p>
    <p class="text-sm text-[#1C2224] mt-2">{{ $item['arti'] }}</p>
    <p class="text-[11px] text-[#607072] mt-2">{{ $item['dalil'] }}</p>
  </div>
  <div class="px-4 pb-3 flex items-center gap-2">
    <button onclick="dzikirTap(this, 1)" class="dzikir-count-btn flex-1 py-2.5 rounded-xl text-sm font-bold transition bg-[#E8F5F3] text-[#1D4D4F]">Ketuk untuk menghitung • 0 / {{ $item['jumlah'] }}</button>
    <button onclick="dzikirTap(this, 10)" title="Tambah 10" class="w-10 h-10 rounded-xl bg-[#F1F5F4] text-[#1D4D4F] text-sm font-black shrink-0">+10</button>
    <button onclick="dzikirResetCard(this)" title="Ulang dari nol" class="w-10 h-10 rounded-xl bg-[#F1F5F4] text-[#607072] text-sm font-bold shrink-0">↺</button>
  </div>
  @if(!empty($item['pilihan']))
  <div class="px-4 pb-3 -mt-1 flex items-center gap-2">
    <span class="text-[11px] text-[#607072]">Target:</span>
    @foreach($item['pilihan'] as $p)
      <button onclick="dzikirSetTarget(this, {{ $p }})" data-value="{{ $p }}" class="dzikir-choice px-3 py-1 rounded-full text-xs font-bold transition {{ $p === $item['jumlah'] ? 'bg-[#1D4D4F] text-white' : 'bg-[#F1F5F4] text-[#607072]' }}">{{ $p }}x</button>
    @endforeach
  </div>
  @endif
</div>
