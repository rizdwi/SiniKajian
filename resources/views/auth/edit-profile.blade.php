@extends('layouts.app')

@section('title', 'Edit Profil - SiniKajian')

@section('content')
<div class="max-w-md mx-auto space-y-4 pb-24 pt-1">
  <header class="flex items-center gap-3">
    <a href="{{ route('profile') }}" class="w-9 h-9 rounded-full bg-white border border-[#E5ECEB] flex items-center justify-center text-[#1D4D4F]">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    </a>
    <div>
      <h1 class="text-xl font-extrabold text-[#1D4D4F] tracking-tight">Edit Profil</h1>
      <p class="text-xs text-[#607072] mt-0.5">Perbarui nama dan informasi pribadi.</p>
    </div>
  </header>

  @if($errors->any())
  <div class="p-3 rounded-2xl bg-red-50 border border-red-200 text-xs text-red-700">
    @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
  </div>
  @endif

  <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="bg-white rounded-3xl p-5 border border-[#E5ECEB] space-y-4">
    @csrf
    @method('PUT')

    <div class="text-center">
      <div class="w-20 h-20 rounded-full bg-[#1D4D4F] text-white flex items-center justify-center text-2xl font-black mx-auto overflow-hidden">
        @if($user->avatar)
          <img src="{{ $user->avatar }}" alt="Foto" class="w-full h-full object-cover">
        @else
          {{ strtoupper(substr($user->name, 0, 2)) }}
        @endif
      </div>
      <label class="text-xs font-bold text-[#1D4D4F] mt-2 inline-block cursor-pointer hover:underline">
        Ganti Foto
        <input type="file" name="avatar" accept="image/*" class="hidden">
      </label>
    </div>

    <div>
      <label class="text-xs font-bold text-[#1C2224] block mb-1.5">Nama Lengkap</label>
      <input type="text" name="name" value="{{ old('name', $user->name) }}" required
        class="w-full px-4 py-2.5 rounded-2xl border border-[#E5ECEB] text-sm focus:outline-none focus:border-[#1D4D4F]">
    </div>

    <div>
      <label class="text-xs font-bold text-[#1C2224] block mb-1.5">Email</label>
      <input type="email" name="email" value="{{ old('email', $user->email) }}" required
        class="w-full px-4 py-2.5 rounded-2xl border border-[#E5ECEB] text-sm focus:outline-none focus:border-[#1D4D4F]">
    </div>

    <button type="submit" class="w-full py-3 rounded-full bg-[#1D4D4F] text-white text-sm font-bold hover:bg-[#163d3f] transition">
      Simpan Perubahan
    </button>
  </form>
</div>
@endsection
