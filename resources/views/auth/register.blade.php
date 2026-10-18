@extends('layouts.app')

@section('title', 'Daftar Akun - SiniKajian')

@section('content')
<div class="max-w-md mx-auto space-y-4 pb-20 pt-2">
  
  <div class="bg-white rounded-3xl p-6 shadow-xs border border-[#E5ECEB]">
    <!-- Header -->
    <div class="text-center mb-6">
      <div class="w-12 h-12 rounded-2xl bg-[#E6F4F1] text-[#1D4D4F] flex items-center justify-center mx-auto mb-3 border border-[#D1EAE5]">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
      </div>
      <h1 class="text-xl font-extrabold text-[#1D4D4F] tracking-tight">Buat Akun SiniKajian</h1>
      <p class="text-xs text-[#607072] mt-1">Simpan data kajian, riwayat kehadiran, dan ajukan informasi kajian baru.</p>
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

    <!-- Register Form -->
    <form action="{{ route('register.post') }}" method="POST" class="space-y-4">
      @csrf

      <!-- Anti-Bot Honeypot Trap (Invisible) -->
      <div style="display:none !important; opacity:0; position:absolute; left:-9999px;" aria-hidden="true">
        <input type="text" name="website_trap" tabindex="-1" autocomplete="off" value="">
      </div>

      <div>
        <label for="name" class="block text-xs font-bold text-[#1C2224] mb-1.5">Nama Lengkap</label>
        <input type="text" name="name" id="name" required value="{{ old('name') }}" placeholder="Contoh: Abdullah" class="w-full px-3.5 py-2.5 rounded-2xl border border-[#E5ECEB] text-xs text-[#1C2224] focus:outline-none focus:ring-2 focus:ring-[#1D4D4F] focus:border-transparent transition-all">
      </div>

      <div>
        <label for="email" class="block text-xs font-bold text-[#1C2224] mb-1.5">Alamat Email</label>
        <input type="email" name="email" id="email" required value="{{ old('email') }}" placeholder="nama@email.com" class="w-full px-3.5 py-2.5 rounded-2xl border border-[#E5ECEB] text-xs text-[#1C2224] focus:outline-none focus:ring-2 focus:ring-[#1D4D4F] focus:border-transparent transition-all">
      </div>

      <div>
        <label for="password" class="block text-xs font-bold text-[#1C2224] mb-1.5">Kata Sandi</label>
        <div class="relative">
          <input type="password" name="password" id="password" required placeholder="Minimal 8 karakter" class="w-full px-3.5 py-2.5 rounded-2xl border border-[#E5ECEB] text-xs text-[#1C2224] focus:outline-none focus:ring-2 focus:ring-[#1D4D4F] focus:border-transparent transition-all">
          <button type="button" onclick="togglePasswordVisibility('password')" class="absolute right-3.5 top-2.5 text-[#607072] hover:text-[#1D4D4F]">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
          </button>
        </div>
      </div>

      <div>
        <label for="password_confirmation" class="block text-xs font-bold text-[#1C2224] mb-1.5">Konfirmasi Kata Sandi</label>
        <div class="relative">
          <input type="password" name="password_confirmation" id="password_confirmation" required placeholder="Ulangi kata sandi" class="w-full px-3.5 py-2.5 rounded-2xl border border-[#E5ECEB] text-xs text-[#1C2224] focus:outline-none focus:ring-2 focus:ring-[#1D4D4F] focus:border-transparent transition-all">
          <button type="button" onclick="togglePasswordVisibility('password_confirmation')" class="absolute right-3.5 top-2.5 text-[#607072] hover:text-[#1D4D4F]">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
          </button>
        </div>
      </div>

      <button type="submit" class="w-full py-3 rounded-full bg-[#1D4D4F] text-white text-xs font-bold hover:bg-[#143839] active:scale-98 transition-all shadow-sm touch-btn">
        Daftar Akun Baru
      </button>
    </form>

    <!-- Login Link -->
    <div class="mt-6 text-center">
      <p class="text-xs text-[#607072]">
        Sudah memiliki akun?
        <a href="{{ route('login') }}" class="font-bold text-[#1D4D4F] hover:underline ml-1">
          Masuk di Sini ↗
        </a>
      </p>
    </div>
  </div>

</div>

<script>
  function togglePasswordVisibility(inputId) {
    const input = document.getElementById(inputId);
    input.type = input.type === 'password' ? 'text' : 'password';
  }
</script>
@endsection
