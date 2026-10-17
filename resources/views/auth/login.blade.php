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
      </div>

      <button type="submit" class="w-full py-3 rounded-full bg-[#1D4D4F] text-white text-xs font-bold hover:bg-[#143839] active:scale-98 transition-all shadow-sm touch-btn">
        Masuk Sekarang
      </button>
    </form>

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
