@extends('layouts.app')

@section('title', 'Dzikir Pagi & Petang - Majelisku')

@section('meta_description', 'Dzikir pagi dan petang sesuai sunnah dari buku Ustadz Yazid bin Abdul Qadir Jawas — lengkap dengan teks Arab, latin, arti, dalil, penghitung poin, streak, dan kalender aktivitas.')

@section('styles')
<style>
  /* Float Fokus Card: 1 kartu aktif mengambang tanpa tumpukan bayangan */
  .dzikir-stage {
    position: relative;
    width: 100%;
    min-height: 320px;
    touch-action: pan-y;
    -webkit-user-select: none;
    user-select: none;
  }
  .dzikir-float-card {
    display: none;
    width: 100%;
    border-radius: 28px;
    background: #ffffff;
    border: 1px solid #E5ECEB;
    box-shadow: 0 16px 36px -6px rgba(29, 77, 79, 0.12), 0 4px 12px -2px rgba(29, 77, 79, 0.05);
    overflow: hidden;
    position: relative;
    will-change: transform, opacity;
  }
  .dzikir-float-card.active {
    display: block;
  }
  .dzikir-float-card.animating {
    transition: transform 0.26s cubic-bezier(0.2, 0.8, 0.3, 1), opacity 0.26s ease;
  }
  .swipe-stamp {
    position: absolute;
    top: 18px;
    padding: 6px 14px;
    border-radius: 9999px;
    font-size: 12px;
    font-weight: 900;
    letter-spacing: 1px;
    opacity: 0;
    pointer-events: none;
    z-index: 25;
    background: rgba(255, 255, 255, 0.96);
    box-shadow: 0 4px 14px rgba(0,0,0,0.12);
    transition: opacity 0.12s ease;
  }
  .swipe-stamp.stamp-right {
    right: 18px;
    color: #1D4D4F;
    border: 2.5px solid #1D4D4F;
    transform: rotate(8deg);
  }
  .swipe-stamp.stamp-left {
    left: 18px;
    color: #B45309;
    border: 2.5px solid #B45309;
    transform: rotate(-8deg);
  }
  .dzikir-navbtn {
    width: 44px;
    height: 44px;
    border-radius: 9999px;
    flex-shrink: 0;
    background: #ffffff;
    border: 1px solid #E5ECEB;
    color: #1D4D4F;
    font-size: 22px;
    font-weight: 800;
    line-height: 1;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 1px 3px rgba(29, 77, 79, 0.08);
  }
  .dzikir-navbtn:active { background: #E8F5F3; }
  .dzikir-navbtn:disabled { opacity: 0.35; cursor: default; }

  /* Feedback popup saat geser kanan menambah poin */
  .point-toast {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) scale(0.6);
    background: #1D4D4F;
    color: #ffffff;
    padding: 8px 18px;
    border-radius: 9999px;
    font-size: 14px;
    font-weight: 900;
    box-shadow: 0 8px 24px rgba(29, 77, 79, 0.3);
    opacity: 0;
    pointer-events: none;
    z-index: 40;
    transition: transform 0.25s cubic-bezier(0.18, 0.89, 0.32, 1.28), opacity 0.25s ease;
  }
  .point-toast.show {
    transform: translate(-50%, -50%) scale(1.05);
    opacity: 1;
  }
</style>
@endsection

@section('content')
<div class="max-w-md mx-auto space-y-3.5 pb-24 pt-1">

  <!-- 1. Header -->
  <header>
    <h1 class="text-xl font-extrabold text-[#1D4D4F] tracking-tight">Dzikir Pagi &amp; Petang</h1>
    <p class="text-xs text-[#607072] mt-0.5">Pagi: setelah Subuh hingga matahari terbit &middot; Petang: setelah Ashar hingga matahari terbenam.</p>
    <p class="text-[11px] text-[#607072] mt-1">Dari buku &ldquo;Dzikir Pagi dan Petang dan Sesudah Shalat Fardhu menurut Al-Qur&rsquo;an dan As-Sunnah yang Shahih&rdquo; &mdash; Ustadz Yazid bin Abdul Qadir Jawas <span class="text-[#1D4D4F] font-bold">rahimahullah</span>.</p>
  </header>

  <!-- 2. Pemberitahuan Notifikasi Belum Baca Dzikir Pagi/Petang -->
  <div id="dzikirPeriodAlert" class="rounded-2xl p-3.5 bg-amber-50/90 border border-amber-200 text-amber-900 flex items-start gap-3 shadow-2xs transition-all">
    <span class="text-xl leading-none mt-0.5">🔔</span>
    <div class="flex-1 min-w-0">
      <div class="flex items-center justify-between gap-1">
        <h4 class="text-xs font-extrabold uppercase tracking-wide text-amber-800">Pengingat Waktu Dzikir</h4>
        <span id="periodBadgeText" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-200 text-amber-900">Belum Selesai</span>
      </div>
      <p id="periodDescText" class="text-xs font-medium text-amber-800 mt-1 leading-relaxed">
        Memeriksa status dzikir hari ini...
      </p>
    </div>
  </div>

  <!-- 3. Point Streak & Kalender Dzikir (Hari Berlangsung & Lampau) -->
  <div class="rounded-3xl bg-white border border-[#E5ECEB] shadow-2xs p-4 space-y-3">
    <!-- Baris Atas: Streak & Total Poin -->
    <div class="flex items-center justify-between gap-2">
      <div class="flex items-center gap-2.5">
        <span class="w-10 h-10 rounded-2xl bg-amber-50 border border-amber-200/60 text-amber-600 flex items-center justify-center text-xl font-black shadow-2xs">🔥</span>
        <div>
          <span class="text-xs font-bold text-[#1C2224] block leading-tight">Streak Dzikir</span>
          <span id="streakDaysLabel" class="text-[11px] font-extrabold text-[#1D4D4F] block">0 Hari Beruntun</span>
        </div>
      </div>
      <div class="flex items-center gap-1.5 px-3.5 py-2 rounded-2xl bg-[#E8F5F3] text-[#1D4D4F] border border-[#D1ECE7] shadow-2xs">
        <span class="text-sm">⭐</span>
        <span id="totalPointsLabel" class="text-xs font-black">0 Poin</span>
      </div>
    </div>

    <!-- Baris Bawah: Kalender 7 Hari Aktivitas (Sudah/Belum di Hari Berlangsung & Lampau) -->
    <div class="pt-2.5 border-t border-[#F1F5F4]">
      <div class="flex items-center justify-between mb-2 px-0.5">
        <span class="text-[11px] font-bold text-[#607072]">Kalender Dzikir (7 Hari Terakhir)</span>
        <span id="weekSummaryLabel" class="text-[10px] font-extrabold text-[#1D4D4F]">0/7 hari tuntas</span>
      </div>
      <div id="dzikirWeeklyCalendar" class="grid grid-cols-7 gap-1.5 text-center">
        <!-- Diisi otomatis oleh renderDzikirCalendar() -->
      </div>
    </div>
  </div>

  <!-- 4. Tab Pagi / Petang -->
  <div class="flex gap-2">
    <button id="tabPagi" onclick="setDzikirTab('pagi')" class="flex-1 py-2.5 rounded-xl text-sm font-bold transition bg-[#1D4D4F] text-white">☀️ Pagi ({{ count($pagi) }})</button>
    <button id="tabPetang" onclick="setDzikirTab('petang')" class="flex-1 py-2.5 rounded-xl text-sm font-bold transition bg-[#F1F5F4] text-[#607072]">🌙 Petang ({{ count($petang) }})</button>
  </div>

  <!-- 5. Progres Bacaan Hari Ini -->
  <div class="rounded-2xl bg-[#E8F5F3] border border-[#D1ECE7] px-4 py-3 flex items-center justify-between gap-3 shadow-2xs">
    <p id="dzikirProgress" class="text-sm font-bold text-[#1D4D4F]">0 dari {{ count($pagi) }} selesai</p>
    <button onclick="resetDzikirToday()" class="text-xs font-bold text-[#607072] hover:underline shrink-0">Reset hari ini ↺</button>
  </div>

  <!-- 6. Float Fokus Card: Dzikir Pagi -->
  <div id="deckPagi" class="dzikir-deck" data-waktu="pagi">
    <div class="flex items-center justify-between mb-2.5 px-0.5">
      <button type="button" class="dzikir-navbtn dzikir-prev" aria-label="Sebelumnya">‹</button>
      <div class="text-center">
        <span class="dzikir-pos text-sm font-extrabold text-[#1D4D4F]">1 / {{ count($pagi) }}</span>
        <span class="text-[10px] text-[#607072] block font-medium">☀️ Dzikir Pagi</span>
      </div>
      <button type="button" class="dzikir-navbtn dzikir-next" aria-label="Berikutnya">›</button>
    </div>
    
    <div class="dzikir-stage" id="stagePagi">
      <div class="point-toast" id="toastPagi">+1 Poin!</div>
      @foreach($pagi as $i => $item)
        <div class="dzikir-float-card @if($i === 0) active @endif" data-slide-idx="{{ $i }}">
          <div class="swipe-stamp stamp-right">+1 POIN &rarr;</div>
          <div class="swipe-stamp stamp-left">&larr; KEMBALI</div>
          @include('kajian.partials.dzikir-item', ['item' => $item, 'waktu' => 'pagi', 'idx' => $i])
        </div>
      @endforeach
    </div>
    <p class="text-center text-[11px] text-[#8a979b] mt-3">👉 Geser kanan = point bertambah otomatis &bull; 👈 Geser kiri = kembali</p>
  </div>

  <!-- 7. Float Fokus Card: Dzikir Petang -->
  <div id="deckPetang" class="dzikir-deck" data-waktu="petang" style="display:none">
    <div class="flex items-center justify-between mb-2.5 px-0.5">
      <button type="button" class="dzikir-navbtn dzikir-prev" aria-label="Sebelumnya">‹</button>
      <div class="text-center">
        <span class="dzikir-pos text-sm font-extrabold text-[#1D4D4F]">1 / {{ count($petang) }}</span>
        <span class="text-[10px] text-[#607072] block font-medium">🌙 Dzikir Petang</span>
      </div>
      <button type="button" class="dzikir-navbtn dzikir-next" aria-label="Berikutnya">›</button>
    </div>

    <div class="dzikir-stage" id="stagePetang">
      <div class="point-toast" id="toastPetang">+1 Poin!</div>
      @foreach($petang as $i => $item)
        <div class="dzikir-float-card @if($i === 0) active @endif" data-slide-idx="{{ $i }}">
          <div class="swipe-stamp stamp-right">+1 POIN &rarr;</div>
          <div class="swipe-stamp stamp-left">&larr; KEMBALI</div>
          @include('kajian.partials.dzikir-item', ['item' => $item, 'waktu' => 'petang', 'idx' => $i])
        </div>
      @endforeach
    </div>
    <p class="text-center text-[11px] text-[#8a979b] mt-3">👉 Geser kanan = point bertambah otomatis &bull; 👈 Geser kiri = kembali</p>
  </div>

  <!-- 8. Riwayat Bacaan -->
  <details class="rounded-2xl bg-white border border-[#E5ECEB] px-4 py-3 shadow-2xs">
    <summary class="text-sm font-bold text-[#1D4D4F] cursor-pointer">Riwayat bacaan harian</summary>
    <div id="dzikirHistory" class="mt-2.5"></div>
  </details>

  <!-- 9. Navigasi Cepat -->
  <div class="flex gap-2 pt-1">
    <a href="{{ route('kajian.sholat') }}" class="flex-1 block text-center rounded-2xl bg-white border border-[#E5ECEB] p-3 text-sm font-bold text-[#1D4D4F] shadow-2xs hover:border-[#1D4D4F]/40 active:scale-95 transition">← Jadwal Sholat</a>
    <a href="{{ route('kajian.index') }}" class="flex-1 block text-center rounded-2xl bg-[#1D4D4F] text-white p-3 text-sm font-bold shadow-2xs hover:bg-[#153D3F] active:scale-95 transition">Lanjut ngaji →</a>
  </div>
</div>
@endsection

@section('scripts')
<script>
  var DZIKIR_KEY = 'majelisku_dzikir_v1';
  var DZIKIR_HIST_KEY = 'majelisku_dzikir_history_v1';
  var DZIKIR_POINTS_KEY = 'majelisku_dzikir_points_v1';
  var DZIKIR_TAB = 'pagi';
  var DZIKIR_TOTALS = { pagi: {{ count($pagi) }}, petang: {{ count($petang) }} };
  var DZIKIR_BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

  function dzikirTodayStr() {
    var d = new Date();
    return d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2);
  }

  function dzikirLoad() {
    try {
      var s = JSON.parse(localStorage.getItem(DZIKIR_KEY) || '{}');
      if (s.date !== dzikirTodayStr()) return { date: dzikirTodayStr(), pagi: {}, petang: {}, target: { pagi: {}, petang: {} } };
      s.pagi = s.pagi || {}; s.petang = s.petang || {}; s.target = s.target || { pagi: {}, petang: {} };
      return s;
    } catch (e) {
      return { date: dzikirTodayStr(), pagi: {}, petang: {}, target: { pagi: {}, petang: {} } };
    }
  }

  function dzikirSave(s) {
    try { localStorage.setItem(DZIKIR_KEY, JSON.stringify(s)); } catch (e) {}
    try {
      var done = 0, total = 0;
      ['pagi', 'petang'].forEach(function (waktu) {
        document.querySelectorAll('.dzikir-item[data-waktu="' + waktu + '"]').forEach(function (card) {
          var idx = parseInt(card.getAttribute('data-idx'), 10);
          var target = dzikirTarget(waktu, idx, parseInt(card.getAttribute('data-target'), 10));
          total++;
          if (parseInt((s[waktu] || {})[idx] || 0, 10) >= target) done++;
        });
      });
      localStorage.setItem('majelisku_dzikir_summary_v1', JSON.stringify({ date: s.date, done: done, total: total }));
    } catch (e2) {}
  }

  function dzikirHistLoad() {
    try { return JSON.parse(localStorage.getItem(DZIKIR_HIST_KEY) || '{}'); }
    catch (e) { return {}; }
  }

  function dzikirHistSave(h) {
    try { localStorage.setItem(DZIKIR_HIST_KEY, JSON.stringify(h)); } catch (e) {}
  }

  function dzikirPointsLoad() {
    try { return parseInt(localStorage.getItem(DZIKIR_POINTS_KEY) || '0', 10); }
    catch (e) { return 0; }
  }

  function dzikirPointsAdd(pts) {
    var p = dzikirPointsLoad() + pts;
    try { localStorage.setItem(DZIKIR_POINTS_KEY, String(p)); } catch (e) {}
    renderStreakAndPoints();
  }

  function dzikirEscapeHtml(s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function dzikirDayLabel(day) {
    var p = day.split('-');
    return p[2] + ' ' + DZIKIR_BULAN[parseInt(p[1], 10) - 1] + ' ' + p[0];
  }

  /* ===== Point Streak & Kalender 7 Hari ===== */
  function getDzikirStreak() {
    var history = dzikirHistLoad();
    var today = dzikirTodayStr();
    var streak = 0;
    var d = new Date();
    
    var hasToday = history[today] && history[today].length > 0;
    if (hasToday) {
      streak = 1;
      d.setDate(d.getDate() - 1);
    } else {
      d.setDate(d.getDate() - 1);
    }
    
    while (true) {
      var dateStr = d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2);
      if (history[dateStr] && history[dateStr].length > 0) {
        streak++;
        d.setDate(d.getDate() - 1);
      } else {
        break;
      }
    }
    return streak;
  }

  function renderStreakAndPoints() {
    var streak = getDzikirStreak();
    var points = dzikirPointsLoad();
    var sLabel = document.getElementById('streakDaysLabel');
    if (sLabel) sLabel.textContent = streak + ' Hari Beruntun';
    var pLabel = document.getElementById('totalPointsLabel');
    if (pLabel) pLabel.textContent = points + ' Poin';
  }

  function renderDzikirCalendar() {
    var container = document.getElementById('dzikirWeeklyCalendar');
    if (!container) return;
    var history = dzikirHistLoad();
    var daysName = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
    var html = '';
    var completedCount = 0;

    for (var i = 6; i >= 0; i--) {
      var d = new Date();
      d.setDate(d.getDate() - i);
      var dateStr = d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2);
      var dayName = daysName[d.getDay()];
      var dayNum = d.getDate();
      var isToday = (i === 0);
      var isDone = history[dateStr] && history[dateStr].length >= 3;
      if (isDone) completedCount++;

      if (isDone) {
        html += '<div class="flex flex-col items-center py-2 px-1 rounded-2xl bg-[#E8F5F3] border border-[#BDE3DC] text-[#1D4D4F] transition">'
          + '<span class="text-[10px] font-bold text-[#1D4D4F]/70">' + dayName + '</span>'
          + '<span class="text-xs font-black my-0.5">' + dayNum + '</span>'
          + '<span class="w-5 h-5 rounded-full bg-[#1D4D4F] text-white flex items-center justify-center text-[10px] font-black">✓</span>'
          + '</div>';
      } else if (isToday) {
        html += '<div class="flex flex-col items-center py-2 px-1 rounded-2xl bg-amber-50 border-2 border-amber-400 text-amber-900 transition">'
          + '<span class="text-[10px] font-bold text-amber-800">' + dayName + '</span>'
          + '<span class="text-xs font-black my-0.5">' + dayNum + '</span>'
          + '<span class="w-5 h-5 rounded-full bg-amber-200 text-amber-900 flex items-center justify-center text-[9px] font-extrabold">Hari ini</span>'
          + '</div>';
      } else {
        html += '<div class="flex flex-col items-center py-2 px-1 rounded-2xl bg-[#F8FAF9] border border-[#E5ECEB] text-[#A0AEC0] transition">'
          + '<span class="text-[10px] font-medium">' + dayName + '</span>'
          + '<span class="text-xs font-bold my-0.5 text-[#607072]">' + dayNum + '</span>'
          + '<span class="w-5 h-5 rounded-full bg-[#E5ECEB]/60 text-[#A0AEC0] flex items-center justify-center text-xs">&minus;</span>'
          + '</div>';
      }
    }

    container.innerHTML = html;
    var sumLabel = document.getElementById('weekSummaryLabel');
    if (sumLabel) sumLabel.textContent = completedCount + ' / 7 hari tuntas';
  }

  /* ===== Pemberitahuan Notif Belum Baca Dzikir Pagi/Petang ===== */
  function renderDzikirPeriodNotification() {
    var alertEl = document.getElementById('dzikirPeriodAlert');
    if (!alertEl) return;
    var h = new Date().getHours();
    var isMorning = (h >= 4 && h < 15);
    var currentWaktu = isMorning ? 'pagi' : 'petang';
    var waktuLabel = isMorning ? 'Dzikir Pagi' : 'Dzikir Petang';
    
    var done = 0;
    var total = DZIKIR_TOTALS[currentWaktu] || 0;
    document.querySelectorAll('.dzikir-item[data-waktu="' + currentWaktu + '"]').forEach(function (card) {
      var idx = parseInt(card.getAttribute('data-idx'), 10);
      var target = dzikirTarget(currentWaktu, idx, parseInt(card.getAttribute('data-target'), 10));
      if (parseInt(DZIKIR_STATE[currentWaktu][idx] || 0, 10) >= target) done++;
    });

    var isCompleted = (total > 0 && done >= total);
    if (isCompleted) {
      alertEl.className = 'rounded-2xl p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-900 flex items-start gap-3 shadow-2xs transition-all';
      alertEl.innerHTML = '<span class="text-xl leading-none mt-0.5">✨</span>'
        + '<div class="flex-1 min-w-0">'
        + '<div class="flex items-center justify-between gap-1">'
        + '<h4 class="text-xs font-extrabold uppercase tracking-wide text-emerald-800">Alhamdulillah!</h4>'
        + '<span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-200/80 text-emerald-900">Selesai ' + done + '/' + total + ' ✓</span>'
        + '</div>'
        + '<p class="text-xs font-medium text-emerald-800/90 mt-1">' + waktuLabel + ' hari ini sudah selesai dibaca. Semoga Allah menjaga dan memberkahi harimu.</p>'
        + '</div>';
    } else {
      alertEl.className = 'rounded-2xl p-3.5 bg-amber-50/90 border border-amber-200 text-amber-900 flex items-start gap-3 shadow-2xs transition-all';
      alertEl.innerHTML = '<span class="text-xl leading-none mt-0.5">🔔</span>'
        + '<div class="flex-1 min-w-0">'
        + '<div class="flex items-center justify-between gap-1">'
        + '<h4 class="text-xs font-extrabold uppercase tracking-wide text-amber-800">Pengingat Waktu Dzikir</h4>'
        + '<span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-200/80 text-amber-900">' + done + ' / ' + total + ' Selesai</span>'
        + '</div>'
        + '<p class="text-xs font-medium text-amber-800 mt-1">Kamu belum menyelesaikan <strong>' + waktuLabel + '</strong> hari ini. Yuk rutinkan dzikir untuk perlindungan diri.</p>'
        + '</div>';
    }
  }

  /* ===== Float Fokus Card Engine & Gestur Swipe ===== */
  var FLOAT_DECKS = {};
  var DZIKIR_STATE = dzikirLoad();

  function dzikirTarget(waktu, idx, defTarget) {
    var t = DZIKIR_STATE.target[waktu] && DZIKIR_STATE.target[waktu][idx];
    return t ? parseInt(t, 10) : defTarget;
  }

  function dzikirCard(waktu, idx) {
    return document.querySelector('.dzikir-item[data-waktu="' + waktu + '"][data-idx="' + idx + '"]');
  }

  function showPointToast(waktu, text) {
    var toast = document.getElementById(waktu === 'pagi' ? 'toastPagi' : 'toastPetang');
    if (!toast) return;
    toast.textContent = text || '+1 Poin!';
    toast.classList.add('show');
    setTimeout(function () { toast.classList.remove('show'); }, 600);
  }

  function floatLayout(waktu) {
    var st = FLOAT_DECKS[waktu];
    if (!st) return;
    st.slides.forEach(function (slide, idx) {
      slide.classList.remove('animating');
      slide.style.transform = '';
      slide.style.opacity = '';
      var sR = slide.querySelector('.stamp-right');
      var sL = slide.querySelector('.stamp-left');
      if (sR) sR.style.opacity = '0';
      if (sL) sL.style.opacity = '0';
      
      if (idx === st.i) {
        slide.classList.add('active');
      } else {
        slide.classList.remove('active');
      }
    });

    var pos = st.deck.querySelector('.dzikir-pos');
    if (pos) pos.textContent = (st.i + 1) + ' / ' + st.n;
    var pv = st.deck.querySelector('.dzikir-prev');
    var nx = st.deck.querySelector('.dzikir-next');
    if (pv) pv.disabled = st.i <= 0;
    if (nx) nx.disabled = st.i >= st.n - 1;
  }

  function handleSwipeRight(waktu) {
    var st = FLOAT_DECKS[waktu];
    if (!st) return;
    var activeCard = st.slides[st.i];
    var item = activeCard.querySelector('.dzikir-item');
    if (!item) return;

    var idx = st.i;
    var target = dzikirTarget(waktu, idx, parseInt(item.getAttribute('data-target'), 10));
    var cur = parseInt(DZIKIR_STATE[waktu][idx] || 0, 10);

    if (cur < target) {
      // Point bertambah otomatis!
      cur++;
      DZIKIR_STATE[waktu][idx] = cur;
      dzikirPointsAdd(1);
      dzikirSave(DZIKIR_STATE);
      dzikirRenderCard(waktu, idx);
      dzikirRenderAll();
      showPointToast(waktu, '+1 Poin (' + cur + '/' + target + ')');

      if (cur >= target) {
        dzikirPushHistory(waktu, idx, target, item);
        // Bila tuntas, animasikan kartu meluncur ke kanan lalu pindah ke kartu berikutnya
        activeCard.classList.add('animating');
        activeCard.style.transform = 'translate(' + Math.max(window.innerWidth, 500) + 'px, 20px) rotate(20deg)';
        activeCard.style.opacity = '0';
        setTimeout(function () {
          if (st.i < st.n - 1) {
            st.i++;
            floatLayout(waktu);
          } else {
            floatLayout(waktu);
          }
        }, 220);
        return;
      } else {
        // Belum mencapai target: membal kembali ke tengah secara elastis
        activeCard.classList.add('animating');
        activeCard.style.transform = 'translate(0, 0) rotate(0)';
        setTimeout(function () { activeCard.classList.remove('animating'); }, 260);
      }
    } else {
      // Sudah selesai: geser kanan pindah ke kartu berikutnya
      if (st.i < st.n - 1) {
        activeCard.classList.add('animating');
        activeCard.style.transform = 'translate(' + Math.max(window.innerWidth, 500) + 'px, 20px) rotate(20deg)';
        activeCard.style.opacity = '0';
        setTimeout(function () {
          st.i++;
          floatLayout(waktu);
        }, 220);
      } else {
        activeCard.classList.add('animating');
        activeCard.style.transform = 'translate(0, 0) rotate(0)';
        setTimeout(function () { activeCard.classList.remove('animating'); }, 260);
      }
    }
  }

  function handleSwipeLeft(waktu) {
    var st = FLOAT_DECKS[waktu];
    if (!st) return;
    var activeCard = st.slides[st.i];

    if (st.i > 0) {
      // Geser kiri = kembali ke kartu sebelumnya
      activeCard.classList.add('animating');
      activeCard.style.transform = 'translate(-' + Math.max(window.innerWidth, 500) + 'px, 20px) rotate(-20deg)';
      activeCard.style.opacity = '0';
      setTimeout(function () {
        st.i--;
        floatLayout(waktu);
      }, 220);
    } else {
      // Sudah di kartu pertama: bounce kembali
      activeCard.classList.add('animating');
      activeCard.style.transform = 'translate(0, 0) rotate(0)';
      setTimeout(function () { activeCard.classList.remove('animating'); }, 260);
    }
  }

  function initFloatDzikir(waktu) {
    var deck = document.getElementById(waktu === 'pagi' ? 'deckPagi' : 'deckPetang');
    if (!deck) return;
    var stage = deck.querySelector('.dzikir-stage');
    var slides = deck.querySelectorAll('.dzikir-float-card');
    var st = {
      deck: deck,
      stage: stage,
      slides: slides,
      i: 0,
      n: slides.length,
      drag: null,
      suppress: false
    };
    FLOAT_DECKS[waktu] = st;

    // Pointer events untuk swipe geser kanan / kiri
    stage.addEventListener('pointerdown', function (e) {
      if (e.target.closest('button, a')) return;
      var cur = st.slides[st.i];
      if (!cur || !cur.contains(e.target)) return;
      st.drag = { id: e.pointerId, x0: e.clientX, y0: e.clientY, dx: 0, moved: false };
      st.suppress = false;
      cur.classList.remove('animating');
    });

    stage.addEventListener('pointermove', function (e) {
      var dr = st.drag;
      if (!dr || e.pointerId !== dr.id) return;
      var dx = e.clientX - dr.x0;
      var dy = e.clientY - dr.y0;
      if (!dr.moved && Math.abs(dx) + Math.abs(dy) < 8) return;
      dr.moved = true;
      dr.dx = dx;
      st.suppress = true;

      var cur = st.slides[st.i];
      if (!cur) return;
      cur.style.transform = 'translate(' + Math.round(dx) + 'px,' + Math.round(dy * 0.18) + 'px) rotate(' + (dx / 18).toFixed(2) + 'deg)';

      var sR = cur.querySelector('.stamp-right');
      var sL = cur.querySelector('.stamp-left');
      if (sR) sR.style.opacity = dx > 15 ? Math.min(1, (dx - 15) / 50) : 0;
      if (sL) sL.style.opacity = dx < -15 ? Math.min(1, (-dx - 15) / 50) : 0;
    });

    function endDrag(e) {
      var dr = st.drag;
      if (!dr || (e.pointerId !== undefined && e.pointerId !== dr.id)) return;
      st.drag = null;
      var cur = st.slides[st.i];
      if (!cur) return;

      var sR = cur.querySelector('.stamp-right');
      var sL = cur.querySelector('.stamp-left');
      if (sR) sR.style.opacity = '0';
      if (sL) sL.style.opacity = '0';

      var dx = dr.dx;
      if (dx > 65) {
        handleSwipeRight(waktu);
      } else if (dx < -65) {
        handleSwipeLeft(waktu);
      } else {
        cur.classList.add('animating');
        cur.style.transform = 'translate(0, 0) rotate(0)';
        setTimeout(function () { cur.classList.remove('animating'); }, 260);
      }
    }

    stage.addEventListener('pointerup', endDrag);
    stage.addEventListener('pointercancel', endDrag);

    stage.addEventListener('click', function (e) {
      if (st.suppress) {
        e.stopPropagation();
        e.preventDefault();
        st.suppress = false;
      }
    }, true);

    var prevBtn = deck.querySelector('.dzikir-prev');
    var nextBtn = deck.querySelector('.dzikir-next');
    if (prevBtn) prevBtn.addEventListener('click', function () { handleSwipeLeft(waktu); });
    if (nextBtn) nextBtn.addEventListener('click', function () { handleSwipeRight(waktu); });

    floatLayout(waktu);
  }

  function dzikirPushHistory(waktu, idx, target, card) {
    var judul = '';
    var h3 = card.querySelector('h3');
    if (h3) judul = h3.textContent.trim();
    var now = new Date();
    var time = ('0' + now.getHours()).slice(-2) + ':' + ('0' + now.getMinutes()).slice(-2);
    var h = dzikirHistLoad();
    var day = dzikirTodayStr();
    h[day] = h[day] || [];
    h[day].push({ waktu: waktu, judul: judul, target: target, time: time });
    var days = Object.keys(h).sort();
    while (days.length > 30) { delete h[days.shift()]; }
    dzikirHistSave(h);
    dzikirRenderHistory();
    renderStreakAndPoints();
    renderDzikirCalendar();
    renderDzikirPeriodNotification();
  }

  function dzikirRenderHistory() {
    var wrap = document.getElementById('dzikirHistory');
    if (!wrap) return;
    var h = dzikirHistLoad();
    var days = Object.keys(h).sort().reverse();
    if (!days.length) {
      wrap.innerHTML = '<p class="text-xs text-[#8a979b]">Belum ada riwayat bacaan.</p>';
      return;
    }
    var html = '';
    days.forEach(function (day) {
      var entries = (h[day] || []).slice().reverse();
      if (!entries.length) return;
      html += '<p class="text-xs font-bold text-[#1D4D4F] mt-3 mb-1">' + dzikirDayLabel(day) + ' &bull; ' + entries.length + ' bacaan</p>';
      html += '<ul class="space-y-1">';
      entries.forEach(function (en) {
        var tag = en.waktu === 'pagi' ? '☀️' : '🌙';
        html += '<li class="text-xs text-[#1C2224] flex justify-between gap-2">'
          + '<span>' + tag + ' ' + dzikirEscapeHtml(en.judul) + '</span>'
          + '<span class="text-[#607072] shrink-0">' + dzikirEscapeHtml(en.time || '') + ' &bull; ' + en.target + 'x</span></li>';
      });
      html += '</ul>';
    });
    wrap.innerHTML = html || '<p class="text-xs text-[#8a979b]">Belum ada riwayat bacaan.</p>';
  }

  function dzikirRenderCard(waktu, idx) {
    var card = dzikirCard(waktu, idx);
    if (!card) return;
    var defTarget = parseInt(card.getAttribute('data-target'), 10);
    var target = dzikirTarget(waktu, idx, defTarget);
    var count = parseInt(DZIKIR_STATE[waktu][idx] || 0, 10);
    if (count > target) count = target;
    var btn = card.querySelector('.dzikir-count-btn');
    var done = count >= target;
    btn.textContent = done
      ? '✓ Selesai • ' + count + ' / ' + target + ' — ketuk untuk mengulang'
      : 'Ketuk untuk menghitung • ' + count + ' / ' + target;
    btn.classList.toggle('bg-[#1D4D4F]', done);
    btn.classList.toggle('text-white', done);
    btn.classList.toggle('bg-[#E8F5F3]', !done);
    btn.classList.toggle('text-[#1D4D4F]', !done);
    card.querySelector('.dzikir-done-badge').style.display = done ? 'inline-flex' : 'none';
    var choices = card.querySelectorAll('.dzikir-choice');
    choices.forEach(function (c) {
      var v = parseInt(c.getAttribute('data-value'), 10);
      var active = v === target;
      c.classList.toggle('bg-[#1D4D4F]', active);
      c.classList.toggle('text-white', active);
      c.classList.toggle('bg-[#F1F5F4]', !active);
      c.classList.toggle('text-[#607072]', !active);
    });
  }

  function dzikirRenderAll() {
    ['pagi', 'petang'].forEach(function (waktu) {
      document.querySelectorAll('.dzikir-item[data-waktu="' + waktu + '"]').forEach(function (card) {
        dzikirRenderCard(waktu, parseInt(card.getAttribute('data-idx'), 10));
      });
    });
    var done = 0;
    document.querySelectorAll('.dzikir-item[data-waktu="' + DZIKIR_TAB + '"]').forEach(function (card) {
      var waktu = card.getAttribute('data-waktu');
      var idx = parseInt(card.getAttribute('data-idx'), 10);
      var target = dzikirTarget(waktu, idx, parseInt(card.getAttribute('data-target'), 10));
      if (parseInt(DZIKIR_STATE[waktu][idx] || 0, 10) >= target) done++;
    });
    document.getElementById('dzikirProgress').textContent = done + ' dari ' + DZIKIR_TOTALS[DZIKIR_TAB] + ' selesai';
    renderDzikirPeriodNotification();
  }

  function dzikirTap(btn, add) {
    var card = btn.closest('.dzikir-item');
    var waktu = card.getAttribute('data-waktu');
    var idx = parseInt(card.getAttribute('data-idx'), 10);
    var target = dzikirTarget(waktu, idx, parseInt(card.getAttribute('data-target'), 10));
    var prevCount = parseInt(DZIKIR_STATE[waktu][idx] || 0, 10);
    var wasDone = prevCount >= target;
    var count = prevCount;
    if (count >= target) {
      count = 0;
    } else {
      count = Math.min(target, count + add);
      dzikirPointsAdd(add);
      showPointToast(waktu, '+' + add + ' Poin (' + count + '/' + target + ')');
    }
    DZIKIR_STATE[waktu][idx] = count;
    dzikirSave(DZIKIR_STATE);
    dzikirRenderCard(waktu, idx);
    dzikirRenderAll();
    if (!wasDone && count >= target) {
      dzikirPushHistory(waktu, idx, target, card);
      setTimeout(function () {
        var st = FLOAT_DECKS[waktu];
        if (st && st.i === idx && st.i < st.n - 1) {
          handleSwipeRight(waktu);
        }
      }, 500);
    }
  }

  function dzikirResetCard(btn) {
    var card = btn.closest('.dzikir-item');
    var waktu = card.getAttribute('data-waktu');
    var idx = parseInt(card.getAttribute('data-idx'), 10);
    DZIKIR_STATE[waktu][idx] = 0;
    dzikirSave(DZIKIR_STATE);
    dzikirRenderCard(waktu, idx);
    dzikirRenderAll();
  }

  function dzikirSetTarget(btn, value) {
    var card = btn.closest('.dzikir-item');
    var waktu = card.getAttribute('data-waktu');
    var idx = parseInt(card.getAttribute('data-idx'), 10);
    DZIKIR_STATE.target[waktu][idx] = value;
    DZIKIR_STATE[waktu][idx] = 0;
    dzikirSave(DZIKIR_STATE);
    dzikirRenderCard(waktu, idx);
    dzikirRenderAll();
  }

  function setDzikirTab(waktu) {
    DZIKIR_TAB = waktu;
    document.getElementById('deckPagi').style.display = waktu === 'pagi' ? '' : 'none';
    document.getElementById('deckPetang').style.display = waktu === 'petang' ? '' : 'none';
    var tabPagi = document.getElementById('tabPagi');
    var tabPetang = document.getElementById('tabPetang');
    tabPagi.className = 'flex-1 py-2.5 rounded-xl text-sm font-bold transition ' + (waktu === 'pagi' ? 'bg-[#1D4D4F] text-white' : 'bg-[#F1F5F4] text-[#607072]');
    tabPetang.className = 'flex-1 py-2.5 rounded-xl text-sm font-bold transition ' + (waktu === 'petang' ? 'bg-[#1D4D4F] text-white' : 'bg-[#F1F5F4] text-[#607072]');
    
    var st = FLOAT_DECKS[waktu];
    if (st) {
      // Buka kartu pertama yang belum selesai
      for (var i = 0; i < st.n; i++) {
        var item = st.slides[i].querySelector('.dzikir-item');
        var tgt = dzikirTarget(waktu, i, parseInt(item.getAttribute('data-target'), 10));
        if (parseInt(DZIKIR_STATE[waktu][i] || 0, 10) < tgt) { st.i = i; break; }
      }
      floatLayout(waktu);
    }
    dzikirRenderAll();
  }

  function resetDzikirToday() {
    DZIKIR_STATE = { date: dzikirTodayStr(), pagi: {}, petang: {}, target: { pagi: {}, petang: {} } };
    dzikirSave(DZIKIR_STATE);
    dzikirRenderAll();
    renderDzikirCalendar();
    renderDzikirPeriodNotification();
  }

  (function dzikirInit() {
    initFloatDzikir('pagi');
    initFloatDzikir('petang');
    dzikirRenderHistory();
    renderStreakAndPoints();
    renderDzikirCalendar();

    var h = new Date().getHours();
    setDzikirTab((h >= 4 && h < 15) ? 'pagi' : 'petang');

    // Panah keyboard desktop (kiri = kembali, kanan = point / lanjut)
    document.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowRight') handleSwipeRight(DZIKIR_TAB);
      if (e.key === 'ArrowLeft') handleSwipeLeft(DZIKIR_TAB);
    });
  })();
</script>
@endsection
