@extends('layouts.app')

@section('title', 'Edit Profil - Majelisku')

@section('content')
<div class="max-w-md mx-auto space-y-4 pb-28 pt-2 px-1">

  <!-- Header Area (Sesuai Screenshot Edit Profil) -->
  <header class="flex items-center gap-3">
    <a href="{{ route('profile') }}" class="w-10 h-10 rounded-full bg-white border border-[#E5ECEB] text-[#1D4D4F] flex items-center justify-center hover:bg-[#F8FAF9] active:scale-95 transition shadow-2xs" aria-label="Kembali ke Profil">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M15 19l-7-7 7-7"></path>
      </svg>
    </a>
    <div>
      <h1 class="text-xl font-extrabold text-[#1D4D4F] tracking-tight">Edit Profil</h1>
      <p class="text-xs text-[#607072] mt-0.5">Perbarui nama dan informasi pribadi.</p>
    </div>
  </header>

  @if(session('success'))
    <div class="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center gap-2">
      <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
      <span>{{ session('success') }}</span>
    </div>
  @endif

  @if($errors->any())
    <div class="p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs space-y-1">
      @foreach($errors->all() as $err)
        <p class="flex items-center gap-1.5">• {{ $err }}</p>
      @endforeach
    </div>
  @endif

  <!-- Form Edit Profil Card (Sesuai Desain Screenshot) -->
  <div class="rounded-3xl bg-white border border-[#E5ECEB] p-5 shadow-2xs">
    <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
      @csrf

      <!-- Upload Foto Profil / Avatar -->
      <div class="flex flex-col items-center justify-center pb-2">
        <div class="relative group cursor-pointer" onclick="document.getElementById('avatarInput').click();">
          <div id="avatarContainer" class="w-20 h-20 rounded-full overflow-hidden bg-[#1D4D4F] text-white flex items-center justify-center font-extrabold text-2xl shadow-sm ring-4 ring-[#EDF5F3]">
            <img id="avatarPreview" src="{{ $user->avatar ?? '' }}" alt="{{ $user->name }}" class="w-full h-full object-cover {{ !empty($user->avatar) ? '' : 'hidden' }}">
            <span id="avatarInitial" class="{{ !empty($user->avatar) ? 'hidden' : '' }}">{{ strtoupper(substr($user->name ?? 'A', 0, 2)) }}</span>
          </div>
          
          <div class="absolute inset-0 rounded-full bg-black/35 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
          </div>
        </div>

        <!-- Tombol Link Upload Foto -->
        <input type="file" id="avatarInput" name="avatar" accept="image/*" class="hidden" onchange="handleAvatarChange(event)">
        <input type="hidden" id="avatarData" name="avatar_data" value="">
        
        <button type="button" onclick="document.getElementById('avatarInput').click();" class="text-xs font-bold text-[#1D4D4F] hover:underline mt-2.5 inline-flex items-center gap-1.5">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
          <span>Unggah Foto Profil</span>
        </button>
        <span class="text-[10px] text-[#607072] mt-0.5">Format JPG, PNG, atau WEBP (Maks 3 MB)</span>
      </div>

      <!-- Nama Lengkap Field (Sesuai Screenshot) -->
      <div>
        <label for="name" class="block text-xs font-bold text-[#1C2224] mb-1.5">Nama Lengkap</label>
        <input type="text" id="name" name="name" required value="{{ old('name', $user->name) }}" class="w-full px-4 py-3 rounded-2xl bg-[#F8FAF9] border border-[#E5ECEB] text-xs text-[#1C2224] font-medium focus:outline-none focus:border-[#1D4D4F] focus:bg-white transition" placeholder="Masukkan nama lengkap">
      </div>

      <!-- Email Field (Sesuai Screenshot) -->
      <div>
        <label for="email" class="block text-xs font-bold text-[#1C2224] mb-1.5">Email</label>
        <input type="email" id="email" name="email" required value="{{ old('email', $user->email) }}" class="w-full px-4 py-3 rounded-2xl bg-[#F8FAF9] border border-[#E5ECEB] text-xs text-[#1C2224] font-medium focus:outline-none focus:border-[#1D4D4F] focus:bg-white transition" placeholder="nama@contoh.id">
      </div>

      <!-- Tombol Simpan Perubahan (Sesuai Screenshot) -->
      <div class="pt-2">
        <button type="submit" class="w-full py-3.5 rounded-full bg-[#1D4D4F] hover:bg-[#153A3B] active:scale-98 text-white font-bold text-xs sm:text-sm tracking-wide shadow-md transition touch-btn text-center">
          Simpan Perubahan
        </button>
      </div>

    </form>
  </div>

</div>

<script>
  function handleAvatarChange(e) {
    const file = e.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(event) {
      const img = new Image();
      img.onload = function() {
        // Kompresi & downscale otomatis via canvas client-side (maks 320px, ~30KB)
        const canvas = document.createElement('canvas');
        const maxDim = 320;
        let w = img.width;
        let h = img.height;
        if (w > maxDim || h > maxDim) {
          if (w > h) {
            h = Math.round((h * maxDim) / w);
            w = maxDim;
          } else {
            w = Math.round((w * maxDim) / h);
            h = maxDim;
          }
        }
        canvas.width = w;
        canvas.height = h;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(img, 0, 0, w, h);
        const compressedDataUrl = canvas.toDataURL('image/jpeg', 0.8);

        const preview = document.getElementById('avatarPreview');
        const initial = document.getElementById('avatarInitial');
        
        if (preview) {
          preview.src = compressedDataUrl;
          preview.classList.remove('hidden');
        }
        if (initial) initial.classList.add('hidden');
        
        // Simpan dataUrl terkompresi ke input hidden & localStorage
        document.getElementById('avatarData').value = compressedDataUrl;
        try {
          localStorage.setItem('sinikajian_user_avatar', compressedDataUrl);
        } catch(err) {}
      };
      img.src = event.target.result;
    };
    reader.readAsDataURL(file);
  }

  // Sinkronisasi otomatis dari localStorage jika server belum merender avatar
  document.addEventListener('DOMContentLoaded', function() {
    try {
      const localAvatar = localStorage.getItem('sinikajian_user_avatar');
      const preview = document.getElementById('avatarPreview');
      const initial = document.getElementById('avatarInitial');
      const avatarData = document.getElementById('avatarData');
      
      if (localAvatar && localAvatar.startsWith('data:image')) {
        if (preview && (!preview.src || preview.src.includes('data:image') || preview.classList.contains('hidden') || preview.getAttribute('src') === '')) {
          preview.src = localAvatar;
          preview.classList.remove('hidden');
          if (initial) initial.classList.add('hidden');
          if (avatarData && !avatarData.value) avatarData.value = localAvatar;
        }
      }
    } catch(e) {}
  });

  // Simpan sebelum submit
  const profileForm = document.querySelector('form');
  if (profileForm) {
    profileForm.addEventListener('submit', function() {
      const val = document.getElementById('avatarData').value;
      if (val && val.startsWith('data:image')) {
        try {
          localStorage.setItem('sinikajian_user_avatar', val);
        } catch(e) {}
      }
    });
  }
</script>
@endsection
