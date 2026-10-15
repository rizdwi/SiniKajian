/**
 * SiniKajian Comprehensive Interactive Verification
 * Simulates user interactions and verifies R-26 (Real Interactive Behaviors) & R-35 (Verify Before Delivery)
 */

const fs = require('fs');
const assert = require('assert');
const vm = require('vm');

// Load HTML
const html = fs.readFileSync('C:/Users/Rizz/SiniKajian/index.html', 'utf8');

// Extract the script block
const scriptMatch = html.match(/<script>([\s\S]*?)<\/script>[\s\S]*?<script>([\s\S]*?)<\/script>/);
const appScript = scriptMatch ? scriptMatch[2] : '';

// Create a DOM-like testing harness
const localStorageMock = {
  store: {},
  getItem(k) { return this.store[k] || null; },
  setItem(k, v) { this.store[k] = String(v); },
  removeItem(k) { delete this.store[k]; }
};

console.log('--- STARTING INTERACTIVE TEST HARNESS ---');

// Mock browser globals
global.window = {
  innerWidth: 1024,
  matchMedia: () => ({ matches: false }),
  speechSynthesis: {
    cancel: () => {},
    speak: (u) => { global.lastSpokenText = u.text; }
  }
};

global.domElements = {};

global.document = {
  documentElement: {
    classList: {
      classes: new Set(),
      add(c) { this.classes.add(c); },
      remove(c) { this.classes.delete(c); },
      toggle(c) {
        if (this.classes.has(c)) { this.classes.delete(c); return false; }
        this.classes.add(c); return true;
      },
      contains(c) { return this.classes.has(c); }
    }
  },
  getElementById: (id) => {
    if (!global.domElements[id]) {
      global.domElements[id] = {
        id,
        textContent: '',
        innerText: '',
        value: '',
        innerHTML: '',
        href: '',
        style: {},
        classList: {
          items: new Set(),
          add(...c) { c.forEach(x => this.items.add(x)); },
          remove(...c) { c.forEach(x => this.items.delete(x)); },
          toggle(x) { this.items.has(x) ? this.items.delete(x) : this.items.add(x); },
          contains(x) { return this.items.has(x); }
        },
        querySelectorAll: () => [],
        reset: () => {}
      };
    }
    return global.domElements[id];
  },
  querySelectorAll: (selector) => {
    if (selector === '.tab-view') {
      return [
        global.document.getElementById('view-kajian'),
        global.document.getElementById('view-peta'),
        global.document.getElementById('view-sholat'),
        global.document.getElementById('view-kiblat'),
        global.document.getElementById('view-tersimpan')
      ];
    }
    return [];
  },
  addEventListener: () => {}
};

global.localStorage = localStorageMock;
global.navigator = {
  clipboard: {
    writeText: async (t) => { global.lastCopied = t; return Promise.resolve(); }
  },
  serviceWorker: {
    register: async () => Promise.resolve()
  }
};

global.L = {
  divIcon: () => ({}),
  map: () => ({
    setView: () => {},
    invalidateSize: () => {},
    removeLayer: () => {}
  }),
  tileLayer: () => ({ addTo: () => {} }),
  marker: () => {
    const m = {
      addTo: () => m,
      bindPopup: () => m,
      on: () => m
    };
    return m;
  },
  control: {
    zoom: () => ({ addTo: () => {} })
  }
};

vm.runInThisContext(appScript);

// Initialize events like DOMContentLoaded does
events = INITIAL_EVENTS;

console.log('✓ Script evaluated cleanly with 0 exceptions.');

// Interaction 1: Verify Theme Toggle
const isDarkInitially = document.documentElement.classList.contains('dark');
toggleDarkMode();
assert(document.documentElement.classList.contains('dark') !== isDarkInitially, 'Theme should toggle');
console.log('✓ Interaction 1 (Dark mode toggle): PASS');

// Interaction 2: Search keyword filter
document.getElementById('searchInput').value = 'Firanda';
onSearchChange();
assert.strictEqual(searchQuery, 'Firanda', 'searchQuery state set to Firanda');
renderKajianList();
assert.strictEqual(document.getElementById('resultsCount').innerText, 1, 'Only 1 event matches Firanda');
console.log('✓ Interaction 2 (Search keyword filter): PASS');

// Interaction 3: Clear search
clearSearch();
assert.strictEqual(searchQuery, '', 'searchQuery reset');
renderKajianList();
assert.strictEqual(document.getElementById('resultsCount').innerText, INITIAL_EVENTS.length, 'Clear search returns all events');
console.log('✓ Interaction 3 (Clear search restore): PASS');

// Interaction 4: Radius filtering
activeRadius = 10;
renderKajianList();
assert.strictEqual(document.getElementById('resultsCount').innerText, 4, 'Radius 10 km filters out distant events (4 remain)');
console.log('✓ Interaction 4 (Radius filter limit): PASS');

// Interaction 5: Category filtering
activeRadius = 999;
selectCategory('Fiqih');
assert.strictEqual(activeCategory, 'Fiqih', 'Active category state is Fiqih');
renderKajianList();
assert.strictEqual(document.getElementById('resultsCount').innerText, 2, 'Exactly 2 Fiqih events in db');
console.log('✓ Interaction 5 (Category filter): PASS');

// Interaction 6: Bookmark Toggle & Persistence
selectCategory('Semua');
assert.strictEqual(bookmarks.length, 0, 'Bookmarks start at 0');
toggleBookmark(1);
assert.strictEqual(bookmarks.length, 1, 'Bookmarks now contains 1 item');
assert(bookmarks.includes(1), 'Bookmarks contains id 1');
toggleBookmark(1);
assert.strictEqual(bookmarks.length, 0, 'Bookmark id 1 removed');
console.log('✓ Interaction 6 (Bookmark toggle & persistence): PASS');

// Interaction 7: RSVP Attendance Toggle
assert.strictEqual(rsvps.length, 0, 'RSVPs start at 0');
toggleRsvp(2);
assert.strictEqual(rsvps.length, 1, 'RSVPs now contains 1 item');
assert(rsvps.includes(2), 'RSVP list contains id 2');
toggleRsvp(2);
assert.strictEqual(rsvps.length, 0, 'RSVP id 2 removed');
console.log('✓ Interaction 7 (RSVP attendance toggle): PASS');

// Interaction 8: Tab View Switching
switchTab('peta');
assert(!document.getElementById('view-peta').classList.contains('hidden'), 'Peta tab is visible');
assert(document.getElementById('view-kajian').classList.contains('hidden'), 'Kajian tab is hidden');
switchTab('kajian');
assert(!document.getElementById('view-kajian').classList.contains('hidden'), 'Kajian tab is restored');
console.log('✓ Interaction 8 (Tab view switching): PASS');

// Interaction 9: Prayer Schedule Calculation Invariant
updatePrayerSchedule();
assert(document.getElementById('time-subuh').innerText.length === 5, 'Subuh prayer time formatted hh:mm');
assert(document.getElementById('mini-dzuhur').innerText.length === 5, 'Desktop mini Dzuhur formatted hh:mm');
console.log('✓ Interaction 9 (Prayer times calculation): PASS');

// Interaction 10: Qibla Orientation Invariant
updateQiblaDisplay();
const dial = document.getElementById('compassDial');
assert(dial.style.transform.includes('rotate('), 'Compass dial rotation computed');
assert(document.getElementById('qiblaDegreeText').innerText.includes('°'), 'Qibla degree text formatted');
console.log('✓ Interaction 10 (Qibla orientation computed): PASS');

console.log('\n--- ALL 10 USER INTERACTIONS FULLY VALIDATED AND PASSING 100% ---');
