@extends('layouts.app')

@section('title', 'Masuk Akun - SiniKajian')

@section('content')
<div class="max-w-md mx-auto px-4 pt-6 pb-12">
  
  <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200/80">
    <!-- Header -->
    <div class="text-center mb-6">
      <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-800 flex items-center justify-center mx-auto mb-3">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
      </div>
      <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Masuk ke SiniKajian</h1>
      <p class="text-xs text-slate-500 mt-1">Sinkronisasi jadwal kajian favorit dan data kehadiran Anda.</p>
    </div>

    <!-- Error Validation Alert -->
    @if($errors->any())
      <div class="mb-5 p-3.5 rounded-2xl bg-red-50 border border-red-200 text-red-700 text-xs font-medium">
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
        <label for="email" class="block text-xs font-bold text-slate-700 mb-1.5">Alamat Email</label>
        <div class="relative">
          <input type="email" name="email" id="email" required value="{{ old('email') }}" placeholder="nama@email.com" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:border-transparent transition-all">
        </div>
      </div>

      <div>
        <label for="password" class="block text-xs font-bold text-slate-700 mb-1.5">Kata Sandi</label>
        <div class="relative">
          <input type="password" name="password" id="password" required placeholder="Minimal 8 karakter" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:border-transparent transition-all">
          <button type="button" onclick="togglePasswordVisibility('password')" class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
          </button>
        </div>
      </div>

      <div class="flex items-center justify-between pt-1">
        <label class="flex items-center gap-2 cursor-pointer">
          <input type="checkbox" name="remember" class="w-4 h-4 rounded text-emerald-800 focus:ring-emerald-700 border-slate-300">
          <span class="text-xs text-slate-600 font-medium">Ingat perangkat ini</span>
        </label>
      </div>

      <button type="submit" class="w-full py-3 rounded-xl bg-emerald-800 text-white text-xs font-bold hover:bg-emerald-900 active:scale-95 transition-all shadow-sm">
        Masuk Sekarang
      </button>
    </form>

    <!-- Credential Security Notice -->
    <div class="mt-5 pt-4 border-t border-slate-100 flex items-start gap-2 text-[11px] text-slate-500">
      <svg class="w-4 h-4 text-emerald-700 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
      <span>Kredensial Anda dienkripsi secara aman dengan algoritma bcrypt dan dilindungi proteksi CSRF.</span>
    </div>

    <!-- Register Link -->
    <div class="mt-6 text-center">
      <p class="text-xs text-slate-600">
        Belum memiliki akun?
        <a href="{{ route('register') }}" class="font-bold text-emerald-800 hover:text-emerald-900 underline ml-1">
          Daftar Akun Baru
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
