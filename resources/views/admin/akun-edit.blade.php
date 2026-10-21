@extends('layouts.app')

@section('title', 'Edit Akun Pantau - Panel Admin')

@section('content')
<div class="max-w-4xl mx-auto space-y-6 pb-24 pt-2 px-2 sm:px-0">
  <div class="bg-white rounded-3xl p-5 border border-[#E5ECEB] shadow-xs space-y-4">
    <div class="flex items-center justify-between">
      <h1 class="text-base font-extrabold text-[#1D4D4F]">Edit Akun — {{ $account['username'] }}</h1>
      <a href="{{ route('admin.akun.index') }}" class="px-3.5 py-2 rounded-full border border-[#E5ECEB] text-xs font-bold text-[#607072] hover:bg-[#F8FAF9] transition">&larr; Kembali</a>
    </div>

    @if(session('error'))
      <div class="p-4 rounded-2xl bg-red-50 border border-red-200 text-red-800 text-xs font-bold">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.akun.update', $account['id']) }}" class="space-y-3">
      @csrf @method('PUT')
      <div>
        <label class="text-[11px] font-bold text-[#607072] uppercase tracking-wide">Jenis Daftar</label>
        <p class="text-xs font-bold text-[#1C2224] mt-1">{{ ($account['list_type'] ?? 'monitor') === 'blacklist' ? 'Blacklist' : 'Dipantau' }}</p>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label class="text-[11px] font-bold text-[#607072] uppercase tracking-wide">Platform</label>
          <select name="platform" class="mt-1 w-full text-xs border border-[#E5ECEB] rounded-xl px-3 py-2.5 bg-white">
            <option value="" {{ empty($account['platform']) ? 'selected' : '' }}>— (nama/pola)</option>
            @foreach(['instagram','facebook','threads'] as $p)
              <option value="{{ $p }}" {{ ($account['platform'] ?? '') === $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="text-[11px] font-bold text-[#607072] uppercase tracking-wide">Username / Nama</label>
          <input name="username" required value="{{ $account['username'] }}" class="mt-1 w-full text-xs border border-[#E5ECEB] rounded-xl px-3 py-2.5">
        </div>
        <div>
          <label class="text-[11px] font-bold text-[#607072] uppercase tracking-wide">URL Profil</label>
          <input name="url" value="{{ $account['url'] ?? '' }}" class="mt-1 w-full text-xs border border-[#E5ECEB] rounded-xl px-3 py-2.5">
        </div>
        <div>
          <label class="text-[11px] font-bold text-[#607072] uppercase tracking-wide">Wilayah</label>
          <input name="region" value="{{ $account['region'] ?? '' }}" class="mt-1 w-full text-xs border border-[#E5ECEB] rounded-xl px-3 py-2.5">
        </div>
      </div>
      <div>
        <label class="text-[11px] font-bold text-[#607072] uppercase tracking-wide">Catatan</label>
        <input name="note" value="{{ $account['note'] ?? '' }}" class="mt-1 w-full text-xs border border-[#E5ECEB] rounded-xl px-3 py-2.5">
      </div>
      <button class="px-4 py-2.5 rounded-full bg-[#1D4D4F] text-white text-xs font-bold hover:opacity-90 transition">Simpan Perubahan</button>
    </form>
  </div>
</div>
@endsection
