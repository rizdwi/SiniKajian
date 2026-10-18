@extends('layouts.app')

@section('title', 'Verifikasi Alamat Email - SiniKajian')

@section('content')
<div class="max-w-md mx-auto space-y-4 pb-20 pt-4 px-2 sm:px-0">
  
  <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-xs border border-[#E5ECEB] text-center">
    
    <!-- Icon Spruce Teal -->
    <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-[#E6F4F1] border border-[#D1EAE5] flex items-center justify-center text-[#1D4D4F]">
      <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
      </svg>
    </div>

    <h1 class="text-xl font-extrabold text-[#1D4D4F] mb-2 tracking-tight">Verifikasi Email Anda</h1>
    <p class="text-xs sm:text-sm text-[#607072] leading-relaxed mb-6">
      Tautan konfirmasi telah kami kirimkan ke <strong class="text-[#1C2224]">{{ Auth::user()?->email }}</strong>. Silakan periksa kotak masuk atau folder spam Anda.
    </p>

    <!-- Success Feedback -->
    @if (session('success'))
      <div class="mb-5 p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium text-left flex items-start gap-2.5">
        <svg class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg>
        <span>{{ session('success') }}</span>
      </div>
    @endif

    <!-- Error Feedback -->
    @if (session('error'))
      <div class="mb-5 p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-medium text-left flex items-start gap-2.5">
        <svg class="w-4 h-4 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
        </svg>
        <span>{{ session('error') }}</span>
      </div>
    @endif

    <!-- Form Kirim Ulang -->
    <form method="POST" action="{{ route('verification.send') }}" class="space-y-3">
      @csrf
      <button type="submit" class="w-full py-3.5 px-5 rounded-full bg-[#1D4D4F] text-white text-xs font-bold hover:bg-[#143839] active:scale-[0.99] transition shadow-xs flex items-center justify-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
        </svg>
        <span>Kirim Ulang Tautan Verifikasi</span>
      </button>
    </form>

    <!-- Logout Option -->
    <form method="POST" action="{{ route('logout') }}" class="mt-4">
      @csrf
      <button type="submit" class="text-xs text-[#607072] hover:text-[#1D4D4F] font-medium transition underline">
        Keluar atau gunakan akun lain
      </button>
    </form>

    <!-- Local / Log Mode Helper Notice -->
    @if(config('mail.default') === 'log' || config('app.debug'))
      <div class="mt-6 pt-5 border-t border-[#E5ECEB] text-left">
        <div class="p-4 rounded-2xl bg-amber-50/80 border border-amber-200/80 text-amber-900 space-y-2.5">
          <div class="flex items-center gap-2 font-bold text-xs text-amber-800">
            <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span>Informasi Driver Email (Status: {{ strtoupper(config('mail.default')) }})</span>
          </div>
          <p class="text-[11px] leading-relaxed text-amber-700">
            Driver mailer saat ini disetel ke <code>{{ config('mail.default') }}</code>. Di lingkungan lokal/pengujian, email ditulis ke <code>storage/logs/laravel.log</code>. Anda dapat mengklik tombol di bawah untuk langsung memverifikasi akun ini:
          </p>
          @auth
            <a href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), ['id' => Auth::id(), 'hash' => sha1(Auth::user()->getEmailForVerification())]) }}" class="inline-flex items-center justify-center gap-1.5 w-full py-2.5 px-4 rounded-xl bg-amber-700 text-white text-xs font-bold hover:bg-amber-800 transition">
              <span>Aktivasi / Verifikasi Akun Sekarang (Bypass Pengujian)</span>
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </a>
          @endauth
        </div>
      </div>
    @endif

  </div>

</div>
@endsection
