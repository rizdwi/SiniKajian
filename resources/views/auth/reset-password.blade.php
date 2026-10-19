@extends('layouts.app')

@section('title', 'Buat Kata Sandi Baru - Majelisku')

@section('content')
<div class="max-w-md mx-auto space-y-4 pb-20 pt-2">
  
  <div class="bg-white rounded-3xl p-6 shadow-xs border border-[#E5ECEB]">
    <!-- Header -->
    <div class="text-center mb-6">
      <div class="w-12 h-12 rounded-2xl bg-[#E6F4F1] text-[#1D4D4F] flex items-center justify-center mx-auto mb-3 border border-[#D1EAE5]">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
        </svg>
      </div>
      <h1 class="text-xl font-extrabold text-[#1D4D4F] tracking-tight">Buat Kata Sandi Baru</h1>
      <p class="text-xs text-[#607072] mt-1">Masukkan kata sandi baru yang aman untuk akun Majelisku Anda.</p>
    </div>

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
    <form action="{{ route('password.update') }}" method="POST" class="space-y-4">
      @csrf
      <input type="hidden" name="token" value="{{ $token }}">

      <!-- Anti-Bot Honeypot Trap (Invisible) -->
      <div style="display:none !important; opacity:0; position:absolute; left:-9999px;" aria-hidden="true">
        <input type="text" name="website_trap" tabindex="-1" autocomplete="off" value="">
      </div>

      <div>
        <label for="email" class="block text-xs font-bold text-[#1C2224] mb-1.5">Alamat Email</label>
        <div class="relative">
          <input type="email" name="email" id="email" required value="{{ old('email', $email) }}" placeholder="nama@email.com" class="w-full px-3.5 py-2.5 rounded-2xl border border-[#E5ECEB] text-xs text-[#1C2224] focus:outline-none focus:ring-2 focus:ring-[#1D4D4F] focus:border-transparent transition-all">
        </div>
      </div>

      <div>
        <label for="password" class="block text-xs font-bold text-[#1C2224] mb-1.5">Kata Sandi Baru</label>
        <div class="relative">
          <input type="password" name="password" id="password" required placeholder="Minimal 6 karakter" class="w-full px-3.5 py-2.5 rounded-2xl border border-[#E5ECEB] text-xs text-[#1C2224] focus:outline-none focus:ring-2 focus:ring-[#1D4D4F] focus:border-transparent transition-all">
        </div>
      </div>

      <div>
        <label for="password_confirmation" class="block text-xs font-bold text-[#1C2224] mb-1.5">Konfirmasi Kata Sandi Baru</label>
        <div class="relative">
          <input type="password" name="password_confirmation" id="password_confirmation" required placeholder="Ulangi kata sandi baru" class="w-full px-3.5 py-2.5 rounded-2xl border border-[#E5ECEB] text-xs text-[#1C2224] focus:outline-none focus:ring-2 focus:ring-[#1D4D4F] focus:border-transparent transition-all">
        </div>
      </div>

      <button type="submit" class="w-full py-3 rounded-full bg-[#1D4D4F] text-white text-xs font-bold hover:bg-[#143839] active:scale-98 transition-all shadow-sm touch-btn">
        Simpan Kata Sandi Baru
      </button>
    </form>

    <div class="mt-6 text-center">
      <a href="{{ route('login') }}" class="text-xs font-bold text-[#607072] hover:text-[#1D4D4F] transition">
        &larr; Batalkan & Kembali ke Halaman Masuk
      </a>
    </div>

  </div>
</div>
@endsection
