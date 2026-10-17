@extends('layouts.app')

@section('title', 'Daftar Akun - SiniKajian')

@section('content')
<div class="max-w-md mx-auto px-4 pt-6 pb-12">
  
  <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200/80">
    <!-- Header -->
    <div class="text-center mb-6">
      <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-800 flex items-center justify-center mx-auto mb-3">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
      </div>
      <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Buat Akun SiniKajian</h1>
      <p class="text-xs text-slate-500 mt-1">Simpan data kajian, riwayat kehadiran, dan ajukan informasi kajian baru.</p>
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

    <!-- Register Form -->
    <form action="{{ route('register.post') }}" method="POST" class="space-y-4">
      @csrf

      <div>
        <label for="name" class="block text-xs font-bold text-slate-700 mb-1.5">Nama Lengkap</label>
        <input type="text" name="name" id="name" required value="{{ old('name') }}" placeholder="Contoh: Abdullah" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:border-transparent transition-all">
      </div>

      <div>
        <label for="email" class="block text-xs font-bold text-slate-700 mb-1.5">Alamat Email</label>
        <input type="email" name="email" id="email" required value="{{ old('email') }}" placeholder="nama@email.com" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:border-transparent transition-all">
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

      <div>
        <label for="password_confirmation" class="block text-xs font-bold text-slate-700 mb-1.5">Konfirmasi Kata Sandi</label>
        <div class="relative">
          <input type="password" name="password_confirmation" id="password_confirmation" required placeholder="Ulangi kata sandi" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:border-transparent transition-all">
          <button type="button" onclick="togglePasswordVisibility('password_confirmation')" class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
          </button>
        </div>
      </div>

      <button type="submit" class="w-full py-3 rounded-xl bg-emerald-800 text-white text-xs font-bold hover:bg-emerald-900 active:scale-95 transition-all shadow-sm">
        Daftar Akun
      </button>
    </form>

    <!-- Login Link -->
    <div class="mt-6 text-center">
      <p class="text-xs text-slate-600">
        Sudah memiliki akun?
        <a href="{{ route('login') }}" class="font-bold text-emerald-800 hover:text-emerald-900 underline ml-1">
          Masuk di Sini
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
