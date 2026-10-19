# SiniKajian

**Aplikasi Pencarian Majelis Ilmu Sunnah, Waktu Sholat Astronomis & Kompas Kiblat Presisi**  
*Ultra-lightweight, zero-build Progressive Web App didukung oleh arsitektur Laravel 13 & Design System Tokens.*

[![Live Demo](https://img.shields.io/badge/Demo-sinikajian.vercel.app-0C5C40?style=for-the-badge&logo=vercel&logoColor=white)](https://sinikajian.vercel.app)
[![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PWA](https://img.shields.io/badge/PWA-Installable-0C5C40?style=for-the-badge&logo=pwa&logoColor=white)](https://sinikajian.vercel.app)
[![Design Tokens](https://img.shields.io/badge/DESIGN.md-Google%20Token%20Spec-4285F4?style=for-the-badge)](DESIGN.md)

---

## Ringkasan Fitur

1. **Jadwal Kajian Sunnah Real-Time:** Filter kategori majelis ilmu (Aqidah, Fiqih, Hadits, Sirah, Tazkiyatun Nufus, Bahasa Arab) serta jarak radius terdekat dengan formula Haversine geospasial.
2. **Peta Interaktif Sebaran Majelis:** Didukung Leaflet dengan tile CartoDB Voyager dan bottom sheet drawer info detail.
3. **Kompas Arah Kiblat Presisi:** Formula spherical azimuth langsung ke Ka'bah ($21.4225^\circ\text{ N}, 39.8262^\circ\text{ E}$) dipadu sensor gyro / accelerometer mobile dan getaran haptic feedback.
4. **Waktu Sholat Astronomis:** Algoritma posisi matahari mandiri dengan penyesuaian sudut ikhtiyat dan hitung mundur menuju waktu sholat berikutnya.
5. **Autentikasi & Keamanan Kredensial Pengguna:** Form login & register dengan hashing bcrypt, proteksi CSRF, sesi cookie terenkripsi, dan proteksi credential leakage.
6. **Koleksi & Riwayat Kehadiran:** Simpan kajian favorit serta logging presensi majelis ilmu yang telah dihadiri.
7. **PWA Standalone & Offline Shell:** Dilengkapi Service Worker v13.0, manifest maskable icon, dan cache stale-while-revalidate.

---

## Arsitektur Teknis

```
SiniKajian/
├── DESIGN.md                 # Token desain Google Design.md (linter terverifikasi)
├── tailwind.theme.json       # Export tokens untuk styling Tailwind
├── app/
│   ├── Http/Controllers/    # KajianController, AuthController
│   └── Models/              # Kajian, User, Bookmark, Attendance
├── database/
│   ├── migrations/          # Migrasi tabel users, kajians, bookmarks, attendances
│   ├── seeders/             # KajianSeeder (13 data kajian sunnah awal)
│   └── database.sqlite      # SQLite database mandiri
├── resources/views/
│   ├── layouts/app.blade.php # Layout utama PWA (bottom navigation bar & safe area)
│   ├── kajian/              # Views: index, map, qibla, saved
│   └── auth/                # Views: login, register, profile
├── public/
│   ├── index.php            # Front controller
│   ├── sw.js                # Service Worker PWA v13.0
│   └── manifest.json        # Web App Manifest standalone
├── routes/web.php           # Routing web & API JSON
├── vercel.json              # Konfigurasi Single-Lambda vercel-php@0.7.3 & headers
└── api/index.php            # Bridge serverless ephemeral /tmp SQLite untuk Vercel
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

## Menjalankan di Lingkungan Lokal

```bash
# 1. Pastikan dependensi terpasang
composer install

# 2. Setup database dan seeder data
touch database/database.sqlite
php artisan migrate --seed

# 3. Jalankan development server
php artisan serve
# Kunjungi http://127.0.0.1:8000
```

---

---

<p align="center">
  <sub>© 2026 Rizky Dwi — SiniKajian · Laravel 13 · PWA · Vercel Serverless</sub>
</p>
