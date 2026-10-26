@extends('layouts.app')

@section('title', 'Agenda & Tersimpan - Majelisku')

@section('content')
<div class="max-w-md mx-auto space-y-4 pb-24 pt-1">
  
  <!-- Header Text -->
  <header class="flex items-center justify-between">
    <div>
      <h1 class="text-xl font-extrabold text-[#1D4D4F] tracking-tight">Koleksi & Agenda</h1>
      <p class="text-xs text-[#607072] mt-0.5">Jadwal kajian yang Anda simpan dan ikuti.</p>
    </div>
    
    <!-- Streak / Badge Stat -->
    <div class="flex items-center gap-2 bg-[#E6F4F1] border border-[#D1EAE5] px-3 py-1.5 rounded-2xl">
      <span class="text-sm">🕌</span>
      <div>
        <span class="text-[9px] font-bold text-[#1D4D4F] uppercase tracking-wider block">Total Hadir</span>
        <span class="text-xs font-black text-[#1D4D4F]"><span id="totalHadirVal">{{ count($attendances) }}</span> Majelis</span>
      </div>
    </div>
  </header>

  <!-- Segmented Tabs (Pills) -->
  <div class="flex p-1 bg-[#F0F4F3] border border-[#E5ECEB] rounded-2xl">
    <button onclick="switchSavedTab('bookmarks')" id="tabBtnBookmarks" class="flex-1 py-2 text-xs font-bold rounded-xl bg-white text-[#1D4D4F] shadow-xs transition-all">
      Ditandai ({{ count($savedKajians) }})
    </button>
    <button onclick="switchSavedTab('attendances')" id="tabBtnAttendances" class="flex-1 py-2 text-xs font-semibold rounded-xl text-[#607072] hover:text-[#1D4D4F] transition-all">
      Riwayat Hadir ({{ count($attendances) }})
    </button>
  </div>

  <!-- Tab 1: Bookmarks -->
  <div id="tabBookmarks" class="space-y-3">
    @forelse($savedKajians as $item)
      <div class="saved-kajian-card bg-white rounded-3xl p-4 shadow-xs border border-[#E5ECEB] flex flex-col justify-between transition-all hover:border-[#1D4D4F]/30">
        <div class="flex items-start justify-between gap-3">
          @if($item->poster_url)
            <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl overflow-hidden shrink-0 bg-[#F8FAF9] border border-[#E5ECEB] shadow-2xs">
              <img src="{{ $item->poster_url }}" alt="Flyer" class="w-full h-full object-cover" loading="lazy">
            </div>
          @endif
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-1.5 mb-1 flex-wrap">
              <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-[#E6F4F1] text-[#1D4D4F]">
                {{ $item->category ?? 'Kajian' }}
              </span>
              @if($item->source_url)
                <a href="{{ $item->source_url }}" target="_blank" rel="noopener" class="px-2 py-0.5 rounded-full text-[9px] font-bold text-white bg-[#1D4D4F] hover:bg-[#143839] inline-flex items-center gap-0.5">
                  <span>{{ ucfirst($item->source) }} ↗</span>
                </a>
              @endif
              <span class="text-[11px] font-semibold text-[#607072]">
                {{ $item->day ?? '' }} {{ $item->time ? '• ' . $item->time : '' }}
              </span>
            </div>
            <h3 class="text-sm font-extrabold text-[#1C2224] leading-snug line-clamp-2">{{ $item->title }}</h3>
            <p class="text-xs font-medium text-[#607072] mt-1 flex items-center gap-1.5 truncate">
              <svg class="w-3.5 h-3.5 text-[#1D4D4F] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
              {{ $item->ustadz }}
            </p>
            <p class="text-[11px] text-[#607072] mt-0.5 flex items-center gap-1.5 truncate">
              <svg class="w-3.5 h-3.5 text-[#607072] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
              {{ $item->masjid }}
            </p>
          </div>

          <!-- Unbookmark: AJAX tanpa reload (pola form lama memicu reload
               penuh; bersama sesi zombi membuat unsave terasa tak pernah
               tersimpan terhapus) -->
          <button type="button" onclick="unsaveKajian({{ $item->id }}, this)" title="Hapus dari tersimpan" class="p-2 rounded-2xl bg-[#E6F4F1] text-[#1D4D4F] hover:bg-[#d6ece7] transition">
            <svg class="w-4 h-4 fill-[#1D4D4F]" viewBox="0 0 24 24"><path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path></svg>
          </button>
        </div>

        <div class="mt-3 pt-3 border-t border-[#E5ECEB] flex items-center justify-between gap-2">
          <a href="https://www.google.com/maps/dir/?api=1&destination={{ $item->lat }},{{ $item->lon }}" target="_blank" rel="noopener" class="text-xs font-bold text-[#1D4D4F] hover:underline flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>
            Petunjuk Arah ↗
          </a>

          <button type="button" data-att-btn="{{ $item->id }}" data-title="{{ $item->title }}" data-ustadz="{{ $item->ustadz }}" data-masjid="{{ $item->masjid }}" onclick="catatKehadiran({{ $item->id }}, this)" class="px-3.5 py-1.5 rounded-full text-xs font-bold transition-all shadow-2xs {{ in_array($item->id, $attendedKajianIds ?? []) ? 'bg-[#E6F4F1] text-[#1D4D4F] border border-[#1D4D4F]/30' : 'bg-[#1D4D4F] hover:bg-[#143839] text-white' }}">
            {{ in_array($item->id, $attendedKajianIds ?? []) ? '✓ Sudah Tercatat' : 'Catat Kehadiran' }}
          </button>
        </div>
      </div>
    @empty
      <!-- Empty State -->
      <div id="savedEmptyState" class="py-12 px-4 text-center bg-white rounded-3xl border border-[#E5ECEB]">
        <div class="w-12 h-12 mx-auto rounded-2xl bg-[#E6F4F1] text-[#1D4D4F] flex items-center justify-center mb-3">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path></svg>
        </div>
        <h3 class="text-sm font-bold text-[#1C2224]">Belum Ada Kajian Ditandai</h3>
        <p class="text-xs text-[#607072] mt-1 max-w-xs mx-auto">Tandai kajian sunnah pilihan Anda dari beranda untuk menyimpan ke agenda ini.</p>
        <a href="{{ route('kajian.index') }}" class="inline-flex items-center gap-1.5 mt-4 px-4 py-2 rounded-full bg-[#1D4D4F] text-white text-xs font-bold hover:bg-[#143839] transition-colors">
          Cari Jadwal Kajian
        </a>
      </div>
    @endforelse
  </div>

  <!-- Tab 2: Attendance History -->
  <div id="tabAttendances" class="hidden space-y-3">
    @if(count($attendances) > 0)
      <div class="flex justify-end" id="attClearRow">
        <button type="button" onclick="clearAttendanceHistory()" class="text-[11px] font-bold text-red-600 hover:text-red-700 bg-red-50 border border-red-100 px-3 py-1.5 rounded-full transition">
          Bersihkan Riwayat Hadir
        </button>
      </div>
    @endif
    @forelse($attendances as $att)
      <div data-att-card="{{ $att->kajian_id ?? ($att->kajian->id ?? '') }}" class="bg-white rounded-3xl p-4 shadow-xs border border-[#E5ECEB] flex items-center justify-between gap-3">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-[#E6F4F1] text-[#1D4D4F] flex items-center justify-center font-bold text-sm shrink-0 border border-[#D1EAE5]">
            ✓
          </div>
          <div>
            <h4 class="text-xs font-bold text-[#1C2224]">{{ $att->kajian->title ?? 'Majelis Kajian' }}</h4>
            <p class="text-[11px] text-[#607072] mt-0.5">{{ $att->kajian->ustadz ?? '' }} • {{ $att->kajian->masjid ?? '' }}</p>
            <span class="text-[10px] font-semibold text-[#1D4D4F] bg-[#E6F4F1] px-2 py-0.5 rounded-full inline-block mt-1">
              Tanggal: {{ \Carbon\Carbon::parse($att->attendance_date)->isoFormat('D MMMM Y') }}
            </span>
          </div>
        </div>
      </div>
    @empty
      <div id="attEmptyState" class="py-10 px-4 text-center bg-white rounded-3xl border border-[#E5ECEB]">
        <p class="text-xs text-[#607072]">Belum ada riwayat kehadiran tercatat.</p>
      </div>
    @endforelse
  </div>

</div>

<div id="savedToast" class="hidden fixed left-1/2 -translate-x-1/2 bottom-24 z-50 bg-[#1D4D4F] text-white text-xs font-bold px-4 py-2.5 rounded-full shadow-lg"></div>

<script>
  // Catat kehadiran via AJAX di tempat (perintah user 2026-10-20): TANPA
  // navigasi/reload — pola lama memakai form HTML biasa sehingga browser
  // membuka respons JSON mentah sebagai unduhan file "kehadiran.json".
  var attTotal = {{ count($attendances) }};
  var attTodayLabel = "{{ \Carbon\Carbon::now('Asia/Jakarta')->isoFormat('D MMMM Y') }}";

  function showSavedToast(msg) {
    const t = document.getElementById('savedToast');
    if (!t) return;
    t.textContent = msg;
    t.classList.remove('hidden');
    clearTimeout(t._h);
    t._h = setTimeout(() => t.classList.add('hidden'), 2200);
  }

  function paintAttBtn(btn, attended) {
    if (attended) {
      btn.className = 'px-3.5 py-1.5 rounded-full text-xs font-bold transition-all shadow-2xs bg-[#E6F4F1] text-[#1D4D4F] border border-[#1D4D4F]/30';
      btn.textContent = '✓ Sudah Tercatat';
    } else {
      btn.className = 'px-3.5 py-1.5 rounded-full text-xs font-bold transition-all shadow-2xs bg-[#1D4D4F] hover:bg-[#143839] text-white';
      btn.textContent = 'Catat Kehadiran';
    }
  }

  function syncAttCounters() {
    const tabBtn = document.getElementById('tabBtnAttendances');
    if (tabBtn) tabBtn.innerText = `Riwayat Hadir (${attTotal})`;
    const total = document.getElementById('totalHadirVal');
    if (total) total.innerText = attTotal;
  }

  function addAttCard(id, btn) {
    const list = document.getElementById('tabAttendances');
    if (!list || list.querySelector(`[data-att-card="${id}"]`)) return;
    const empty = document.getElementById('attEmptyState');
    if (empty) empty.remove();
    const card = document.createElement('div');
    card.setAttribute('data-att-card', id);
    card.className = 'bg-white rounded-3xl p-4 shadow-xs border border-[#E5ECEB] flex items-center justify-between gap-3';
    card.innerHTML = `
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-2xl bg-[#E6F4F1] text-[#1D4D4F] flex items-center justify-center font-bold text-sm shrink-0 border border-[#D1EAE5]">✓</div>
        <div>
          <h4 class="text-xs font-bold text-[#1C2224]"></h4>
          <p class="text-[11px] text-[#607072] mt-0.5"></p>
          <span class="text-[10px] font-semibold text-[#1D4D4F] bg-[#E6F4F1] px-2 py-0.5 rounded-full inline-block mt-1">Tanggal: ${attTodayLabel}</span>
        </div>
      </div>`;
    card.querySelector('h4').textContent = btn.dataset.title || 'Majelis Kajian';
    card.querySelector('p').textContent = `${btn.dataset.ustadz || ''} • ${btn.dataset.masjid || ''}`;
    list.prepend(card);
  }

  function removeAttCard(id) {
    const list = document.getElementById('tabAttendances');
    if (!list) return;
    const card = list.querySelector(`[data-att-card="${id}"]`);
    if (card) card.remove();
    if (!list.querySelector('[data-att-card]') && !document.getElementById('attEmptyState')) {
      const empty = document.createElement('div');
      empty.id = 'attEmptyState';
      empty.className = 'py-10 px-4 text-center bg-white rounded-3xl border border-[#E5ECEB]';
      empty.innerHTML = '<p class="text-xs text-[#607072]">Belum ada riwayat kehadiran tercatat.</p>';
      list.appendChild(empty);
    }
  }

  function catatKehadiran(id, btn) {
    if (btn.disabled) return;
    btn.disabled = true;
    fetch(`/kajian/${id}/kehadiran`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        'Accept': 'application/json'
      },
      body: JSON.stringify({})
    })
    .then(r => r.json())
    .then(data => {
      btn.disabled = false;
      if (data && data.status === 'auth_required') { window.location.href = '{{ route('login') }}'; return; }
      if (!data || data.status !== 'success') { showSavedToast('Gagal mencatat kehadiran, coba lagi'); return; }
      paintAttBtn(btn, data.attended);
      attTotal += data.attended ? 1 : -1;
      if (attTotal < 0) attTotal = 0;
      syncAttCounters();
      if (data.attended) addAttCard(id, btn); else removeAttCard(id);
      showSavedToast(data.attended ? 'Kehadiran tercatat. Barakallahu fiik!' : 'Catatan kehadiran dibatalkan.');
    })
    .catch(() => { btn.disabled = false; showSavedToast('Gagal mencatat kehadiran, coba lagi'); });
  }

  // Bersihkan seluruh riwayat kehadiran (tombol perintah user 2026-10-20).
  function clearAttendanceHistory() {
    if (!confirm('Hapus semua riwayat kehadiran? Tindakan ini tidak bisa dibatalkan.')) return;
    fetch('{{ route('attendance.clear') }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        'Accept': 'application/json'
      },
      body: JSON.stringify({})
    })
    .then(r => r.json())
    .then(data => {
      if (data && data.status === 'auth_required') { window.location.href = '{{ route('login') }}'; return; }
      if (!data || data.status !== 'success') { showSavedToast('Gagal membersihkan riwayat, coba lagi'); return; }
      attTotal = 0;
      syncAttCounters();
      document.querySelectorAll('#tabAttendances [data-att-card]').forEach(c => c.remove());
      const row = document.getElementById('attClearRow');
      if (row) row.remove();
      if (!document.getElementById('attEmptyState')) {
        const empty = document.createElement('div');
        empty.id = 'attEmptyState';
        empty.className = 'py-10 px-4 text-center bg-white rounded-3xl border border-[#E5ECEB]';
        empty.innerHTML = '<p class="text-xs text-[#607072]">Belum ada riwayat kehadiran tercatat.</p>';
        document.getElementById('tabAttendances').appendChild(empty);
      }
      document.querySelectorAll('[data-att-btn]').forEach(b => paintAttBtn(b, false));
      showSavedToast('Riwayat kehadiran dibersihkan.');
    })
    .catch(() => showSavedToast('Gagal membersihkan riwayat, coba lagi'));
  }

  function switchSavedTab(tab) {
    const btnBook = document.getElementById('tabBtnBookmarks');
    const btnAtt = document.getElementById('tabBtnAttendances');
    const tabBook = document.getElementById('tabBookmarks');
    const tabAtt = document.getElementById('tabAttendances');

    if (tab === 'bookmarks') {
      btnBook.className = 'flex-1 py-2 text-xs font-bold rounded-xl bg-white text-[#1D4D4F] shadow-xs transition-all';
      btnAtt.className = 'flex-1 py-2 text-xs font-semibold rounded-xl text-[#607072] hover:text-[#1D4D4F] transition-all';
      tabBook.classList.remove('hidden');
      tabAtt.classList.add('hidden');
    } else {
      btnAtt.className = 'flex-1 py-2 text-xs font-bold rounded-xl bg-white text-[#1D4D4F] shadow-xs transition-all';
      btnBook.className = 'flex-1 py-2 text-xs font-semibold rounded-xl text-[#607072] hover:text-[#1D4D4F] transition-all';
      tabAtt.classList.remove('hidden');
      tabBook.classList.add('hidden');
    }
  }

  function unsaveKajian(id, btn) {
    fetch(`/kajian/${id}/bookmark`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        'Accept': 'application/json'
      },
      body: JSON.stringify({})
    })
    .then(r => r.json())
    .then(data => {
      if (data && data.status === 'auth_required') { window.location.href = '{{ route('login') }}'; return; }
      const newState = (typeof data.saved === 'boolean') ? data.saved : data.bookmarked;
      if (data && data.status === 'success' && newState === false) {
        removeBookmarkFromLocalStorage(id);
        const card = btn.closest('.saved-kajian-card');
        if (card) card.remove();
        const remaining = document.querySelectorAll('#tabBookmarks .saved-kajian-card').length;
        const tabBtn = document.getElementById('tabBtnBookmarks');
        if (tabBtn) tabBtn.innerText = `Ditandai (${remaining})`;
        showSavedToast('Dihapus dari tersimpan.');
        if (remaining === 0) location.reload();
      } else {
        showSavedToast('Gagal menghapus, coba lagi');
      }
    })
    .catch(() => showSavedToast('Gagal menghapus, coba lagi'));
  }

  function removeBookmarkFromLocalStorage(id) {
    try {
      let saved = JSON.parse(localStorage.getItem('sinikajian_bookmarks') || '[]');
      saved = saved.filter(x => Number(x) !== Number(id));
      localStorage.setItem('sinikajian_bookmarks', JSON.stringify(saved));
    } catch(e) {}
  }

  function removeLocalCard(id, btn) {
    removeBookmarkFromLocalStorage(id);
    const card = btn.closest('.saved-kajian-card') || btn.closest('.bg-white');
    if (card) card.remove();
    const remaining = document.querySelectorAll('#tabBookmarks .saved-kajian-card').length;
    const tabBtn = document.getElementById('tabBtnBookmarks');
    if (tabBtn) tabBtn.innerText = `Ditandai (${remaining})`;
    if (remaining === 0) {
      location.reload();
    }
  }

  // Escape & URL-aman untuk field DB sebelum innerHTML (audit 2026-10-21, FE-H1)
  function escapeHtml(str) {
    return String(str ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  }
  function safeUrl(url) {
    try {
      const u = new URL(String(url || ''), window.location.origin);
      return (u.protocol === 'http:' || u.protocol === 'https:') ? u.href : '';
    } catch (e) { return ''; }
  }

  // Hydrate client-side bookmarks HANYA untuk tamu (belum login). Untuk user
  // login, server adalah sumber kebenaran: localStorage ditimpa dari server
  // dan daftar lokal tidak pernah dipakai menambal tampilan (sumber phantom).
  pageReady(() => {
    try {
      @auth
      localStorage.setItem('sinikajian_bookmarks', JSON.stringify(@json($savedKajianIds ?? [])));
      const savedIds = @json($savedKajianIds ?? []);
      @else
      const savedIds = JSON.parse(localStorage.getItem('sinikajian_bookmarks') || '[]');
      @endauth
      const tabBook = document.getElementById('tabBookmarks');
      const emptyState = document.getElementById('savedEmptyState');
      const serverCount = {{ count($savedKajians) }};

      if (serverCount === 0 && savedIds.length > 0) {
        fetch('/api/kajian')
          .then(r => r.json())
          .then(res => {
            if (res.data && res.data.length > 0) {
              const matched = res.data.filter(k => savedIds.includes(Number(k.id)));
              if (matched.length > 0) {
                if (emptyState) emptyState.remove();
                matched.forEach(item => {
                  const card = document.createElement('div');
                  const posterUrl = safeUrl(item.poster_url);
                  const itemLat = parseFloat(item.lat);
                  const itemLon = parseFloat(item.lon);
                  card.className = "saved-kajian-card bg-white rounded-3xl p-4 shadow-xs border border-[#E5ECEB] flex flex-col justify-between transition-all hover:border-[#1D4D4F]/30";
                  card.innerHTML = `
                    <div class="flex items-start justify-between gap-3">
                      ${posterUrl ? `
                        <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl overflow-hidden shrink-0 bg-[#F8FAF9] border border-[#E5ECEB] shadow-2xs">
                          <img src="${posterUrl}" alt="Flyer" class="w-full h-full object-cover" loading="lazy">
                        </div>
                      ` : ''}
                      <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-1.5 mb-1 flex-wrap">
                          <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-[#E6F4F1] text-[#1D4D4F]">
                            ${escapeHtml(item.category || 'Kajian')}
                          </span>
                          <span class="text-[11px] font-semibold text-[#607072]">
                            ${escapeHtml(item.day || '')} ${item.time ? '• ' + escapeHtml(item.time) : ''}
                          </span>
                        </div>
                        <h3 class="text-sm font-extrabold text-[#1C2224] leading-snug line-clamp-2">${escapeHtml(item.title)}</h3>
                        <p class="text-xs font-medium text-[#607072] mt-1 flex items-center gap-1.5 truncate">
                          <svg class="w-3.5 h-3.5 text-[#1D4D4F] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                          ${escapeHtml(item.ustadz || '')}
                        </p>
                        <p class="text-[11px] text-[#607072] mt-0.5 flex items-center gap-1.5 truncate">
                          <svg class="w-3.5 h-3.5 text-[#607072] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                          ${escapeHtml(item.masjid || '')}
                        </p>
                      </div>
                      <button onclick="removeLocalCard(${Number(item.id)}, this)" title="Hapus dari tersimpan" class="p-2 rounded-2xl bg-[#E6F4F1] text-[#1D4D4F] hover:bg-[#d6ece7] transition">
                        <svg class="w-4 h-4 fill-[#1D4D4F]" viewBox="0 0 24 24"><path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path></svg>
                      </button>
                    </div>
                    <div class="mt-3 pt-3 border-t border-[#E5ECEB] flex items-center justify-between gap-2">
                      <a href="https://www.google.com/maps/dir/?api=1&destination=${isNaN(itemLat) ? 0 : itemLat},${isNaN(itemLon) ? 0 : itemLon}" target="_blank" rel="noopener" class="text-xs font-bold text-[#1D4D4F] hover:underline flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>
                        Petunjuk Arah ↗
                      </a>
                    </div>
                  `;
                  tabBook.appendChild(card);
                });
                const tabBtn = document.getElementById('tabBtnBookmarks');
                if (tabBtn) tabBtn.innerText = `Ditandai (${matched.length})`;
              }
            }
          });
      }

    } catch(e) {}
  });
</script>
@endsection