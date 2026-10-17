@extends('layouts.app')

@section('title', 'Verifikasi Email | SiniKajian')

@section('content')
<div class="max-w-md mx-auto px-4 pt-16 pb-8 text-center">
  <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-[#E6F4F1] flex items-center justify-center">
    <svg class="w-8 h-8 text-[#1D4D4F]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
    </svg>
  </div>

  <h1 class="text-xl font-extrabold text-[#1D4D4F] mb-2">Verifikasi Email Kamu</h1>
  <p class="text-sm text-[#607072] mb-6">
    Kami mengirim link verifikasi ke <strong>{{ Auth::user()->email }}</strong>.
    Klik link tersebut untuk mengaktifkan akunmu.
  </p>

  @if (session('success'))
    <div class="mb-4 p-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-xs">
      {{ session('success') }}
    </div>
  @endif

  <form method="POST" action="{{ route('verification.send') }}">
    @csrf
    <button type="submit" class="w-full py-3 rounded-full bg-[#1D4D4F] text-white text-xs font-bold hover:bg-[#143839] transition">
      Kirim Ulang Email Verifikasi
    </button>
  </form>

  <form method="POST" action="{{ route('logout') }}" class="mt-3">
    @csrf
    <button type="submit" class="text-xs text-[#607072] hover:text-[#1D4D4F] underline">
      Keluar
    </button>
  </form>
</div>
@endsection
