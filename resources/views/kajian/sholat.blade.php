@extends('layouts.app')

@section('title', 'Jadwal Sholat - Majelisku')

@section('meta_description', 'Jadwal sholat hari ini sesuai lokasi Anda — Subuh, Dzuhur, Ashar, Maghrib, Isya — lengkap dengan pengingat adzan, dzikir pagi petang, dan puasa Senin-Kamis.')

@section('content')
<div class="max-w-md mx-auto space-y-3.5 pb-24 pt-1">

  <!-- 1. Header -->
  <header class="flex items-center justify-between">
    <div>
      <h1 class="text-xl font-extrabold text-[#1D4D4F] tracking-tight">Jadwal Sholat</h1>
      <p class="text-xs text-[#607072] mt-0.5">
        {{ $gregorian ?? \Carbon\Carbon::now('Asia/Jakarta')->translatedFormat('l, d F Y') }}
        @if($hijri) • {{ $hijri }} @endif
      </p>
    </div>
    <button onclick="refreshSholatGps()" title="Perbarui dari GPS" class="w-9 h-9 rounded-full border border-[#E5ECEB] bg-white text-[#1D4D4F] flex items-center justify-center hover:bg-[#F8FAF9] active:scale-95 transition shadow-2xs">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
    </button>
  </header>

  <!-- 2. Lokasi -->
  <div class="rounded-2xl bg-[#E8F5F3] border border-[#D1ECE7] p-3 flex items-center justify-between gap-3 shadow-2xs">
    <div class="flex items-center gap-2.5 min-w-0">
      <div class="w-8 h-8 rounded-full bg-[#1D4D4F] text-white flex items-center justify-center shrink-0 shadow-2xs">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
      </div>
      <div class="min-w-0">
        <h3 id="sholatLocationName" class="text-xs font-bold text-[#1C2224] truncate">{{ $city }}</h3>
        <p id="sholatCoordinates" class="text-[10px] text-[#607072] font-mono truncate">{{ number_format($lat, 4) }}, {{ number_format($lon, 4) }}</p>
      </div>
    </div>
    <button onclick="refreshSholatGps()" class="text-xs font-bold text-[#1D4D4F] hover:underline px-2 py-1 shrink-0">GPS Saya</button>
  </div>

  <!-- 3. Sholat berikutnya + hitung mundur -->
  <div class="rounded-3xl bg-[#1D4D4F] text-white p-5 shadow-md">
    <p class="text-[11px] text-white/70 font-medium">Sholat berikutnya</p>
    <div class="flex items-end justify-between gap-3 mt-1">
      <h2 id="nextPrayerName" class="text-2xl font-black leading-none">—</h2>
      <span id="nextPrayerTime" class="text-lg font-bold text-amber-300">—</span>
    </div>
    <p class="mt-2 text-sm font-mono font-bold tracking-wider"><span id="nextPrayerCountdown">—</span></p>
    <p id="nextPrayerNote" class="text-[11px] text-white/60 mt-1"></p>
  </div>

  <!-- 4. Daftar waktu sholat -->
  <div class="rounded-2xl bg-white border border-[#E5ECEB] shadow-2xs overflow-hidden">
    <div class="px-4 py-3 border-b border-[#E5ECEB] flex items-center justify-between">
      <h3 class="text-sm font-bold text-[#1C2224]">Waktu Sholat Hari Ini</h3>
      <span class="text-[10px] font-medium text-[#607072]">Metode Kemenag RI</span>
    </div>
    <div id="prayerList">
      @forelse($timings as $name => $time)
        <div data-prayer="{{ $name }}" data-time="{{ $time }}" class="prayer-row flex items-center justify-between px-4 py-3 border-b border-[#F1F5F4] last:border-b-0">
          <span class="text-sm font-bold text-[#1C2224]">{{ $name }}</span>
          <span class="text-sm font-mono font-bold text-[#1D4D4F]">{{ $time ?: '—' }}</span>
        </div>
      @empty
        <p class="p-4 text-sm text-[#607072]">Jadwal sholat tidak dapat dimuat. Periksa koneksi lalu segarkan halaman.</p>
      @endforelse
    </div>
  </div>

  <!-- 5. Pengingat ibadah -->
  <div class="rounded-2xl bg-white border border-[#E5ECEB] shadow-2xs overflow-hidden">
    <div class="px-4 py-3 border-b border-[#E5ECEB]">
      <h3 class="text-sm font-bold text-[#1C2224]">Pengingat Ibadah</h3>
      <p class="text-[11px] text-[#607072] mt-0.5">Notifikasi push ke perangkat Anda — sama seperti pengingat kajian.</p>
    </div>
    @if($isLoggedIn)
      <div class="divide-y divide-[#F1F5F4]">
        <div class="flex items-center justify-between px-4 py-3">
          <div>
            <p class="text-sm font-bold text-[#1C2224]">Adzan 5 Waktu</p>
            <p class="text-[11px] text-[#607072]">Diingatkan tepat saat masuk waktu sholat.</p>
          </div>
          <button data-pref="adzan" onclick="togglePref(this)" class="pref-btn px-3.5 py-1.5 rounded-full text-xs font-bold transition {{ ($prefs['adzan'] ?? false) ? 'bg-[#1D4D4F] text-white' : 'bg-[#F1F5F4] text-[#607072]' }}">{{ ($prefs['adzan'] ?? false) ? 'Aktif' : 'Mati' }}</button>
        </div>
        <div class="flex items-center justify-between px-4 py-3">
          <div>
            <p class="text-sm font-bold text-[#1C2224]">Dzikir Pagi &amp; Petang</p>
            <p class="text-[11px] text-[#607072]">Setelah Subuh dan setelah Ashar.</p>
          </div>
          <button data-pref="dzikir" onclick="togglePref(this)" class="pref-btn px-3.5 py-1.5 rounded-full text-xs font-bold transition {{ ($prefs['dzikir'] ?? false) ? 'bg-[#1D4D4F] text-white' : 'bg-[#F1F5F4] text-[#607072]' }}">{{ ($prefs['dzikir'] ?? false) ? 'Aktif' : 'Mati' }}</button>
        </div>
        <div class="flex items-center justify-between px-4 py-3">
          <div>
            <p class="text-sm font-bold text-[#1C2224]">Puasa Senin &amp; Kamis</p>
            <p class="text-[11px] text-[#607072]">Diingatkan malam sebelumnya (Ahad &amp; Rabu, 20.00).</p>
          </div>
          <button data-pref="puasa" onclick="togglePref(this)" class="pref-btn px-3.5 py-1.5 rounded-full text-xs font-bold transition {{ ($prefs['puasa'] ?? false) ? 'bg-[#1D4D4F] text-white' : 'bg-[#F1F5F4] text-[#607072]' }}">{{ ($prefs['puasa'] ?? false) ? 'Aktif' : 'Mati' }}</button>
        </div>
      </div>
      <p id="prefStatus" class="px-4 py-2 text-[11px] text-[#607072]"></p>
    @else
      <div class="p-4">
        <p class="text-sm text-[#607072]">Masuk dulu untuk mengaktifkan pengingat adzan, dzikir, dan puasa di perangkat Anda.</p>
        <a href="{{ route('login') }}" class="inline-block mt-3 px-4 py-2 rounded-xl bg-[#1D4D4F] text-white text-xs font-bold">Masuk / Daftar</a>
      </div>
    @endif
    <a href="{{ route('kajian.dzikir') }}" class="block px-4 py-3 border-t border-[#E5ECEB] text-sm font-bold text-[#1D4D4F]">📿 Baca dzikir pagi &amp; petang →</a>
  </div>

  <!-- 5b. Suara adzan -->
  <div class="rounded-2xl bg-white border border-[#E5ECEB] overflow-hidden">
    <div class="px-4 py-3 border-b border-[#E5ECEB]">
      <h3 class="text-sm font-bold text-[#1C2224]">Suara Adzan</h3>
      <p class="text-[11px] text-[#607072] mt-0.5">Diputar saat waktu sholat tiba (halaman ini terbuka) dan saat pratinjau di bawah. Pilihan tersimpan di perangkat ini.</p>
    </div>
    <div id="adzanVoiceList" class="divide-y divide-[#F1F5F4]"></div>
    <p id="adzanVoiceStatus" class="px-4 py-2 text-[11px] text-[#607072]"></p>
  </div>

  <!-- 6. Lanjut ke kajian -->
  <a href="{{ route('kajian.index') }}" class="block rounded-2xl bg-amber-400 text-[#1D4D4F] p-4 shadow-2xs">
    <p class="text-sm font-black">Selesai sholat, lanjut ngaji →</p>
    <p class="text-[11px] font-medium mt-0.5">Lihat kajian Sunnah terdekat dari lokasi Anda hari ini.</p>
  </a>
</div>
@endsection

@section('scripts')
<script>
  var SHOLAT_TIMINGS = @json($timings);
  var SHOLAT_CITY = @json($city);
  var SHOLAT_LAT = {{ $lat }};
  var SHOLAT_LON = {{ $lon }};
  var SHOLAT_LOGGED_IN = {{ $isLoggedIn ? 'true' : 'false' }};
  var CSRF_TOKEN_SHOLAT = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';
  var VAPID_PUBLIC_KEY_SHOLAT = '{{ config("services.vapid.public_key") }}';

  var PRAYER_ORDER = ['Subuh', 'Dzuhur', 'Ashar', 'Maghrib', 'Isya'];

  function sholatMinutes(t) {
    if (!t || t.indexOf(':') === -1) return null;
    var p = t.split(':');
    return parseInt(p[0], 10) * 60 + parseInt(p[1], 10);
  }

  function updateNextPrayer() {
    var now = new Date();
    var nowMin = now.getHours() * 60 + now.getMinutes();
    var next = null, nextMin = null;
    PRAYER_ORDER.forEach(function (name) {
      var m = sholatMinutes(SHOLAT_TIMINGS[name]);
      if (m !== null && m > nowMin && next === null) { next = name; nextMin = m; }
    });
    var nameEl = document.getElementById('nextPrayerName');
    var timeEl = document.getElementById('nextPrayerTime');
    var cdEl = document.getElementById('nextPrayerCountdown');
    var noteEl = document.getElementById('nextPrayerNote');
    if (!next) {
      nameEl.textContent = 'Subuh (besok)';
      timeEl.textContent = SHOLAT_TIMINGS['Subuh'] || '—';
      cdEl.textContent = '—';
      noteEl.textContent = 'Semua sholat hari ini sudah lewat.';
      markPrayerRows(null);
      return;
    }
    nameEl.textContent = next;
    timeEl.textContent = SHOLAT_TIMINGS[next];
    var diff = nextMin - nowMin;
    var h = Math.floor(diff / 60), m = diff % 60;
    cdEl.textContent = '−' + h + ' jam ' + (m < 10 ? '0' : '') + m + ' menit lagi';
    noteEl.textContent = 'Waktu ' + next + ' ' + SHOLAT_TIMINGS[next] + ' WIB';
    markPrayerRows(next);
  }

  function markPrayerRows(next) {
    document.querySelectorAll('.prayer-row').forEach(function (row) {
      var isNext = row.getAttribute('data-prayer') === next;
      row.classList.toggle('bg-amber-50', isNext);
      row.querySelector('span:first-child').classList.toggle('text-amber-700', isNext);
    });
  }

  function renderTimings(timings) {
    SHOLAT_TIMINGS = timings;
    var list = document.getElementById('prayerList');
    var order = ['Imsak', 'Subuh', 'Terbit', 'Dzuhur', 'Ashar', 'Maghrib', 'Isya'];
    list.innerHTML = order.filter(function (n) { return timings[n]; }).map(function (name) {
      return '<div data-prayer="' + name + '" class="prayer-row flex items-center justify-between px-4 py-3 border-b border-[#F1F5F4] last:border-b-0">'
        + '<span class="text-sm font-bold text-[#1C2224]">' + name + '</span>'
        + '<span class="text-sm font-mono font-bold text-[#1D4D4F]">' + timings[name] + '</span></div>';
    }).join('');
    updateNextPrayer();
  }

  async function refreshSholatGps() {
    var locEl = document.getElementById('sholatLocationName');
    locEl.textContent = 'Mendeteksi lokasi...';
    function apply(lat, lon, name) {
      SHOLAT_LAT = lat; SHOLAT_LON = lon;
      document.getElementById('sholatCoordinates').textContent = lat.toFixed(4) + ', ' + lon.toFixed(4);
      if (name) { SHOLAT_CITY = name; locEl.textContent = name; }
      fetch('/api/sholat?lat=' + lat + '&lon=' + lon)
        .then(function (r) { return r.json(); })
        .then(function (d) { if (d.timings) renderTimings(d.timings); if (!name && d.city) locEl.textContent = d.city; })
        .catch(function () { locEl.textContent = SHOLAT_CITY; });
    }
    try {
      if (window.SiniKajianGeo) {
        var c = window.SiniKajianGeo.getCoords();
        if (c && c.lat && c.lon) { apply(c.lat, c.lon, c.name); return; }
        window.SiniKajianGeo.requestLiveGps();
        setTimeout(function () {
          var c2 = window.SiniKajianGeo.getCoords();
          if (c2 && c2.lat && c2.lon) apply(c2.lat, c2.lon, c2.name);
          else locEl.textContent = SHOLAT_CITY;
        }, 2500);
        return;
      }
      throw new Error('no-geo-engine');
    } catch (e) {
      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function (pos) {
          apply(pos.coords.latitude, pos.coords.longitude, 'Lokasi GPS Saya');
        }, function () { locEl.textContent = SHOLAT_CITY; }, { timeout: 8000 });
      } else {
        locEl.textContent = SHOLAT_CITY;
      }
    }
  }

  function urlBase64ToUint8ArraySholat(base64String) {
    var padding = '='.repeat((4 - base64String.length % 4) % 4);
    var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    var rawData = window.atob(base64);
    var outputArray = new Uint8Array(rawData.length);
    for (var i = 0; i < rawData.length; ++i) outputArray[i] = rawData.charCodeAt(i);
    return outputArray;
  }

  // --- Diagnosa pengingat push (2026-10-28): tiap kegagalan mengembalikan
  // alasan + pesan solusi yang spesifik, bukan sekadar "tidak bisa".
  // Gejala pemicu: ketuk toggle -> status "Aktifkan izin notifikasi dulu"
  // -> tombol tetap Mati, tanpa petunjuk apa yang salah.
  function pushSupportInfo() {
    var ua = navigator.userAgent || '';
    var isIOS = /iPhone|iPad|iPod/.test(ua) && !window.MSStream;
    var isStandalone = (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) || window.navigator.standalone === true;
    var inApp = /FBAN|FBAV|Instagram|WhatsApp|Line\/|MiuiBrowser|VivoBrowser/.test(ua);
    return { isIOS: isIOS, isStandalone: isStandalone, inApp: inApp };
  }

  function requestPermissionCompat() {
    // iOS lama: requestPermission callback-based, bukan Promise.
    try {
      var p = Notification.requestPermission();
      if (p && typeof p.then === 'function') return p;
    } catch (e) {}
    return new Promise(function (resolve) {
      try { Notification.requestPermission(function (res) { resolve(res); }); }
      catch (e2) { resolve(Notification.permission || 'default'); }
    });
  }

  var PREF_MESSAGES = {
    'unsupported': 'Browser ini tidak mendukung notifikasi push. Coba Chrome terbaru di Android, atau Safari 16.4+ (iPhone) setelah memasang Majelisku ke layar utama.',
    'in-app': 'Kamu membuka Majelisku dari dalam aplikasi lain (mis. WhatsApp) — notifikasi tidak bisa diaktifkan dari sini. Buka majelisku.web.id langsung di Chrome/Safari, lalu aktifkan pengingat.',
    'ios-not-installed': 'Di iPhone, pengingat hanya bisa aktif bila Majelisku dipasang ke layar utama. Di Safari ketuk tombol Bagikan, pilih "Tambah ke Layar Utama", buka Majelisku dari ikonnya, lalu aktifkan pengingat di sini.',
    'denied': 'Izin notifikasi untuk Majelisku sedang diblokir di browser ini. Ketuk ikon gembok di address bar, pilih Izin situs, ubah Notifikasi menjadi Izinkan, lalu muat ulang halaman dan coba lagi.',
    'dismissed': 'Izin notifikasi belum diberikan. Ketuk lagi tombol pengingat, lalu pilih "Izinkan" saat browser bertanya.',
    'subscribe-failed': 'Gagal mendaftarkan perangkat untuk notifikasi. Periksa koneksi, lalu coba lagi. Bila tetap gagal, tutup dan buka lagi Majelisku.',
    'server': 'Gagal menyimpan pengingat di server. Coba lagi sebentar lagi.'
  };

  async function ensurePushSholat() {
    // return {ok:true} atau {ok:false, reason:'...'}
    if (!('Notification' in window) || !('serviceWorker' in navigator) || !('PushManager' in window)) {
      return { ok: false, reason: 'unsupported' };
    }
    var info = pushSupportInfo();
    if (info.inApp) return { ok: false, reason: 'in-app' };
    if (info.isIOS && !info.isStandalone) {
      // iOS Safari: web push hanya untuk PWA yang dipasang ke layar utama.
      return { ok: false, reason: 'ios-not-installed' };
    }
    if (Notification.permission === 'denied') return { ok: false, reason: 'denied' };
    var permission = Notification.permission;
    if (permission !== 'granted') {
      permission = await requestPermissionCompat();
      if (permission !== 'granted') {
        return { ok: false, reason: permission === 'denied' ? 'denied' : 'dismissed' };
      }
    }
    try {
      var reg = await navigator.serviceWorker.ready;
      var sub = await reg.pushManager.getSubscription();
      if (!sub) {
        sub = await reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: urlBase64ToUint8ArraySholat(VAPID_PUBLIC_KEY_SHOLAT) });
      }
      var res = await fetch('/api/push/subscribe', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN_SHOLAT },
        body: JSON.stringify(sub.toJSON())
      });
      if (!res.ok) return { ok: false, reason: 'server' };
      return { ok: true };
    } catch (e) {
      console.error('Push subscribe gagal:', e);
      return { ok: false, reason: 'subscribe-failed' };
    }
  }

  function showPrefStatus(msg, isError) {
    var statusEl = document.getElementById('prefStatus');
    if (!statusEl) return;
    statusEl.textContent = msg;
    statusEl.className = isError
      ? 'px-4 py-3 text-[11px] bg-amber-50 text-amber-700'
      : 'px-4 py-2 text-[11px] text-[#607072]';
  }

  async function togglePref(btn) {
    if (!SHOLAT_LOGGED_IN) return;
    var key = btn.getAttribute('data-pref');
    var turningOn = btn.textContent.trim() === 'Mati';
    if (turningOn) {
      btn.disabled = true;
      btn.textContent = '…';
      var r = await ensurePushSholat();
      btn.disabled = false;
      if (!r.ok) {
        btn.textContent = 'Mati';
        showPrefStatus(PREF_MESSAGES[r.reason] || PREF_MESSAGES['subscribe-failed'], true);
        return;
      }
    }
    btn.textContent = turningOn ? 'Aktif' : 'Mati';
    btn.classList.toggle('bg-[#1D4D4F]', turningOn);
    btn.classList.toggle('text-white', turningOn);
    btn.classList.toggle('bg-[#F1F5F4]', !turningOn);
    btn.classList.toggle('text-[#607072]', !turningOn);
    try {
      var body = { city: SHOLAT_CITY, lat: SHOLAT_LAT, lon: SHOLAT_LON };
      body[key] = turningOn;
      var res = await fetch('/api/notif-prefs', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN_SHOLAT },
        body: JSON.stringify(body)
      });
      if (!res.ok) {
        var d = await res.json().catch(function () { return {}; });
        showPrefStatus((d && d.error) || 'Gagal menyimpan pengingat.', true);
        btn.textContent = turningOn ? 'Mati' : 'Aktif';
        btn.classList.toggle('bg-[#1D4D4F]', !turningOn);
        btn.classList.toggle('text-white', !turningOn);
        btn.classList.toggle('bg-[#F1F5F4]', turningOn);
        btn.classList.toggle('text-[#607072]', turningOn);
      } else {
        showPrefStatus(turningOn ? 'Pengingat diaktifkan.' : 'Pengingat dimatikan.', false);
      }
    } catch (e) {
      showPrefStatus('Gagal menyimpan pengingat.', true);
    }
  }

  // Petunjuk dini (tanpa menunggu ketuk): bila izin sudah diblokir atau
  // perangkat belum memenuhi syarat, tampilkan alasannya di bawah toggle.
  (function prefHintOnLoad() {
    if (!SHOLAT_LOGGED_IN) return;
    if (!('Notification' in window)) { showPrefStatus(PREF_MESSAGES['unsupported'], true); return; }
    var info = pushSupportInfo();
    if (info.inApp) { showPrefStatus(PREF_MESSAGES['in-app'], true); return; }
    if (info.isIOS && !info.isStandalone) { showPrefStatus(PREF_MESSAGES['ios-not-installed'], true); return; }
    if (Notification.permission === 'denied') { showPrefStatus(PREF_MESSAGES['denied'], true); return; }
  })();

    // --- Pilihan suara adzan (2026-10-29): 24 suara terverifikasi.
  // Aladhan (6) + muazin Masjidil Haram via archive.org (13) +
  // nada/maqam Ali Mullah (5). a11 Aladhan (Mansour Al-Zahrani)
  // MATI 403 per 2026-10-28 — diganti Ahmed Basnawi (Makkah).
  // Suara diputar dari CDN saat pratinjau / waktu sholat tiba;
  // notifikasi push tetap memakai bunyi default perangkat.
  var ADZAN_BASE_ARCHIVE = 'https://archive.org/download/MuadhinsMakkah/';
  var ADZAN_VOICES = [
    { id: 'a9', group: 'Aladhan', name: 'Mishary Rashid Alafasy', note: 'Nada klasik — paling dikenal', url: 'https://cdn.aladhan.com/audio/adhans/a9.mp3' },
    { id: 'a1', group: 'Aladhan', name: 'Ahmad al-Nafees', note: 'Aladhan', url: 'https://cdn.aladhan.com/audio/adhans/a1.mp3' },
    { id: 'a2', group: 'Aladhan', name: 'Hafiz Mustafa Özcan', note: 'Muazin Turki', url: 'https://cdn.aladhan.com/audio/adhans/a2.mp3' },
    { id: 'a3', group: 'Aladhan', name: 'Karl Jenkins', note: 'Mass for Peace', url: 'https://cdn.aladhan.com/audio/adhans/a3.mp3' },
    { id: 'a4', group: 'Aladhan', name: 'Mishary Rashid Alafasy', note: 'Versi Dubai One TV', url: 'https://cdn.aladhan.com/audio/adhans/a4.mp3' },
    { id: 'a7', group: 'Aladhan', name: 'Mishary Rashid Alafasy', note: 'Versi lain', url: 'https://cdn.aladhan.com/audio/adhans/a7.mp3' },
    { id: 'm-ali-mullah', group: 'Muazin Masjidil Haram', name: 'Ali Ahmed Mullah', note: 'Masjidil Haram, Makkah', url: ADZAN_BASE_ARCHIVE + 'ADHAAN/Sheikh%20Ali%20Mullah/Ali%20Ahmed%20Mullah.mp3' },
    { id: 'm-basnawi', group: 'Muazin Masjidil Haram', name: 'Ahmed Abdullah Basnawi', note: 'Masjidil Haram, Makkah', url: ADZAN_BASE_ARCHIVE + 'ADHAAN/Sheikh%20Ahmed%20Basnawi/Ahmed%20Abdullah%20Basnawi.mp3' },
    { id: 'm-naif', group: 'Muazin Masjidil Haram', name: 'Naif Saleh Faydah', note: 'Masjidil Haram, Makkah', url: ADZAN_BASE_ARCHIVE + 'ADHAAN/Sheikh%20Naif%20Faydah/Naif%20Saleh%20Faydah.mp3' },
    { id: 'm-majid', group: 'Muazin Masjidil Haram', name: 'Majid Ibrahim Abbas', note: 'Masjidil Haram, Makkah', url: ADZAN_BASE_ARCHIVE + 'ADHAAN/Sheikh%20Majid%20Abbas/Majid%20Ibrahim%20Abbas.mp3' },
    { id: 'm-maghribi', group: 'Muazin Masjidil Haram', name: 'Muhammad Maghribi', note: 'Masjidil Haram, Makkah', url: ADZAN_BASE_ARCHIVE + 'ADHAAN/Sheikh%20Muhammad%20Maghribi/Muhammad%20Maghribi.mp3' },
    { id: 'm-hamd', group: 'Muazin Masjidil Haram', name: 'Hamd Daghreeree', note: 'Masjidil Haram, Makkah', url: ADZAN_BASE_ARCHIVE + 'ADHAAN/Sheikh%20Hamd%20Daghreeree/Hamd%20Daghreeree.mp3' },
    { id: 'm-hashim', group: 'Muazin Masjidil Haram', name: 'Hashim Muhammad Saqaf', note: 'Masjidil Haram, Makkah', url: ADZAN_BASE_ARCHIVE + 'ADHAAN/Sheikh%20Hashim%20Saqaf/Hashim%20Muhammad%20Saqaf.mp3' },
    { id: 'm-hussain', group: 'Muazin Masjidil Haram', name: 'Hussain Hassan Shahat', note: 'Masjidil Haram, Makkah', url: ADZAN_BASE_ARCHIVE + 'ADHAAN/Sheikh%20Hussain%20Shahat/Hussain%20Hassan%20Shahat.mp3' },
    { id: 'm-emad', group: 'Muazin Masjidil Haram', name: 'Emad Ismaeel Baqree', note: 'Masjidil Haram, Makkah', url: ADZAN_BASE_ARCHIVE + 'ADHAAN/Sheikh%20Emad%20Baqree/Emad%20Ismaeel%20Baqree.mp3' },
    { id: 'm-khoja', group: 'Muazin Masjidil Haram', name: 'Ahmed Yunus Khoja', note: 'Masjidil Haram, Makkah', url: ADZAN_BASE_ARCHIVE + 'ADHAAN/Sheikh%20Ahmed%20Khoja/Ahmed%20Yunus%20Khoja.mp3' },
    { id: 'm-saeed', group: 'Muazin Masjidil Haram', name: 'Saeed Umar Fullatah', note: 'Masjidil Haram, Makkah', url: ADZAN_BASE_ARCHIVE + 'ADHAAN/Sheikh%20Saeed%20Fullatah/Saeed%20Umar%20Fullatah.mp3' },
    { id: 'm-suhail', group: 'Muazin Masjidil Haram', name: 'Suhail Abdul Malik Hafiz', note: 'Masjidil Haram, Makkah', url: ADZAN_BASE_ARCHIVE + 'ADHAAN/Sheikh%20Suhail%20Hafiz/Suhail%20Abdul%20Malik%20Hafiz.mp3' },
    { id: 'm-taufiq', group: 'Muazin Masjidil Haram', name: 'Taufiq Abdul Hafeez Koj', note: 'Masjidil Haram, Makkah', url: ADZAN_BASE_ARCHIVE + 'ADHAAN/Sheikh%20Taufiq%20Koj/Taufiq%20Abdul%20Hafeez%20Koj.mp3' },
    { id: 'q-hijaz-kar', group: 'Nada & Maqam — Ali Mullah', name: 'Hijaz Kar', note: 'Maqam Hijaz', url: ADZAN_BASE_ARCHIVE + 'Tableegh%20Styles/Sheikh%20Ali%20Mullah/Hijaz%20Kar.mp3' },
    { id: 'q-sika', group: 'Nada & Maqam — Ali Mullah', name: 'Sika', note: 'Maqam Sika', url: ADZAN_BASE_ARCHIVE + 'Tableegh%20Styles/Sheikh%20Ali%20Mullah/Sika.mp3' },
    { id: 'q-husaini', group: 'Nada & Maqam — Ali Mullah', name: 'Husaini', note: 'Maqam Husaini', url: ADZAN_BASE_ARCHIVE + 'Tableegh%20Styles/Sheikh%20Ali%20Mullah/Husaini.mp3' },
    { id: 'q-jaharka', group: 'Nada & Maqam — Ali Mullah', name: 'Jaharka', note: 'Maqam Jaharka', url: ADZAN_BASE_ARCHIVE + 'Tableegh%20Styles/Sheikh%20Ali%20Mullah/Jaharka.mp3' },
    { id: 'q-maya', group: 'Nada & Maqam — Ali Mullah', name: 'Maya', note: 'Maqam Maya', url: ADZAN_BASE_ARCHIVE + 'Tableegh%20Styles/Sheikh%20Ali%20Mullah/Maya.mp3' }
  ];

  var ADZAN_VOICE_KEY = 'majelisku_adzan_voice';
  var ADZAN_DEFAULT = 'a9';
  var adzanAudio = null;
  var adzanPlayingId = null;

  function adzanVoiceId() {
    try {
      var v = localStorage.getItem(ADZAN_VOICE_KEY);
      if (v && ADZAN_VOICES.some(function (x) { return x.id === v; })) return v;
    } catch (e) {}
    return ADZAN_DEFAULT;
  }

  function adzanVoiceById(id) {
    for (var i = 0; i < ADZAN_VOICES.length; i++) {
      if (ADZAN_VOICES[i].id === id) return ADZAN_VOICES[i];
    }
    return ADZAN_VOICES[0];
  }

  function adzanStop() {
    if (adzanAudio) { try { adzanAudio.pause(); } catch (e) {} }
    adzanPlayingId = null;
    document.querySelectorAll('.voice-play-btn').forEach(function (b) { b.textContent = '▶'; });
  }

  function renderAdzanVoices() {
    var list = document.getElementById('adzanVoiceList');
    if (!list) return;
    var sel = adzanVoiceId();
    var html = '';
    var lastGroup = '';
    ADZAN_VOICES.forEach(function (v) {
      if (v.group !== lastGroup) {
        html += '<div class="px-4 pt-3 pb-1 text-[11px] font-bold text-[#607072]">' + v.group + '</div>';
        lastGroup = v.group;
      }
      var isSel = v.id === sel;
      html += '<div class="flex items-center gap-2.5 px-4 py-2.5 cursor-pointer" onclick="selectAdzanVoice(\'' + v.id + '\')">'
        + '<button onclick="event.stopPropagation(); previewAdzan(\'' + v.id + '\')" class="voice-play-btn w-9 h-9 rounded-full bg-[#E8F5F3] text-[#1D4D4F] flex items-center justify-center shrink-0 text-sm font-bold" data-voice="' + v.id + '">▶</button>'
        + '<div class="min-w-0 flex-1"><p class="text-sm font-bold text-[#1C2224] truncate">' + v.name + '</p><p class="text-[11px] text-[#607072] truncate">' + v.note + '</p></div>'
        + (isSel ? '<span class="text-[11px] font-bold text-[#1D4D4F] px-2 py-0.5 rounded-full shrink-0" style="background:#E8F5F3">✓ Dipakai</span>' : '')
        + '</div>';
    });
    list.innerHTML = html;
  }

  function previewAdzan(id) {
    var v = adzanVoiceById(id);
    if (!adzanAudio) {
      adzanAudio = new Audio();
      adzanAudio.preload = 'none';
      adzanAudio.addEventListener('ended', adzanStop);
      adzanAudio.addEventListener('error', function () {
        var st = document.getElementById('adzanVoiceStatus');
        if (st) st.textContent = 'Suara tidak dapat diputar saat ini. Periksa koneksi lalu coba lagi.';
        adzanStop();
      });
    }
    if (adzanPlayingId === id && !adzanAudio.paused) { adzanStop(); return; }
    adzanStop();
    adzanAudio.src = v.url;
    adzanPlayingId = id;
    var btn = document.querySelector('.voice-play-btn[data-voice="' + id + '"]');
    if (btn) btn.textContent = '⏸';
    adzanAudio.play().catch(function () {
      var st = document.getElementById('adzanVoiceStatus');
      if (st) st.textContent = 'Suara tidak dapat diputar saat ini. Periksa koneksi lalu coba lagi.';
      adzanStop();
    });
  }

  function selectAdzanVoice(id) {
    try { localStorage.setItem(ADZAN_VOICE_KEY, id); } catch (e) {}
    renderAdzanVoices();
    var v = adzanVoiceById(id);
    var st = document.getElementById('adzanVoiceStatus');
    if (st) st.textContent = 'Suara adzan: ' + v.name + ' (' + v.group + '). Tersimpan di perangkat ini.';
  }

  // Putar adzan saat waktu sholat tiba (halaman terbuka + pengingat
  // adzan Aktif). Sekali per sholat per hari.
  function checkAdzanAutoPlay() {
    var adzanBtn = document.querySelector('[data-pref="adzan"]');
    if (!adzanBtn || adzanBtn.textContent.trim() !== 'Aktif') return;
    var now = new Date();
    var hhmm = ('0' + now.getHours()).slice(-2) + ':' + ('0' + now.getMinutes()).slice(-2);
    var today = now.getFullYear() + '-' + ('0' + (now.getMonth() + 1)).slice(-2) + '-' + ('0' + now.getDate()).slice(-2);
    PRAYER_ORDER.forEach(function (name) {
      if (SHOLAT_TIMINGS[name] !== hhmm) return;
      var playedKey = 'majelisku_adzan_played_' + today + '_' + name;
      try {
        if (localStorage.getItem(playedKey)) return;
        localStorage.setItem(playedKey, '1');
      } catch (e) {}
      var v = adzanVoiceById(adzanVoiceId());
      if (!adzanAudio) {
        adzanAudio = new Audio();
        adzanAudio.preload = 'none';
        adzanAudio.addEventListener('ended', adzanStop);
      }
      adzanStop();
      adzanAudio.src = v.url;
      adzanPlayingId = v.id;
      adzanAudio.play().catch(function () {});
    });
  }

  renderAdzanVoices();
  setInterval(checkAdzanAutoPlay, 15000);

  updateNextPrayer();
  setInterval(updateNextPrayer, 30000);
  // Sesuaikan dengan GPS perangkat bila tersedia (tanpa reload).
  document.addEventListener('sk:location-updated', function (e) {
    if (e.detail && e.detail.lat && e.detail.lon) {
      fetch('/api/sholat?lat=' + e.detail.lat + '&lon=' + e.detail.lon)
        .then(function (r) { return r.json(); })
        .then(function (d) {
          if (d.timings) renderTimings(d.timings);
          if (e.detail.name) document.getElementById('sholatLocationName').textContent = e.detail.name;
        })
        .catch(function () {});
    }
  });
</script>
@endsection
