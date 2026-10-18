@extends('layouts.app')

@section('title', 'Moderasi Jadwal Kajian - Panel Admin')

@section('content')
<div class="max-w-4xl mx-auto space-y-6 pb-24 pt-2 px-2 sm:px-0">
  
  <!-- Header Bar -->
  <div class="flex items-center justify-between bg-white rounded-3xl p-5 border border-[#E5ECEB] shadow-xs">
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 rounded-2xl bg-[#E6F4F1] text-[#1D4D4F] flex items-center justify-center font-bold">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
      </div>
      <div>
        <h1 class="text-base font-extrabold text-[#1D4D4F]">Moderasi Majelis & Kajian</h1>
        <p class="text-xs text-[#607072]">Kelola pengajuan komunitas dan keabsahan jadwal kajian.</p>
      </div>
    </div>
    <a href="{{ route('profile') }}" class="px-3.5 py-2 rounded-full border border-[#E5ECEB] text-xs font-bold text-[#607072] hover:bg-[#F8FAF9] transition">
      &larr; Ke Profil
    </a>
  </div>

  <!-- Alert Notifikasi -->
  @if(session('success'))
    <div class="p-4 rounded-2xl bg-[#E6F4F1] border border-[#D1EAE5] text-[#1D4D4F] text-xs font-bold flex items-center gap-2">
      <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
      <span>{{ session('success') }}</span>
    </div>
  @endif

  <!-- Section 1: Antrean Pengajuan Belum Diverifikasi -->
  <div class="bg-white rounded-3xl p-5 border border-[#E5ECEB] shadow-xs space-y-4">
    <div class="flex items-center justify-between pb-2 border-b border-[#E5ECEB]">
      <div class="flex items-center gap-2">
        <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse"></span>
        <h2 class="text-sm font-extrabold text-[#1C2224]">Antrean Pengajuan Jamaah</h2>
      </div>
      <span class="px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-800 text-[11px] font-bold border border-amber-200">
        {{ $pendingKajians->count() }} Perlu Ditinjau
      </span>
    </div>

    @if($pendingKajians->isEmpty())
      <div class="py-8 text-center text-[#607072] text-xs">
        <p class="font-medium">Tidak ada antrean pengajuan jadwal kajian baru saat ini.</p>
      </div>
    @else
      <div class="space-y-3">
        @foreach($pendingKajians as $item)
          <div class="p-4 rounded-2xl bg-[#F8FAF9] border border-[#E5ECEB] flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div class="space-y-1">
              <div class="flex items-center gap-2 flex-wrap">
                <span class="px-2 py-0.5 rounded-md bg-amber-100 text-amber-800 text-[10px] font-bold">Menunggu</span>
                <span class="px-2 py-0.5 rounded-md bg-[#E6F4F1] text-[#1D4D4F] text-[10px] font-bold">{{ $item->category }}</span>
                <span class="text-[11px] text-[#607072]">{{ $item->day }} &bull; {{ $item->time }}</span>
              </div>
              <h3 class="text-sm font-bold text-[#1C2224]">{{ $item->title }}</h3>
              <p class="text-xs text-[#607072]">Pemateri: <span class="font-semibold text-[#1C2224]">{{ $item->ustadz }}</span> &bull; Masjid: <span class="font-semibold text-[#1C2224]">{{ $item->masjid }}</span></p>
              @if($item->description)
                <p class="text-[11px] text-[#607072] line-clamp-2 mt-1">{{ $item->description }}</p>
              @endif
            </div>

            <div class="flex items-center gap-2 shrink-0 pt-2 md:pt-0">
              <form action="{{ route('admin.kajian.verify', $item) }}" method="POST">
                @csrf
                <button type="submit" class="px-3.5 py-2 rounded-full bg-[#1D4D4F] hover:bg-[#143839] text-white text-xs font-bold transition active:scale-95 shadow-2xs">
                  Setujui & Terbitkan
                </button>
              </form>
              <form action="{{ route('admin.kajian.destroy', $item) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menolak & menghapus pengajuan ini?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-3.5 py-2 rounded-full bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-bold transition active:scale-95 border border-rose-200">
                  Tolak
                </button>
              </form>
            </div>
          </div>
        @endforeach
      </div>
    @endif
  </div>

  <!-- Section 2: Kajian Aktif & Terverifikasi -->
  <div class="bg-white rounded-3xl p-5 border border-[#E5ECEB] shadow-xs space-y-4">
    <div class="flex items-center justify-between pb-2 border-b border-[#E5ECEB]">
      <h2 class="text-sm font-extrabold text-[#1C2224]">Kajian Aktif di Feed Publik</h2>
      <span class="text-xs text-[#607072] font-semibold">Total: {{ $verifiedKajians->total() }}</span>
    </div>

    <div class="divide-y divide-[#E5ECEB]">
      @foreach($verifiedKajians as $item)
        <div class="py-3.5 flex items-center justify-between gap-3">
          <div class="space-y-0.5">
            <div class="flex items-center gap-2">
              <span class="px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 text-[10px] font-bold border border-emerald-200">Aktif</span>
              <span class="text-xs font-bold text-[#1C2224]">{{ $item->title }}</span>
            </div>
            <p class="text-[11px] text-[#607072]">
              {{ $item->ustadz }} &bull; {{ $item->masjid }} &bull; <span class="font-medium">{{ $item->day }}</span>
            </p>
          </div>
          <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('kajian.show', $item) }}" target="_blank" class="px-3 py-1.5 rounded-full border border-[#E5ECEB] text-xs font-bold text-[#1D4D4F] hover:bg-[#F8FAF9] transition">
              Lihat
            </a>
            <form action="{{ route('admin.kajian.destroy', $item) }}" method="POST" onsubmit="return confirm('Hapus kajian ini dari sistem?')">
              @csrf
              @method('DELETE')
              <button type="submit" class="p-1.5 rounded-full text-slate-400 hover:text-rose-600 transition" title="Hapus">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
              </button>
            </form>
          </div>
        </div>
      @endforeach
    </div>

    <div class="pt-2">
      {{ $verifiedKajians->links() }}
    </div>
  </div>

</div>
@endsection
