@extends('layouts.app')

@section('title', 'Dzikir Pagi & Petang - Majelisku')

@section('meta_description', 'Dzikir pagi dan petang sesuai sunnah dari buku Ustadz Yazid bin Abdul Qadir Jawas — lengkap dengan teks Arab, latin, arti, dalil, dan penghitung.')

@section('styles')
<style>
  /* Deck kartu ala Tinder: tumpukan kartu mengambang, seret kanan=lanjut, kiri=kembali */
  .tinder-viewport {
    position: relative; touch-action: pan-y; transition: height .3s ease;
    -webkit-user-select: none; user-select: none;
  }
  .tinder-card {
    position: absolute; left: 4px; right: 4px; top: 0; touch-action: pan-y;
    transition: transform .38s cubic-bezier(.2,.7,.3,1), opacity .38s ease;
    will-change: transform;
  }
  .tinder-card.dragging { transition: none; }
  .tinder-card .dzikir-item { box-shadow: 0 14px 34px rgba(29,77,79,.16); }
  .tinder-stamp {
    position: absolute; top: 16px; padding: 6px 14px; border-radius: 10px;
    font-weight: 900; font-size: 20px; letter-spacing: 2px; opacity: 0;
    border: 3px solid; pointer-events: none; z-index: 5; background: rgba(255,255,255,.88);
  }
  .tinder-stamp.next { right: 18px; color: #1D4D4F; border-color: #1D4D4F; transform: rotate(10deg); }
  .tinder-stamp.prev { left: 18px; color: #b45309; border-color: #b45309; transform: rotate(-10deg); }
  .dzikir-navbtn {
    width: 44px; height: 44px; border-radius: 9999px; flex-shrink: 0;
    background: #fff; border: 1px solid #E5ECEB; color: #1D4D4F;
    font-size: 22px; font-weight: 800; line-height: 1; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 1px 3px rgba(29,77,79,.08);
  }
  .dzikir-navbtn:active { background: #E8F5F3; }
  .dzikir-navbtn:disabled { opacity: .3; cursor: default; }
  .dzikir-hint { text-align: center; font-size: 11px; color: #8a979b; margin-top: 8px; }
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

  <!-- 2. Tab Pagi / Petang -->
  <div class="flex gap-2">
    <button id="tabPagi" onclick="setDzikirTab('pagi')" class="flex-1 py-2.5 rounded-xl text-sm font-bold transition bg-[#1D4D4F] text-white">☀️ Pagi ({{ count($pagi) }})</button>
    <button id="tabPetang" onclick="setDzikirTab('petang')" class="flex-1 py-2.5 rounded-xl text-sm font-bold transition bg-[#F1F5F4] text-[#607072]">🌙 Petang ({{ count($petang) }})</button>
  </div>

  <!-- 3. Progres -->
  <div class="rounded-2xl bg-[#E8F5F3] border border-[#D1ECE7] px-4 py-3 flex items-center justify-between gap-3">
    <p id="dzikirProgress" class="text-sm font-bold text-[#1D4D4F]">0 dari {{ count($pagi) }} selesai</p>
    <button onclick="resetDzikirToday()" class="text-xs font-bold text-[#607072] hover:underline shrink-0">Reset hari ini ↺</button>
  </div>

  <!-- 4. Deck kartu swipe: dzikir pagi -->
  <div id="deckPagi" class="dzikir-deck" data-waktu="pagi">
    <div class="flex items-center justify-between mb-2">
      <button type="button" class="dzikir-navbtn dzikir-prev" aria-label="Sebelumnya">‹</button>
      <span class="dzikir-pos text-sm font-bold text-[#607072]">1 / {{ count($pagi) }}</span>
      <button type="button" class="dzikir-navbtn dzikir-next" aria-label="Berikutnya">›</button>
    </div>
    <div class="tinder-viewport">
      @foreach($pagi as $i => $item)
        <div class="tinder-card">
          @include('kajian.partials.dzikir-item', ['item' => $item, 'waktu' => 'pagi', 'idx' => $i])
        </div>
      @endforeach
    </div>
    <p class="dzikir-hint">Geser kanan = selesai &amp; tercatat &bull; kiri = kembali</p>
  </div>

  <!-- 5. Deck kartu swipe: dzikir petang -->
  <div id="deckPetang" class="dzikir-deck" data-waktu="petang" style="display:none">
    <div class="flex items-center justify-between mb-2">
      <button type="button" class="dzikir-navbtn dzikir-prev" aria-label="Sebelumnya">‹</button>
      <span class="dzikir-pos text-sm font-bold text-[#607072]">1 / {{ count($petang) }}</span>
      <button type="button" class="dzikir-navbtn dzikir-next" aria-label="Berikutnya">›</button>
    </div>
    <div class="tinder-viewport">
      @foreach($petang as $i => $item)
        <div class="tinder-card">
          @include('kajian.partials.dzikir-item', ['item' => $item, 'waktu' => 'petang', 'idx' => $i])
        </div>
      @endforeach
    </div>
    <p class="dzikir-hint">Geser kanan = selesai &amp; tercatat &bull; kiri = kembali</p>
  </div>

  <!-- 6. Riwayat bacaan -->
  <details class="rounded-2xl bg-white border border-[#E5ECEB] px-4 py-3">
    <summary class="text-sm font-bold text-[#1D4D4F] cursor-pointer">Riwayat bacaan</summary>
    <div id="dzikirHistory" class="mt-2"></div>
  </details>

  <!-- 7. Kembali -->
  <div class="flex gap-2">
    <a href="{{ route('kajian.sholat') }}" class="flex-1 block text-center rounded-2xl bg-white border border-[#E5ECEB] p-3 text-sm font-bold text-[#1D4D4F]">← Jadwal Sholat</a>
    <a href="{{ route('kajian.index') }}" class="flex-1 block text-center rounded-2xl bg-amber-400 text-[#1D4D4F] p-3 text-sm font-bold">Lanjut ngaji →</a>
  </div>
</div>
@endsection

@section('scripts')
<script>
  var DZIKIR_KEY = 'majelisku_dzikir_v1';
  var DZIKIR_TAB = 'pagi';
  var DZIKIR_TOTALS = { pagi: {{ count($pagi) }}, petang: {{ count($petang) }} };

  /* ===== Deck kartu ala Tinder: tumpukan mengambang, kanan=lanjut, kiri=kembali ===== */
  var DECK = {};

  function tinderLayout(waktu, instantAll) {
    var st = DECK[waktu];
    if (!st) return;
    st.slides.forEach(function (slide, idx) {
      var diff = idx - st.i;
      var instant = instantAll || slide.dataset.instant === '1';
      if (instant) slide.style.transition = 'none';
      slide.classList.remove('dragging');
      slide.querySelectorAll('.tinder-stamp').forEach(function (s) { s.style.opacity = '0'; });
      if (diff === 0) {
        slide.style.transform = '';
        slide.style.opacity = '1'; slide.style.visibility = '';
        slide.style.pointerEvents = ''; slide.style.zIndex = 30;
      } else if (diff === 1 || diff === 2) {
        var sc = diff === 1 ? 0.95 : 0.9, y = diff * 16;
        slide.style.transform = 'translateY(' + y + 'px) scale(' + sc + ')';
        slide.style.opacity = diff === 1 ? '0.85' : '0.6'; slide.style.visibility = '';
        slide.style.pointerEvents = 'none'; slide.style.zIndex = 30 - diff;
      } else {
        slide.style.transform = 'translateY(48px) scale(0.86)';
        slide.style.opacity = '0'; slide.style.visibility = 'hidden';
        slide.style.pointerEvents = 'none'; slide.style.zIndex = 1;
      }
      if (instant) { void slide.offsetWidth; slide.style.transition = ''; delete slide.dataset.instant; }
    });
    var pos = st.deck.querySelector('.dzikir-pos');
    if (pos) pos.textContent = (st.i + 1) + ' / ' + st.n;
    var pv = st.deck.querySelector('.dzikir-prev'), nx = st.deck.querySelector('.dzikir-next');
    if (pv) pv.disabled = st.i <= 0;
    if (nx) nx.disabled = st.i >= st.n - 1;
    var top = st.slides[st.i];
    if (top) st.viewport.style.height = top.offsetHeight + 'px';
  }

  function tinderFirstIncomplete(waktu) {
    var st = DECK[waktu];
    if (!st) return 0;
    for (var i = 0; i < st.n; i++) {
      var card = st.slides[i].querySelector('.dzikir-item');
      var target = dzikirTarget(waktu, i, parseInt(card.getAttribute('data-target'), 10));
      if (parseInt(DZIKIR_STATE[waktu][i] || 0, 10) < target) return i;
    }
    return 0;
  }

  function tinderDown(waktu, e) {
    var st = DECK[waktu];
    if (!st) return;
    var top = st.slides[st.i];
    if (!top || !top.contains(e.target)) return;
    if (e.target.closest('button, a')) return; // biarkan tombol diklik normal
    st.drag = { id: e.pointerId, x0: e.clientX, y0: e.clientY, dx: 0, moved: false };
    st.suppress = false;
    top.classList.add('dragging');
  }

  function tinderMove(waktu, e) {
    var st = DECK[waktu];
    var dr = st && st.drag;
    if (!dr || e.pointerId !== dr.id) return;
    var dx = e.clientX - dr.x0, dy = e.clientY - dr.y0;
    if (!dr.moved && Math.abs(dx) + Math.abs(dy) < 10) return;
    dr.moved = true; dr.dx = dx; st.suppress = true;
    var top = st.slides[st.i];
    top.style.transform = 'translate(' + Math.round(dx) + 'px,' + Math.round(dy * 0.25) + 'px) rotate(' + (dx / 16).toFixed(2) + 'deg)';
    var sN = top.querySelector('.tinder-stamp.next'), sP = top.querySelector('.tinder-stamp.prev');
    if (sN) sN.style.opacity = dx > 0 ? Math.min(1, Math.max(0, (dx - 40) / 80)) : 0;
    if (sP) sP.style.opacity = dx < 0 ? Math.min(1, Math.max(0, (-dx - 40) / 80)) : 0;
    // kartu di belakang ikut membesar halus saat kartu atas diseret
    var p = Math.min(1, Math.abs(dx) / 110);
    [1, 2].forEach(function (d) {
      var c = st.slides[st.i + d];
      if (!c) return;
      var sc = (d === 1 ? 0.95 : 0.9) + 0.05 * p, y = d * 16 * (1 - p);
      c.style.transform = 'translateY(' + y.toFixed(1) + 'px) scale(' + sc.toFixed(3) + ')';
    });
  }

  function tinderUp(waktu, e) {
    var st = DECK[waktu];
    var dr = st && st.drag;
    if (!dr || (e.pointerId !== undefined && e.pointerId !== dr.id)) return;
    st.drag = null;
    var top = st.slides[st.i];
    if (top) top.classList.remove('dragging');
    var dx = dr.dx;
    if (dx > 110) { tinderFly(waktu, 'next'); return; }
    if (dx < -110) {
      if (st.i > 0) { tinderFly(waktu, 'prev'); return; }
    }
    tinderLayout(waktu); // snap back
  }

  function tinderFly(waktu, dir) {
    var st = DECK[waktu];
    if (!st) return;
    // Geser kanan = selesai: point tercatat otomatis + masuk riwayat.
    if (dir === 'next') dzikirSwipeComplete(waktu, st.i);
    var top = st.slides[st.i];
    if (!top) return;
    if (dir === 'prev' && st.i <= 0) { tinderLayout(waktu); return; }
    if (dir === 'next' && st.i >= st.n - 1) { tinderLayout(waktu); return; }
    top.classList.remove('dragging');
    st.drag = null;
    top.dataset.flying = '1';
    var x = dir === 'next' ? Math.max(window.innerWidth, 600) : -Math.max(window.innerWidth, 600);
    top.style.transform = 'translate(' + x + 'px, 60px) rotate(' + (dir === 'next' ? 30 : -30) + 'deg)';
    setTimeout(function () {
      delete top.dataset.flying;
      top.dataset.instant = '1'; // pindah tumpukan tanpa animasi yang terlihat
      st.i = dir === 'next' ? st.i + 1 : st.i - 1;
      tinderLayout(waktu);
    }, 300);
  }

  function tinderInit(waktu) {
    var deck = document.getElementById(waktu === 'pagi' ? 'deckPagi' : 'deckPetang');
    if (!deck) return;
    var st = {
      deck: deck,
      viewport: deck.querySelector('.tinder-viewport'),
      slides: deck.querySelectorAll('.tinder-card'),
      i: 0, drag: null, suppress: false
    };
    st.n = st.slides.length;
    DECK[waktu] = st;
    // stempel ala Tinder di tiap kartu
    st.slides.forEach(function (slide) {
      if (slide.querySelector('.tinder-stamp')) return;
      var a = document.createElement('div'); a.className = 'tinder-stamp next'; a.textContent = 'LANJUT';
      var b = document.createElement('div'); b.className = 'tinder-stamp prev'; b.textContent = 'KEMBALI';
      slide.appendChild(a); slide.appendChild(b);
    });
    var vp = st.viewport;
    vp.addEventListener('pointerdown', function (e) { tinderDown(waktu, e); });
    vp.addEventListener('pointermove', function (e) { tinderMove(waktu, e); });
    vp.addEventListener('pointerup', function (e) { tinderUp(waktu, e); });
    vp.addEventListener('pointercancel', function (e) { tinderUp(waktu, e); });
    // klik yang terjadi tepat setelah drag diabaikan sekali
    vp.addEventListener('click', function (e) {
      if (st.suppress) { e.stopPropagation(); e.preventDefault(); st.suppress = false; }
    }, true);
    deck.querySelector('.dzikir-prev').addEventListener('click', function () { tinderFly(waktu, 'prev'); });
    deck.querySelector('.dzikir-next').addEventListener('click', function () { tinderFly(waktu, 'next'); });
    tinderLayout(waktu, true);
  }

  function tinderAdvanceIfDone(waktu, idx) {
    var st = DECK[waktu];
    if (!st || st.i !== idx || st.i >= st.n - 1) return;
    var card = st.slides[idx].querySelector('.dzikir-item');
    var target = dzikirTarget(waktu, idx, parseInt(card.getAttribute('data-target'), 10));
    if (parseInt(DZIKIR_STATE[waktu][idx] || 0, 10) >= target) {
      setTimeout(function () {
        var s2 = DECK[waktu];
        if (!s2 || s2.i !== idx || s2.i >= s2.n - 1) return;
        var c2 = s2.slides[idx].querySelector('.dzikir-item');
        var t2 = dzikirTarget(waktu, idx, parseInt(c2.getAttribute('data-target'), 10));
        if (parseInt(DZIKIR_STATE[waktu][idx] || 0, 10) >= t2) tinderFly(waktu, 'next');
      }, 650);
    }
  }

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
    // Ringkasan untuk kartu "Baca Dzikir" di halaman /sholat.
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

  /* ===== Riwayat bacaan dzikir (per hari, 30 hari terakhir) ===== */
  var DZIKIR_HIST_KEY = 'majelisku_dzikir_history_v1';
  var DZIKIR_BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

  function dzikirHistLoad() {
    try { return JSON.parse(localStorage.getItem(DZIKIR_HIST_KEY) || '{}'); }
    catch (e) { return {}; }
  }

  function dzikirHistSave(h) {
    try { localStorage.setItem(DZIKIR_HIST_KEY, JSON.stringify(h)); } catch (e) {}
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

  // Catat satu bacaan selesai ke riwayat (dipanggil tepat saat transisi ke selesai).
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

  // Selesaikan kartu karena di-swipe kanan: point tercatat otomatis + masuk riwayat.
  function dzikirSwipeComplete(waktu, idx) {
    var st = DECK[waktu];
    if (!st) return;
    var card = st.slides[idx].querySelector('.dzikir-item');
    if (!card) return;
    var target = dzikirTarget(waktu, idx, parseInt(card.getAttribute('data-target'), 10));
    var count = parseInt(DZIKIR_STATE[waktu][idx] || 0, 10);
    if (count >= target) return; // sudah selesai sebelumnya: jangan catat dobel
    DZIKIR_STATE[waktu][idx] = target;
    dzikirSave(DZIKIR_STATE);
    dzikirRenderCard(waktu, idx);
    dzikirRenderAll();
    dzikirPushHistory(waktu, idx, target, card);
  }

  var DZIKIR_STATE = dzikirLoad();

  function dzikirTarget(waktu, idx, defTarget) {
    var t = DZIKIR_STATE.target[waktu] && DZIKIR_STATE.target[waktu][idx];
    return t ? parseInt(t, 10) : defTarget;
  }

  function dzikirCard(waktu, idx) {
    return document.querySelector('.dzikir-item[data-waktu="' + waktu + '"][data-idx="' + idx + '"]');
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
    }
    DZIKIR_STATE[waktu][idx] = count;
    dzikirSave(DZIKIR_STATE);
    dzikirRenderCard(waktu, idx);
    dzikirRenderAll();
    if (!wasDone && count >= target) dzikirPushHistory(waktu, idx, target, card);
    tinderAdvanceIfDone(waktu, idx);
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
    // Deck yang baru ditampilkan di-layout ulang + lanjut dari kartu
    // pertama yang belum selesai
    var st = DECK[waktu];
    if (st) { st.i = tinderFirstIncomplete(waktu); tinderLayout(waktu, true); }
    dzikirRenderAll();
  }

  function resetDzikirToday() {
    DZIKIR_STATE = { date: dzikirTodayStr(), pagi: {}, petang: {}, target: { pagi: {}, petang: {} } };
    dzikirSave(DZIKIR_STATE);
    dzikirRenderAll();
  }

  (function dzikirInit() {
    tinderInit('pagi');
    tinderInit('petang');
    dzikirRenderHistory();
    var h = new Date().getHours();
    setDzikirTab((h >= 4 && h < 12) ? 'pagi' : 'petang');
    window.addEventListener('resize', function () {
      tinderLayout('pagi', true); tinderLayout('petang', true);
    });
    window.addEventListener('load', function () {
      tinderLayout('pagi', true); tinderLayout('petang', true);
    });
    // Panah kiri/kanan untuk pindah kartu (desktop)
    document.addEventListener('keydown', function (e) {
      if (e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') return;
      tinderFly(DZIKIR_TAB, e.key === 'ArrowRight' ? 'next' : 'prev');
    });
  })();
</script>
@endsection
