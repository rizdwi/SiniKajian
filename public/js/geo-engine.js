/**
 * SiniKajian - Zero-Click Auto Geolocation & Sensor State Manager
 * Author: rizdwi (SiniKajian)
 *
 * Prinsip:
 * 1. Begitu app nyala / page load, LANGSUNG ambil koordinat GPS secara otomatis di background tanpa perlu tombol ditekan.
 * 2. Simpan cache koordinat di localStorage agar saat buka ulang langsung instan (0 detik).
 * 3. Pantau pergerakan secara kontinu menggunakan watchPosition.
 * 4. Hitung sudut Kiblat secara otomatis dan pasang listener sensor orientasi kompas secara otomatis.
 * 5. Siarkan custom event 'sk:location-updated' ke seluruh halaman (Beranda, Peta, Kiblat, Profil).
 */

(function(window) {
  'use strict';

  // Ka'bah Coordinates (Mecca, Saudi Arabia)
  const KAABA_COORDS = { lat: 21.4225, lon: 39.8262 };

  // Daftar kota / wilayah referensi di Indonesia untuk penamaan otomatis dari GPS
  const REGION_CENTROIDS = [
    { name: 'Dago, Bandung', lat: -6.8851, lon: 107.6136, radius: 15 },
    { name: 'Bandung Kota', lat: -6.9175, lon: 107.6191, radius: 25 },
    { name: 'Cimahi, Bandung Barat', lat: -6.8723, lon: 107.5422, radius: 15 },
    { name: 'Jakarta Selatan', lat: -6.2615, lon: 106.8106, radius: 15 },
    { name: 'Jakarta Pusat', lat: -6.1802, lon: 106.8283, radius: 12 },
    { name: 'Jakarta Timur', lat: -6.2250, lon: 106.9004, radius: 15 },
    { name: 'Jakarta Barat', lat: -6.1683, lon: 106.7589, radius: 15 },
    { name: 'Jakarta Utara', lat: -6.1214, lon: 106.8824, radius: 15 },
    { name: 'Bekasi', lat: -6.2383, lon: 106.9756, radius: 20 },
    { name: 'Depok', lat: -6.4025, lon: 106.7942, radius: 15 },
    { name: 'Bogor', lat: -6.5971, lon: 106.8060, radius: 20 },
    { name: 'Tangerang', lat: -6.1783, lon: 106.6319, radius: 20 },
    { name: 'Tangerang Selatan', lat: -6.2888, lon: 106.7179, radius: 15 },
    { name: 'Karawang Barat', lat: -6.3146, lon: 107.2917, radius: 20 },
    { name: 'Cikampek', lat: -6.4061, lon: 107.4589, radius: 15 },
    { name: 'Purwakarta', lat: -6.5569, lon: 107.4433, radius: 20 },
    { name: 'Subang', lat: -6.5714, lon: 107.7602, radius: 25 },
    { name: 'Cirebon', lat: -6.7320, lon: 108.5523, radius: 20 },
    { name: 'Semarang', lat: -6.9667, lon: 110.4167, radius: 25 },
    { name: 'Yogyakarta', lat: -7.7956, lon: 110.3695, radius: 25 },
    { name: 'Surakarta (Solo)', lat: -7.5755, lon: 110.8243, radius: 20 },
    { name: 'Surabaya', lat: -7.2575, lon: 112.7521, radius: 25 },
    { name: 'Malang', lat: -7.9666, lon: 112.6326, radius: 25 },
    { name: 'Medan', lat: 3.5952, lon: 98.6722, radius: 30 },
    { name: 'Makassar', lat: -5.1477, lon: 119.4327, radius: 30 }
  ];

  function haversine(lat1, lon1, lat2, lon2) {
    const R = 6371;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
              Math.sin(dLon / 2) * Math.sin(dLon / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
  }

  function resolveRegionName(lat, lon) {
    let closest = null;
    let minD = Infinity;

    for (let reg of REGION_CENTROIDS) {
      const d = haversine(lat, lon, reg.lat, reg.lon);
      if (d <= reg.radius && d < minD) {
        minD = d;
        closest = reg.name;
      }
    }

    if (closest) return closest;
    const latStr = `${Math.abs(lat).toFixed(2)}° ${lat < 0 ? 'LS' : 'LU'}`;
    const lonStr = `${Math.abs(lon).toFixed(2)}° ${lon < 0 ? 'BB' : 'BT'}`;
    return `Lokasi GPS (${latStr}, ${lonStr})`;
  }

  function reverseGeocode(lat, lon, callback) {
    const url = `https://api.bigdatacloud.net/data/reverse-geocode-client?latitude=${lat}&longitude=${lon}&localityLanguage=id`;
    fetch(url)
      .then(res => res.json())
      .then(data => {
        let locality = data.locality || '';
        let city = data.city || '';
        let province = data.principalSubdivision || '';
        city = city.replace(/^Kota\s+/i, '');
        
        let formatted = '';
        if (locality && city && locality.toLowerCase() !== city.toLowerCase()) {
          formatted = `${locality}, ${city}`;
        } else if (city) {
          formatted = city;
        } else if (province) {
          formatted = province;
        }
        if (formatted) {
          callback(formatted, city, province);
        }
      })
      .catch(() => {});
  }

  function calculateQiblaAzimuth(lat, lon) {
    const phiK = KAABA_COORDS.lat * Math.PI / 180;
    const lambdaK = KAABA_COORDS.lon * Math.PI / 180;
    const phi = lat * Math.PI / 180;
    const lambda = lon * Math.PI / 180;

    const numerator = Math.sin(lambdaK - lambda);
    const denominator = Math.cos(phi) * Math.tan(phiK) - Math.sin(phi) * Math.cos(lambdaK - lambda);

    let q = Math.atan2(numerator, denominator) * 180 / Math.PI;
    return (q + 360) % 360;
  }

  const SiniKajianGeo = {
    current: {
      lat: -6.9175,
      lon: 107.6191,
      name: 'Mendeteksi Lokasi...',
      city: '',
      province: '',
      accuracy: 0,
      isGps: false,
      qiblaAzimuth: 295.2,
      kaabaDistance: 7924
    },
    watchId: null,
    manualAt: 0,
    explicitGpsRequest: false,

    init() {
      // 1. Muat cache dari localStorage untuk respon 0 detik
      try {
        const cached = JSON.parse(localStorage.getItem('sk_geo_state') || 'null');
        if (cached && cached.lat && cached.lon) {
          this.current.lat = Number(cached.lat);
          this.current.lon = Number(cached.lon);
          this.current.name = cached.name || resolveRegionName(this.current.lat, this.current.lon);
          this.current.city = cached.city || '';
          this.current.province = cached.province || '';
          this.current.isGps = !!cached.isGps;
          this.manualAt = Number(cached.manualAt || 0);
          // Migrasi state lama: saveState dulu hanya dipanggil oleh fix
          // GPS (isGps=true) atau pilihan manual (isGps=false) — jadi
          // cache isGps=false tanpa manualAt tetap berarti pilihan manual.
          if (!this.current.isGps && !this.manualAt && cached.timestamp) {
            this.manualAt = Number(cached.timestamp);
          }
        } else {
          const oldLoc = JSON.parse(localStorage.getItem('sk_user_loc') || 'null');
          if (oldLoc && oldLoc.lat && oldLoc.lon) {
            this.current.lat = Number(oldLoc.lat);
            this.current.lon = Number(oldLoc.lon);
            this.current.name = oldLoc.name || resolveRegionName(this.current.lat, this.current.lon);
          }
        }
      } catch (e) {
        console.warn('Gagal membaca cache lokasi:', e);
      }

      this.current.qiblaAzimuth = calculateQiblaAzimuth(this.current.lat, this.current.lon);
      this.current.kaabaDistance = Math.round(haversine(this.current.lat, this.current.lon, KAABA_COORDS.lat, KAABA_COORDS.lon));

      // 2. LANGSUNG nyalakan geolokasi GPS otomatis di background begitu app dibuka
      this.startAutoGps();
    },

    requestLiveGps() {
      if (!navigator.geolocation) {
        alert('Geolocation tidak didukung browser ini.');
        return;
      }
      this.explicitGpsRequest = true;
      navigator.geolocation.getCurrentPosition(
        (pos) => {
          this.handlePositionUpdate(pos, true);
          // User secara eksplisit meminta GPS live: pantau pergerakan
          // selama sesi ini saja (watch tidak pernah dimulai otomatis).
          try { localStorage.setItem('sk:gps-live', '1'); } catch (e) {}
          this.startWatch();
        },
        (err) => {
          this.explicitGpsRequest = false;
          console.warn('GPS Error:', err.message);
          alert('Tidak dapat mendeteksi lokasi GPS. Pastikan izin lokasi aktif pada peramban Anda.');
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
      );
    },

    startWatch() {
      if (this.watchId || !navigator.geolocation) return;
      // Pilihan manual selalu menang — jangan pantau bila user memilih kota.
      if (!this.current.isGps && this.manualAt) return;
      this.watchId = navigator.geolocation.watchPosition(
        (pos) => {
          this.handlePositionUpdate(pos, false);
        },
        (err) => {
          // silent fallback
        },
        { enableHighAccuracy: false, timeout: 15000, maximumAge: 30000 }
      );
    },

    startAutoGps() {
      if (!navigator.geolocation) {
        console.warn('Geolocation tidak didukung browser.');
        return;
      }

      // AUDIT 2026-10-21 (FE-H2/H3): pilihan kota manual TIDAK BOLEH
      // ditimpa GPS otomatis — dan jangan memancing prompt izin / watch
      // permanen ke semua pengunjung. Aturan baru:
      //  - Ada pilihan manual -> berhenti total di sini.
      //  - Izin lokasi SUDAH pernah diberikan -> segarkan satu kali
      //    (one-shot, akurasi hemat), watch hanya bila user pernah
      //    memakai tombol GPS live.
      //  - Izin belum diberikan -> diam; tunggu gestur user (tombol GPS).
      const hasManualChoice = !this.current.isGps && this.manualAt > 0;
      if (hasManualChoice) {
        return;
      }

      const oneShot = () => {
        navigator.geolocation.getCurrentPosition(
          (pos) => {
            this.handlePositionUpdate(pos, true);
          },
          (err) => {
            console.warn('Otomatisasi GPS background:', err.message);
          },
          { enableHighAccuracy: false, timeout: 8000, maximumAge: 60000 }
        );
        try {
          if (localStorage.getItem('sk:gps-live') === '1') this.startWatch();
        } catch (e) {}
      };

      if (navigator.permissions && navigator.permissions.query) {
        navigator.permissions.query({ name: 'geolocation' }).then((status) => {
          if (status.state === 'granted') oneShot();
        }).catch(() => {});
      }
      // Tanpa Permissions API: tidak meminta apa pun secara otomatis.
    },

    handlePositionUpdate(pos, isInitial) {
      const lat = pos.coords.latitude;
      const lon = pos.coords.longitude;
      const accuracy = pos.coords.accuracy;

      // Pilihan manual user menang atas update GPS apa pun, kecuali
      // update ini datang dari tombol GPS yang user tekan sendiri.
      if (!this.current.isGps && this.manualAt > 0 && !this.explicitGpsRequest) {
        return;
      }
      if (this.explicitGpsRequest) {
        this.manualAt = 0;
        this.explicitGpsRequest = false;
      }

      // Cek apakah lokasi berubah signifikan (> 100 meter) atau inisial
      const dist = haversine(this.current.lat, this.current.lon, lat, lon);
      if (isInitial || (this.current.isGps && dist > 0.1)) {
        this.current.lat = lat;
        this.current.lon = lon;
        this.current.accuracy = accuracy;
        this.current.isGps = true;
        this.current.name = resolveRegionName(lat, lon);
        this.current.qiblaAzimuth = calculateQiblaAzimuth(lat, lon);
        this.current.kaabaDistance = Math.round(haversine(lat, lon, KAABA_COORDS.lat, KAABA_COORDS.lon));

        // Simpan ke storage sementara dengan nama centroid
        this.saveState();
        window.dispatchEvent(new CustomEvent('sk:location-updated', { detail: this.current }));

        // Lakukan reverse geocode online untuk mendapatkan nama wilayah / kecamatan akurat
        reverseGeocode(lat, lon, (formattedName, cityName, provinceName) => {
          this.current.name = formattedName;
          this.current.city = cityName;
          this.current.province = provinceName;
          this.saveState();
          window.dispatchEvent(new CustomEvent('sk:location-updated', { detail: this.current }));
        });
      }
    },

    saveState() {
      try {
        const payload = {
          lat: this.current.lat,
          lon: this.current.lon,
          name: this.current.name,
          city: this.current.city || '',
          province: this.current.province || '',
          accuracy: this.current.accuracy,
          isGps: this.current.isGps,
          manualAt: this.manualAt || 0,
          timestamp: Date.now()
        };
        localStorage.setItem('sk_geo_state', JSON.stringify(payload));
        localStorage.setItem('sk_user_loc', JSON.stringify(payload));
      } catch (e) {}
    },

    getCoords() {
      return { ...this.current };
    },

    setManualCity(name, lat, lon) {
      this.current.lat = lat;
      this.current.lon = lon;
      this.current.name = name;
      this.current.isGps = false;
      this.manualAt = Date.now();
      // Berhenti memantau GPS — pilihan manual berlaku sampai user
      // sendiri menekan tombol GPS lagi.
      if (this.watchId) {
        navigator.geolocation.clearWatch(this.watchId);
        this.watchId = null;
      }
      try { localStorage.removeItem('sk:gps-live'); } catch (e) {}
      this.current.qiblaAzimuth = calculateQiblaAzimuth(lat, lon);
      this.current.kaabaDistance = Math.round(haversine(lat, lon, KAABA_COORDS.lat, KAABA_COORDS.lon));

      this.saveState();
      window.dispatchEvent(new CustomEvent('sk:location-updated', { detail: this.current }));
    }
  };

  // Inisialisasi otomatis instan
  SiniKajianGeo.init();
  window.SiniKajianGeo = SiniKajianGeo;

})(window);
