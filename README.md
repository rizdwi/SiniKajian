# SiniKajian

PWA (Progressive Web App) pencarian majelis ilmu sunnah, jadwal sholat astronomis presisi, dan kompas arah kiblat dengan antarmuka native mobile yang ringan, ergonomis, dan cepat.

[![Live Demo](https://img.shields.io/badge/Demo-Vercel-black?style=flat-square&logo=vercel)](https://sinikajian.vercel.app)
[![PWA](https://img.shields.io/badge/PWA-Installable-1E5E3A?style=flat-square)](https://sinikajian.vercel.app)
[![License](https://img.shields.io/badge/License-MIT-blue?style=flat-square)](LICENSE)

---

## Fitur Utama

- **Navigasi Native Mobile PWA:** Navigasi bawah permanen (Kajian, Peta, Kiblat, Tersimpan) dengan safe-area inset dan micro-interactions responsif.
- **Pencarian & Radius Geospasial:** Formula Haversine mandiri untuk menghitung jarak presisi dari titik lokasi pengguna ke lokasi masjid tanpa beban server.
- **Peta Sebaran Interaktif:** Didukung Leaflet interaktif untuk navigasi spasial majelis ilmu di sekitar Anda.
- **Waktu Sholat Presisi:** Kalkulasi astronomis waktu sholat surya (Subuh, Terbit, Dzuhur, Ashar, Maghrib, Isya) dengan hitung mundur detik real-time.
- **Kompas Kiblat:** Perhitungan azimuth bola Ka'bah (Mekkah) dengan dukungan sensor orientasi kompas perangkat mobile.
- **Bookmark & Kehadiran Offline:** Penyimpanan lokal favorit dan konfirmasi kehadiran majelis ilmu.
- **Integrasi Supabase:** Skema database PostgreSQL dengan Row Level Security (RLS) untuk antrean moderasi kajian baru.
- **Offline Shell PWA:** Service Worker dengan strategi cache-first untuk aset inti dan manifest standalone.

---

## Arsitektur Teknis

```
SiniKajian/
├── index.html            # Single Page Application (Tailwind CSS, Leaflet, ES6+)
├── manifest.json         # Web App Manifest untuk instalasi layar utama
├── sw.js                 # Service Worker offline caching & fallback
├── vercel.json           # Konfigurasi routing Vercel, region sin1, cache headers
├── supabase/
│   └── schema.sql        # Migrasi tabel kajian, indeks, dan kebijakan RLS
├── test_algorithms.js    # Pengujian invariant matematika (Haversine, Kiblat, Sholat)
└── test_interactive.js   # Pengujian suite interaksi DOM dan fungsionalitas
```

---

## Logika Matematika Mandiri

1. **Jarak Haversine ($d$):**
   $$a = \sin^2\left(\frac{\Delta\phi}{2}\right) + \cos(\phi_1)\cos(\phi_2)\sin^2\left(\frac{\Delta\lambda}{2}\right)$$
   $$d = 2R \cdot \text{atan2}\left(\sqrt{a}, \sqrt{1-a}\right) \quad (R = 6371\text{ km})$$

2. **Arah Kiblat Ka'bah ($\theta$):**
   $$\theta = \text{atan2}\left(\sin(\Delta\lambda), \cos(\phi)\tan(\phi_k) - \sin(\phi)\cos(\Delta\lambda)\right)$$
   Titik Ka'bah: $21.4225^\circ\text{ N}, 39.8262^\circ\text{ E}$.

---

## Setup & Menjalankan Lokal

```bash
# Jalankan server lokal
python -m http.server 4321

# Buka di browser
# http://127.0.0.1:4321
```

### Menjalankan Pengujian

```bash
node test_algorithms.js
node test_interactive.js
```

---

## Deploy ke Vercel

```bash
npx --yes vercel link --yes
npx --yes vercel deploy --prod --yes
```

---

## Integrasi Database Supabase

Jalankan script SQL di file `supabase/schema.sql` pada menu **SQL Editor** di dashboard Supabase Anda. Tabel `public.kajian` akan otomatis terkonfigurasi dengan Row Level Security dan data awal terverifikasi.
