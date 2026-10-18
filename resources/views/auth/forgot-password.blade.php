@extends('layouts.app')

@section('title', 'Lupa Kata Sandi - SiniKajian')

@section('content')
<div class="max-w-md mx-auto space-y-4 pb-20 pt-2">
  
  <div class="bg-white rounded-3xl p-6 shadow-xs border border-[#E5ECEB]">
    <!-- Header -->
    <div class="text-center mb-6">
      <div class="w-12 h-12 rounded-2xl bg-[#E6F4F1] text-[#1D4D4F] flex items-center justify-center mx-auto mb-3 border border-[#D1EAE5]">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
        </svg>
      </div>
      <h1 class="text-xl font-extrabold text-[#1D4D4F] tracking-tight">Atur Ulang Kata Sandi</h1>
      <p class="text-xs text-[#607072] mt-1">Masukkan alamat email Anda untuk menerima tautan pembaruan kata sandi.</p>
    </div>

    <!-- Status Alert -->
    @if(session('status'))
      <div class="mb-5 p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold">
        <p>{{ session('status') }}</p>
        @if(session('reset_url'))
          <div class="mt-2.5 pt-2 border-t border-emerald-200">
            <a href="{{ session('reset_url') }}" class="inline-flex items-center gap-1.5 font-bold text-[#1D4D4F] underline hover:text-[#143839]">
              Lanjutkan ke Halaman Ganti Sandi &rarr;
            </a>
          </div>
        @endif
      </div>
    @endif

    <!-- Error Validation Alert -->
    @if($errors->any())
      <div class="mb-5 p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-medium">
        <ul class="list-disc list-inside space-y-1">
          @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <!-- Form -->
    <form action="{{ route('password.email') }}" method="POST" class="space-y-4">
      @csrf

      <div>
        <label for="email" class="block text-xs font-bold text-[#1C2224] mb-1.5">Alamat Email Terdaftar</label>
        <div class="relative">
          <input type="email" name="email" id="email" required value="{{ old('email') }}" placeholder="nama@email.com" class="w-full px-3.5 py-2.5 rounded-2xl border border-[#E5ECEB] text-xs text-[#1C2224] focus:outline-none focus:ring-2 focus:ring-[#1D4D4F] focus:border-transparent transition-all">
        </div>
      </div>

      <button type="submit" class="w-full py-3 rounded-full bg-[#1D4D4F] text-white text-xs font-bold hover:bg-[#143839] active:scale-98 transition-all shadow-sm touch-btn">
        Kirim Tautan Atur Ulang
      </button>
    </form>

    <div class="mt-6 text-center">
      <a href="{{ route('login') }}" class="text-xs font-bold text-[#607072] hover:text-[#1D4D4F] transition">
        &larr; Kembali ke Halaman Masuk
      </a>
    </div>

  </div>
</div>
@endsection
