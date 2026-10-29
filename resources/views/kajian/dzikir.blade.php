@extends('layouts.app')

@section('title', 'Dzikir Pagi & Petang - Majelisku')

@section('meta_description', 'Dzikir pagi dan petang sesuai sunnah dari buku Ustadz Yazid bin Abdul Qadir Jawas — lengkap dengan teks Arab, latin, arti, dalil, dan penghitung.')

@section('styles')
<style>
  /* Deck kartu swipe dzikir: satu kartu per layar, geser kiri/kanan */
  .dzikir-viewport { overflow: hidden; touch-action: pan-y; }
  .dzikir-track { display: flex; transition: transform .32s cubic-bezier(.2,.7,.3,1); will-change: transform; }
  .dzikir-track.dragging { transition: none; }
  .dzikir-slide { flex: 0 0 100%; min-width: 0; box-sizing: border-box; padding: 0 2px; }
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
    <div class="dzikir-viewport">
      <div class="dzikir-track">
        @foreach($pagi as $i => $item)
          <div class="dzikir-slide">
            @include('kajian.partials.dzikir-item', ['item' => $item, 'waktu' => 'pagi', 'idx' => $i])
          </div>
        @endforeach
      </div>
    </div>
    <p class="dzikir-hint">Geser kiri / kanan untuk pindah kartu</p>
  </div>

  <!-- 5. Deck kartu swipe: dzikir petang -->
  <div id="deckPetang" class="dzikir-deck" data-waktu="petang" style="display:none">
    <div class="flex items-center justify-between mb-2">
      <button type="button" class="dzikir-navbtn dzikir-prev" aria-label="Sebelumnya">‹</button>
      <span class="dzikir-pos text-sm font-bold text-[#607072]">1 / {{ count($petang) }}</span>
      <button type="button" class="dzikir-navbtn dzikir-next" aria-label="Berikutnya">›</button>
    </div>
    <div class="dzikir-viewport">
      <div class="dzikir-track">
        @foreach($petang as $i => $item)
          <div class="dzikir-slide">
            @include('kajian.partials.dzikir-item', ['item' => $item, 'waktu' => 'petang', 'idx' => $i])
          </div>
        @endforeach
      </div>
    </div>
    <p class="dzikir-hint">Geser kiri / kanan untuk pindah kartu</p>
  </div>

  <!-- 6. Kembali -->
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

  /* ===== Deck kartu swipe (satu kartu per layar, geser kiri/kanan) ===== */
  var DECK = {};

  function deckMeasure(waktu) {
    var st = DECK[waktu];
    if (!st) return;
    st.w = st.deck.querySelector('.dzikir-viewport').clientWidth || 0;
  }

  function deckUpdate(waktu, instant) {
    var st = DECK[waktu];
    if (!st) return;
    if (instant) st.track.style.transition = 'none';
    st.track.style.transform = 'translateX(' + (-st.i * st.w + st.dragX) + 'px)';
    if (instant) { void st.track.offsetWidth; st.track.style.transition = ''; }
    st.deck.querySelector('.dzikir-pos').textContent = (st.i + 1) + ' / ' + st.n;
    st.deck.querySelector('.dzikir-prev').disabled = st.i <= 0;
    st.deck.querySelector('.dzikir-next').disabled = st.i >= st.n - 1;
  }

  function deckGo(waktu, i) {
    var st = DECK[waktu];
    if (!st) return;
    st.i = Math.max(0, Math.min(st.n - 1, i));
    deckUpdate(waktu);
  }

  function deckFirstIncomplete(waktu) {
    var st = DECK[waktu];
    if (!st) return 0;
    for (var i = 0; i < st.n; i++) {
      var target = dzikirTarget(waktu, i, parseInt(st.slides[i].querySelector('.dzikir-item').getAttribute('data-target'), 10));
      if (parseInt(DZIKIR_STATE[waktu][i] || 0, 10) < target) return i;
    }
    return 0;
  }

  function deckDown(waktu, e) {
    var st = DECK[waktu];
    if (!st || e.target.closest('button, a')) return; // biarkan tombol diklik normal
    st.dragging = true; st.pid = e.pointerId; st.startX = e.clientX; st.dragX = 0; st.moved = false;
    st.track.classList.add('dragging');
  }

  function deckMove(waktu, e) {
    var st = DECK[waktu];
    if (!st || !st.dragging || e.pointerId !== st.pid) return;
    var dx = e.clientX - st.startX;
    if (Math.abs(dx) > 12) st.moved = true;
    if (!st.moved) return;
    var base = -st.i * st.w;
    var x = base + dx;
    var max = 0, min = -(st.n - 1) * st.w;
    if (x > max) x = max + (x - max) * 0.35; // resistance di ujung
    if (x < min) x = min + (x - min) * 0.35;
    st.dragX = x - base;
    deckUpdate(waktu);
  }

  function deckUp(waktu, e) {
    var st = DECK[waktu];
    if (!st || !st.dragging || (e.pointerId !== undefined && e.pointerId !== st.pid)) return;
    st.dragging = false;
    st.track.classList.remove('dragging');
    var dx = st.dragX;
    st.dragX = 0;
    var thr = Math.min(120, st.w * 0.25);
    if (dx < -thr && st.i < st.n - 1) st.i++;
    else if (dx > thr && st.i > 0) st.i--;
    deckUpdate(waktu);
  }

  function deckInit(waktu) {
    var deck = document.getElementById(waktu === 'pagi' ? 'deckPagi' : 'deckPetang');
    if (!deck) return;
    var st = {
      deck: deck,
      track: deck.querySelector('.dzikir-track'),
      slides: deck.querySelectorAll('.dzikir-slide'),
      i: 0, w: 0, dragX: 0, dragging: false, pid: null, startX: 0, moved: false
    };
    st.n = st.slides.length;
    DECK[waktu] = st;
    deckMeasure(waktu);
    deckUpdate(waktu, true);
    var vp = deck.querySelector('.dzikir-viewport');
    vp.addEventListener('pointerdown', function (e) { deckDown(waktu, e); });
    vp.addEventListener('pointermove', function (e) { deckMove(waktu, e); });
    vp.addEventListener('pointerup', function (e) { deckUp(waktu, e); });
    vp.addEventListener('pointercancel', function (e) { deckUp(waktu, e); });
    // Klik yang terjadi setelah drag diabaikan sekali (agar tombol tak kepencet)
    vp.addEventListener('click', function (e) {
      if (st.moved) { e.stopPropagation(); e.preventDefault(); st.moved = false; }
    }, true);
    deck.querySelector('.dzikir-prev').addEventListener('click', function () { deckGo(waktu, st.i - 1); });
    deck.querySelector('.dzikir-next').addEventListener('click', function () { deckGo(waktu, st.i + 1); });
  }

  function deckAdvanceIfDone(waktu, idx) {
    var st = DECK[waktu];
    if (!st || st.i !== idx) return;
    var target = dzikirTarget(waktu, idx, parseInt(st.slides[idx].querySelector('.dzikir-item').getAttribute('data-target'), 10));
    if (parseInt(DZIKIR_STATE[waktu][idx] || 0, 10) >= target && st.i < st.n - 1) {
      setTimeout(function () {
        // pastikan masih di kartu yang sama & masih selesai
        if (DECK[waktu].i === idx &&
            parseInt(DZIKIR_STATE[waktu][idx] || 0, 10) >=
            dzikirTarget(waktu, idx, parseInt(st.slides[idx].querySelector('.dzikir-item').getAttribute('data-target'), 10))) {
          deckGo(waktu, idx + 1);
        }
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
    var count = parseInt(DZIKIR_STATE[waktu][idx] || 0, 10);
    if (count >= target) {
      count = 0;
    } else {
      count = Math.min(target, count + add);
    }
    DZIKIR_STATE[waktu][idx] = count;
    dzikirSave(DZIKIR_STATE);
    dzikirRenderCard(waktu, idx);
    dzikirRenderAll();
    deckAdvanceIfDone(waktu, idx);
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
    // Deck yang baru ditampilkan harus diukur ulang (lebar 0 saat hidden)
    deckMeasure(waktu);
    deckUpdate(waktu, true);
    // Lanjut dari kartu pertama yang belum selesai
    deckGo(waktu, deckFirstIncomplete(waktu));
    dzikirRenderAll();
  }

  function resetDzikirToday() {
    DZIKIR_STATE = { date: dzikirTodayStr(), pagi: {}, petang: {}, target: { pagi: {}, petang: {} } };
    dzikirSave(DZIKIR_STATE);
    dzikirRenderAll();
  }

  (function dzikirInit() {
    deckInit('pagi');
    deckInit('petang');
    var h = new Date().getHours();
    setDzikirTab((h >= 4 && h < 12) ? 'pagi' : 'petang');
    window.addEventListener('resize', function () {
      deckMeasure('pagi'); deckMeasure('petang');
      deckUpdate('pagi', true); deckUpdate('petang', true);
    });
    // Panah kiri/kanan untuk pindah kartu (desktop)
    document.addEventListener('keydown', function (e) {
      if (e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') return;
      var st = DECK[DZIKIR_TAB];
      if (!st) return;
      deckGo(DZIKIR_TAB, st.i + (e.key === 'ArrowRight' ? 1 : -1));
    });
  })();
</script>
@endsection
