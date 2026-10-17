---
version: alpha
name: SiniKajian
description: Native-feel Islamic knowledge finder, astronomical prayer times, and Qibla compass with lightweight, high-contrast, tactile UI.
colors:
  primary: "#1D4D4F"
  secondary: "#235B5E"
  tertiary: "#E6F4F1"
  neutral: "#F8FAF9"
  surface: "#FFFFFF"
  card-border: "#E5ECEB"
  text-primary: "#1C2224"
  text-secondary: "#607072"
  text-inverse: "#FFFFFF"
  gold-accent: "#FCD34D"
typography:
  h1:
    fontFamily: Plus Jakarta Sans
    fontSize: 1.5rem
    fontWeight: 700
    lineHeight: 1.2
    letterSpacing: "-0.02em"
  h2:
    fontFamily: Plus Jakarta Sans
    fontSize: 1.25rem
    fontWeight: 600
    lineHeight: 1.3
  body-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 0.9375rem
    fontWeight: 400
    lineHeight: 1.5
  body-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 0.8125rem
    fontWeight: 400
    lineHeight: 1.4
  arabic:
    fontFamily: Amiri
    fontSize: 1.125rem
    fontWeight: 700
    lineHeight: 1.6
rounded:
  sm: 6px
  md: 12px
  lg: 16px
  xl: 24px
  full: 9999px
spacing:
  xs: 4px
  sm: 8px
  md: 12px
  lg: 16px
  xl: 24px
  xxl: 32px
components:
  button-primary:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.text-inverse}"
    rounded: "{rounded.lg}"
    padding: 12px
  button-primary-hover:
    backgroundColor: "{colors.secondary}"
    textColor: "{colors.text-inverse}"
  button-secondary:
    backgroundColor: "{colors.neutral}"
    textColor: "{colors.primary}"
    rounded: "{rounded.lg}"
    padding: 12px
  kajian-card:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.text-primary}"
    rounded: "{rounded.xl}"
    padding: 16px
  input-field:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.text-primary}"
    rounded: "{rounded.lg}"
    padding: 12px
  metadata-text:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.text-secondary}"
  prayer-highlight:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.gold-accent}"
  card-divider:
    backgroundColor: "{colors.card-border}"
    textColor: "{colors.text-primary}"
---

## Overview

SiniKajian adalah panduan penunjuk majelis ilmu sunnah, waktu sholat surya presisi, dan kompas kiblat yang dirancang dengan prinsip mobile-first native PWA, tipografi jernih, dan interaksi taktil yang ergonomis.

## Colors

- **Primary (#0C5C40):** Hijau Zamrud (Deep Emerald) sebagai identitas spiritual utama yang tenang dan berwibawa.
- **Secondary (#2C654C):** Hijau Hutan (Forest Green) untuk elemen sekunder dan state aktif.
- **Tertiary (#B45309):** Amber / Warm Gold untuk sorotan waktu sholat aktif dan penanda jarak.
- **Neutral (#FAF8F3):** Warm Alabaster Sand sebagai latar belakang kanvas agar mata tidak lelah.
- **Surface (#FFFFFF):** Putih murni untuk kartu konten (cards) dan lembar aksi (action sheets).
- **Text Primary (#0F172A):** Slate pekat untuk kontras teks tajam (WCAG AAA 15:1).
- **Text Secondary (#475569):** Slate sedang untuk metadata lokasi, ustadz, dan jam pelaksanaan.

## Typography

- **Plus Jakarta Sans:** Tipografi utama antarmuka untuk seluruh teks, tombol, formulir, dan metadata. Dipilih karena keterbacaan tinggi pada layar mobile resolusi padat.
- **Amiri:** Tipografi serif Arab berbobot untuk kutipan hadits, basmalah, dan istilah syar'i agar memiliki martabat visual.

## Layout

- **Mobile First Safe Area:** Memanfaatkan `env(safe-area-inset-top)` dan `env(safe-area-inset-bottom)` agar tata letak nyaman pada notch layar dan home bar gesture iOS/Android.
- **Bottom Navigation Bar:** 5 titik sentuh utama (Kajian, Peta, Kiblat, Tersimpan, Akun) setinggi minimal 48px untuk kemudahan navigasi satu ibu jari.
- **Sheet Pattern:** Filter, detail kajian, dan formulir login menggunakan modal sheet yang dapat digeser dan ditutup cepat dengan tombol Esc atau tap overlay.

## Elevation & Depth

- Tidak menggunakan shadow berlebihan atau efek mengambang palsu.
- Permukaan kartu dipisahkan dengan border halus 1px solid `#E2E8F0` pada tema terang dan `#1E293B` pada tema gelap.
- Elevasi nyata hanya diberikan pada Bottom Sheet dan Action Drawer saat aktif.

## Shapes

- Radius sudut terstruktur: 12px untuk elemen input dan tombol, 16px untuk kartu kajian dan panel waktu sholat, 24px untuk dialog modal.
- Menghindari pill-shape berlebihan pada seluruh kartu agar hierarki visual tetap tegas.

## Components

- **`button-primary`**: Tombol tindakan utama dengan warna hijau zamrud `#0C5C40`, teks putih tebal, dan transisi sentuh halus.
- **`kajian-card`**: Kartu representasi kajian dengan chip kategori beraksen lembut, badge waktu/hari, nama masjid, dan tombol aksi rute / simpan.
- **`input-field`**: Kolom input formulir login dan pencarian dengan border jelas, indikator fokus ring-2 hijau, dan label aksesibel.

## Do's and Don'ts

### Do's
- Gunakan data geospasial riil dengan kalkulasi Haversine langsung pada perangkat pengguna.
- Pastikan setiap interaksi formulir login memiliki validasi CSRF dan pesan kesalahan yang spesifik.
- Sediakan fallback kompas sentuh manual jika sensor giroskop mobile tidak tersedia.

### Don'ts
- Jangan menggunakan em dash (—) pada teks antarmuka sesuai standar antislop.
- Jangan menggunakan angka palsu atau statistik fiktif pada halaman akun.
- Jangan membebani halaman dengan animasi berulang yang tidak memiliki tujuan UX.
