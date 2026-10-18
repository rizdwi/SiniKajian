@extends('layouts.app')

@section('title', 'Masuk Akun - SiniKajian')

@section('content')
<div class="max-w-md mx-auto space-y-4 pb-20 pt-2">
  
  <div class="bg-white rounded-3xl p-6 shadow-xs border border-[#E5ECEB]">
    <!-- Header -->
    <div class="text-center mb-6">
      <div class="w-12 h-12 rounded-2xl bg-[#E6F4F1] text-[#1D4D4F] flex items-center justify-center mx-auto mb-3 border border-[#D1EAE5]">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
      </div>
      <h1 class="text-xl font-extrabold text-[#1D4D4F] tracking-tight">Masuk ke SiniKajian</h1>
      <p class="text-xs text-[#607072] mt-1">Sinkronisasi jadwal kajian favorit dan data kehadiran Anda.</p>
    </div>

    <!-- Status Alert -->
    @if(session('status'))
      <div class="mb-5 p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold">
        {{ session('status') }}
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

    <!-- Login Form -->
    <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
      @csrf

      <!-- Anti-Bot Honeypot Trap (Invisible) -->
      <div style="display:none !important; opacity:0; position:absolute; left:-9999px;" aria-hidden="true">
        <input type="text" name="website_trap" tabindex="-1" autocomplete="off" value="">
      </div>

      <div>
        <label for="email" class="block text-xs font-bold text-[#1C2224] mb-1.5">Alamat Email</label>
        <div class="relative">
          <input type="email" name="email" id="email" required value="{{ old('email') }}" placeholder="nama@email.com" class="w-full px-3.5 py-2.5 rounded-2xl border border-[#E5ECEB] text-xs text-[#1C2224] focus:outline-none focus:ring-2 focus:ring-[#1D4D4F] focus:border-transparent transition-all">
        </div>
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

      <div class="flex items-center justify-between pt-1">
        <label class="flex items-center gap-2 cursor-pointer">
          <input type="checkbox" name="remember" class="w-4 h-4 rounded text-[#1D4D4F] focus:ring-[#1D4D4F] border-[#E5ECEB]">
          <span class="text-xs text-[#607072] font-medium">Ingat perangkat ini</span>
        </label>
        <a href="{{ route('password.request') }}" class="text-xs font-bold text-[#1D4D4F] hover:underline">
          Lupa kata sandi?
        </a>
      </div>

      <button type="submit" class="w-full py-3 rounded-full bg-[#1D4D4F] text-white text-xs font-bold hover:bg-[#143839] active:scale-98 transition-all shadow-sm touch-btn">
        Masuk Sekarang
      </button>
    </form>

    <!-- Social Login -->
    <div class="mt-5">
      <div class="flex items-center gap-3 mb-3">
        <div class="flex-1 h-px bg-[#E5ECEB]"></div>
        <span class="text-[11px] text-[#607072] font-medium">atau masuk dengan</span>
        <div class="flex-1 h-px bg-[#E5ECEB]"></div>
      </div>
      <div class="grid grid-cols-1 gap-3">
        <a href="{{ route('social.redirect', 'google') }}" class="flex items-center justify-center gap-2 py-2.5 rounded-full border border-[#E5ECEB] bg-white hover:bg-slate-50 transition text-xs font-bold text-slate-700">
          <svg class="w-4 h-4" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.1c-.22-.66-.35-1.36-.35-2.1s.13-1.44.35-2.1V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l3.66-2.84z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/></svg>
          Lanjutkan dengan Google
        </a>
        {{-- Facebook: disembunyikan sampai kredensial tersedia --}}
      </div>
    </div>

    <!-- Credential Security Notice -->
    <div class="mt-5 pt-4 border-t border-[#E5ECEB] flex items-start gap-2 text-[11px] text-[#607072]">
      <svg class="w-4 h-4 text-[#1D4D4F] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
      <span>Kredensial Anda dienkripsi secara aman dengan algoritma bcrypt dan dilindungi proteksi CSRF.</span>
    </div>

    <!-- Register Link -->
    <div class="mt-6 text-center">
      <p class="text-xs text-[#607072]">
        Belum memiliki akun?
        <a href="{{ route('register') }}" class="font-bold text-[#1D4D4F] hover:underline ml-1">
          Daftar Akun Baru ↗
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
