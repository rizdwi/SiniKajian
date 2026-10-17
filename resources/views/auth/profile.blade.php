@extends('layouts.app')

@section('title', 'Profil Pengguna - SiniKajian')

@section('content')
<div class="max-w-md mx-auto px-4 pt-6 pb-12 space-y-5">
  
  <!-- Profile Header Card -->
  <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200/80 text-center relative overflow-hidden">
    <div class="absolute -top-12 -right-12 w-32 h-32 rounded-full bg-emerald-50 pointer-events-none"></div>
    
    <!-- User Avatar Initial -->
    <div class="w-16 h-16 rounded-3xl bg-emerald-800 text-white flex items-center justify-center text-xl font-black mx-auto shadow-md border-2 border-white">
      {{ strtoupper(substr($user->name, 0, 1)) }}
    </div>

    <h1 class="text-base font-extrabold text-slate-900 mt-3">{{ $user->name }}</h1>
    <p class="text-xs text-slate-500 font-mono mt-0.5">{{ $user->email }}</p>
    
    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-emerald-800 text-[11px] font-bold mt-3">
      <span>●</span>
      {{ ucfirst($user->role ?? 'Penuntut Ilmu') }}
    </div>

    <!-- Stats Row -->
    <div class="grid grid-cols-3 gap-2 mt-6 pt-5 border-t border-slate-100">
      <div class="p-2.5 rounded-2xl bg-slate-50 border border-slate-100">
        <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider block">Ditandai</span>
        <span class="text-base font-black text-slate-900 mt-0.5 block">{{ count($savedKajians) }}</span>
      </div>
      <div class="p-2.5 rounded-2xl bg-slate-50 border border-slate-100">
        <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider block">Hadir</span>
        <span class="text-base font-black text-emerald-800 mt-0.5 block">{{ count($attendances) }}</span>
      </div>
      <div class="p-2.5 rounded-2xl bg-slate-50 border border-slate-100">
        <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider block">Diajukan</span>
        <span class="text-base font-black text-slate-900 mt-0.5 block">{{ count($submittedKajians) }}</span>
      </div>
    </div>
  </div>

  <!-- Submitted Kajians List (Moderation status) -->
  <div class="bg-white rounded-3xl p-5 shadow-sm border border-slate-200/80 space-y-3">
    <div class="flex items-center justify-between">
      <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Kajian Yang Anda Ajukan</h3>
      <span class="text-xs text-slate-400 font-mono">{{ count($submittedKajians) }} total</span>
    </div>

    @forelse($submittedKajians as $sub)
      <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/70 flex items-start justify-between gap-3">
        <div class="min-w-0 flex-1">
          <div class="flex items-center gap-2 mb-1">
            @if($sub->verified)
              <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                Terverifikasi
              </span>
            @else
              <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                Menunggu Review
              </span>
            @endif
            <span class="text-[10px] text-slate-500">{{ $sub->day ?? '' }}</span>
          </div>
          <h4 class="text-xs font-bold text-slate-900 truncate">{{ $sub->title }}</h4>
          <p class="text-[11px] text-slate-600 truncate mt-0.5">{{ $sub->ustadz }} • {{ $sub->masjid }}</p>
        </div>
      </div>
    @empty
      <div class="py-6 text-center text-slate-400 text-xs">
        <p>Anda belum pernah mengajukan jadwal kajian.</p>
        <p class="text-[11px] text-slate-400 mt-1">Gunakan tombol 'Ajukan' di beranda jika ada info kajian sunnah yang belum terdaftar.</p>
      </div>
    @endforelse
  </div>

  <!-- Logout Action -->
  <div class="bg-white rounded-3xl p-4 shadow-sm border border-slate-200/80">
    <form action="{{ route('logout') }}" method="POST">
      @csrf
      <button type="submit" class="w-full py-3 rounded-2xl bg-red-50 text-red-700 hover:bg-red-100 text-xs font-bold transition-all flex items-center justify-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
        Keluar Dari Akun
      </button>
    </form>
  </div>

</div>
@endsection
