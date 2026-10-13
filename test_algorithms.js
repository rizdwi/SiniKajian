/**
 * Algorithmic Unit Tests for SiniKajian Core (Ponytail / LeetCode Style)
 * Checks mathematical invariants for spherical geometry & search filtering.
 */

const assert = require('assert');

const KAABA_COORDS = { lat: 21.4225, lon: 39.8262 };

function haversine(lat1, lon1, lat2, lon2) {
  const toRad = deg => (deg * Math.PI) / 180;
  const R = 6371; // Earth radius in km
  const dLat = toRad(lat2 - lat1);
  const dLon = toRad(lon2 - lon1);
  const a = Math.sin(dLat / 2) ** 2 +
            Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLon / 2) ** 2;
  return 2 * R * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

function calculateQiblaAzimuth(lat, lon) {
  const toRad = deg => (deg * Math.PI) / 180;
  const toDeg = rad => (rad * 180) / Math.PI;
  const phi1 = toRad(lat);
  const phi2 = toRad(KAABA_COORDS.lat);
  const deltaLambda = toRad(KAABA_COORDS.lon - lon);

  const y = Math.sin(deltaLambda);
  const x = Math.cos(phi1) * Math.tan(phi2) - Math.sin(phi1) * Math.cos(deltaLambda);
  let qibla = toDeg(Math.atan2(y, x));
  return (qibla + 360) % 360;
}

console.log('--- RUNNING SINIKAJIAN ALGORITHM TESTS ---');

// Test 1: Zero distance identity
const dZero = haversine(-6.2, 106.8, -6.2, 106.8);
assert(Math.abs(dZero) < 0.0001, `Expected 0 km, got ${dZero}`);
console.log('Test 1 (Identity): PASS (0 km)');

// Test 2: Known geographical distance (Jakarta to Bandung ~ 118-125 km great circle)
const dJktBdg = haversine(-6.2088, 106.8456, -6.9175, 107.6191);
assert(dJktBdg > 115 && dJktBdg < 125, `Expected ~120 km, got ${dJktBdg}`);
console.log(`Test 2 (Jakarta -> Bandung): PASS (${dJktBdg.toFixed(2)} km)`);

// Test 3: Qibla azimuth from Jakarta
const qiblaJkt = calculateQiblaAzimuth(-6.2088, 106.8456);
assert(qiblaJkt > 294 && qiblaJkt < 296, `Expected ~295.14 deg, got ${qiblaJkt}`);
console.log(`Test 3 (Qibla Azimuth Jakarta): PASS (${qiblaJkt.toFixed(2)} deg)`);

// Test 4: Distance sorting invariant
const points = [
  { id: 1, lat: -6.9175, lon: 107.6191 }, // Bandung (~120 km)
  { id: 2, lat: -6.2100, lon: 106.8500 }, // Jakarta nearby (~0.5 km)
  { id: 3, lat: -6.3000, lon: 106.8400 }, // Jakarta south (~10 km)
];
const user = { lat: -6.2088, lon: 106.8456 };
const sorted = points
  .map(p => ({ ...p, dist: haversine(user.lat, user.lon, p.lat, p.lon) }))
  .sort((a, b) => a.dist - b.dist);

assert.strictEqual(sorted[0].id, 2, 'Closest should be id 2');
assert.strictEqual(sorted[1].id, 3, 'Second should be id 3');
assert.strictEqual(sorted[2].id, 1, 'Furthest should be id 1');
console.log('Test 4 (Monotonic distance sort): PASS');

console.log('ALL 4 ALGORITHMIC INVARIANTS VERIFIED SUCCESSFULLY.');
