@extends('layouts.app')

@section('title', 'Kajian Tersimpan & Kehadiran - SiniKajian')

@section('content')
<div class="max-w-md mx-auto px-4 pt-4 pb-12 space-y-5">
  
  <!-- Header -->
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Koleksi Saya</h1>
      <p class="text-xs text-slate-500 mt-0.5">Jadwal kajian yang Anda tandai dan ikuti</p>
    </div>
    
    <!-- Streak / Badge Stat -->
    <div class="flex items-center gap-2 bg-emerald-50 border border-emerald-200/80 px-3 py-1.5 rounded-2xl">
      <span class="text-base">🕌</span>
      <div>
        <span class="text-[10px] font-bold text-emerald-800 uppercase tracking-wider block">Hadir</span>
        <span class="text-xs font-black text-emerald-900">{{ count($attendances) }} Majelis</span>
      </div>
    </div>
  </div>

  <!-- Segmented Tabs -->
  <div class="flex p-1 bg-slate-100 rounded-2xl">
    <button onclick="switchSavedTab('bookmarks')" id="tabBtnBookmarks" class="flex-1 py-2 text-xs font-bold rounded-xl bg-white text-slate-900 shadow-sm transition-all">
      Ditandai ({{ count($savedKajians) }})
    </button>
    <button onclick="switchSavedTab('attendances')" id="tabBtnAttendances" class="flex-1 py-2 text-xs font-semibold rounded-xl text-slate-600 hover:text-slate-900 transition-all">
      Kehadiran ({{ count($attendances) }})
    </button>
  </div>

  <!-- Tab 1: Bookmarks -->
  <div id="tabBookmarks" class="space-y-3">
    @forelse($savedKajians as $item)
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-200/80 flex flex-col justify-between transition-all hover:shadow-md">
        <div class="flex items-start justify-between gap-3">
          <div class="flex-1">
            <div class="flex items-center gap-2 mb-1.5">
              <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-800">
                {{ $item->category ?? 'Kajian' }}
              </span>
              <span class="text-[11px] font-semibold text-slate-500">
                {{ $item->day ?? '' }} {{ $item->time ? '• ' . $item->time : '' }}
              </span>
            </div>
            <h3 class="text-sm font-bold text-slate-900">{{ $item->title }}</h3>
            <p class="text-xs font-medium text-slate-600 mt-1 flex items-center gap-1.5">
              <svg class="w-3.5 h-3.5 text-emerald-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
              {{ $item->ustadz }}
            </p>
            <p class="text-[11px] text-slate-500 mt-1 flex items-center gap-1.5">
              <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
              {{ $item->masjid }}
            </p>
          </div>

          <!-- Unbookmark Form -->
          <form action="{{ route('kajian.bookmark', $item->id) }}" method="POST">
            @csrf
            <button type="submit" title="Hapus dari tersimpan" class="p-2 rounded-xl bg-amber-50 text-amber-600 hover:bg-amber-100 transition-colors">
              <svg class="w-4 h-4 fill-amber-500" viewBox="0 0 24 24"><path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path></svg>
            </button>
          </form>
        </div>

        <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
          <a href="https://www.google.com/maps/dir/?api=1&destination={{ $item->lat }},{{ $item->lon }}" target="_blank" rel="noopener" class="text-xs font-bold text-emerald-800 hover:text-emerald-900 flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>
            Petunjuk Arah
          </a>

          <form action="{{ route('kajian.attendance', $item->id) }}" method="POST">
            @csrf
            <button type="submit" class="px-3 py-1.5 rounded-xl bg-slate-100 text-slate-700 hover:bg-emerald-50 hover:text-emerald-800 text-xs font-bold transition-all">
              Catat Kehadiran
            </button>
          </form>
        </div>
      </div>
    @empty
      <!-- Empty State -->
      <div class="py-12 px-4 text-center bg-white rounded-3xl border border-slate-200/80">
        <div class="w-14 h-14 mx-auto rounded-2xl bg-slate-100 flex items-center justify-center text-slate-400 mb-3">
          <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path></svg>
        </div>
        <h3 class="text-sm font-bold text-slate-800">Belum Ada Kajian Ditandai</h3>
        <p class="text-xs text-slate-500 mt-1 max-w-xs mx-auto">Tandai kajian favorit Anda dengan ikon bookmark di beranda untuk menyimpannya di sini.</p>
        <a href="{{ route('kajian.index') }}" class="inline-flex items-center gap-1.5 mt-4 px-4 py-2 rounded-xl bg-emerald-800 text-white text-xs font-bold hover:bg-emerald-900 transition-colors">
          Cari Jadwal Kajian
        </a>
      </div>
    @endforelse
  </div>

  <!-- Tab 2: Attendance History -->
  <div id="tabAttendances" class="hidden space-y-3">
    @forelse($attendances as $att)
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-200/80 flex items-center justify-between gap-3">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-800 flex items-center justify-center font-bold text-sm shrink-0">
            ✓
          </div>
          <div>
            <h4 class="text-xs font-bold text-slate-900">{{ $att->kajian->title ?? 'Majelis Kajian' }}</h4>
            <p class="text-[11px] text-slate-500 mt-0.5">{{ $att->kajian->ustadz ?? '' }} • {{ $att->kajian->masjid ?? '' }}</p>
            <span class="text-[10px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full inline-block mt-1">
              Tanggal: {{ \Carbon\Carbon::parse($att->attendance_date)->isoFormat('D MMMM Y') }}
            </span>
          </div>
        </div>
      </div>
    @empty
      <div class="py-12 px-4 text-center bg-white rounded-3xl border border-slate-200/80">
        <div class="w-14 h-14 mx-auto rounded-2xl bg-slate-100 flex items-center justify-center text-slate-400 mb-3">
          <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <h3 class="text-sm font-bold text-slate-800">Belum Ada Catatan Kehadiran</h3>
        <p class="text-xs text-slate-500 mt-1 max-w-xs mx-auto">Klik tombol 'Hadir' pada kartu kajian setelah Anda menghadiri majelis ilmu untuk mendata riwayat kehadiran.</p>
      </div>
    @endforelse
  </div>

</div>

<script>
  function switchSavedTab(tab) {
    const tabBookmarks = document.getElementById('tabBookmarks');
    const tabAttendances = document.getElementById('tabAttendances');
    const btnB = document.getElementById('tabBtnBookmarks');
    const btnA = document.getElementById('tabBtnAttendances');

    if (tab === 'bookmarks') {
      tabBookmarks.classList.remove('hidden');
      tabAttendances.classList.add('hidden');
      btnB.className = 'flex-1 py-2 text-xs font-bold rounded-xl bg-white text-slate-900 shadow-sm transition-all';
      btnA.className = 'flex-1 py-2 text-xs font-semibold rounded-xl text-slate-600 hover:text-slate-900 transition-all';
    } else {
      tabBookmarks.classList.add('hidden');
      tabAttendances.classList.remove('hidden');
      btnA.className = 'flex-1 py-2 text-xs font-bold rounded-xl bg-white text-slate-900 shadow-sm transition-all';
      btnB.className = 'flex-1 py-2 text-xs font-semibold rounded-xl text-slate-600 hover:text-slate-900 transition-all';
    }
  }
</script>
@endsection
