@extends('layouts.app')

@section('title', 'Dzikir Pagi & Petang - Majelisku')

@section('meta_description', 'Dzikir pagi dan petang sesuai sunnah dari buku Ustadz Yazid bin Abdul Qadir Jawas — lengkap dengan teks Arab, latin, arti, dalil, dan penghitung.')

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

  <!-- 4. Daftar dzikir pagi -->
  <div id="dzikirPagi" class="space-y-3.5">
    @foreach($pagi as $i => $item)
      @include('kajian.partials.dzikir-item', ['item' => $item, 'waktu' => 'pagi', 'idx' => $i])
    @endforeach
  </div>

  <!-- 5. Daftar dzikir petang -->
  <div id="dzikirPetang" class="space-y-3.5" style="display:none">
    @foreach($petang as $i => $item)
      @include('kajian.partials.dzikir-item', ['item' => $item, 'waktu' => 'petang', 'idx' => $i])
    @endforeach
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
    document.getElementById('dzikirPagi').style.display = waktu === 'pagi' ? '' : 'none';
    document.getElementById('dzikirPetang').style.display = waktu === 'petang' ? '' : 'none';
    var tabPagi = document.getElementById('tabPagi');
    var tabPetang = document.getElementById('tabPetang');
    tabPagi.className = 'flex-1 py-2.5 rounded-xl text-sm font-bold transition ' + (waktu === 'pagi' ? 'bg-[#1D4D4F] text-white' : 'bg-[#F1F5F4] text-[#607072]');
    tabPetang.className = 'flex-1 py-2.5 rounded-xl text-sm font-bold transition ' + (waktu === 'petang' ? 'bg-[#1D4D4F] text-white' : 'bg-[#F1F5F4] text-[#607072]');
    dzikirRenderAll();
  }

  function resetDzikirToday() {
    DZIKIR_STATE = { date: dzikirTodayStr(), pagi: {}, petang: {}, target: { pagi: {}, petang: {} } };
    dzikirSave(DZIKIR_STATE);
    dzikirRenderAll();
  }

  (function dzikirInit() {
    var h = new Date().getHours();
    setDzikirTab((h >= 4 && h < 12) ? 'pagi' : 'petang');
  })();
</script>
@endsection
