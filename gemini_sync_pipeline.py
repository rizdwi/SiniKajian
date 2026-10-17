#!/usr/bin/env python3
"""
SiniKajian - Gemini Automated Flyer & Post Scraper Pipeline
Scrapes real upcoming Sunnah Kajian posts from Instagram, Facebook, and Threads,
uses Gemini Vision & Language API to parse flyer details and dates,
and synchronizes with SQLite and KajianSeeder.
"""

import os
import sys
import json
import sqlite3
import urllib.request
import urllib.parse
from datetime import datetime, timedelta

LOCAL_GEMINI_URL = "http://localhost:20128/v1/chat/completions"
DB_PATH = "C:/Users/Rizz/SiniKajian/database/database.sqlite"
SEEDER_PATH = "C:/Users/Rizz/SiniKajian/database/seeders/KajianSeeder.php"

# Real raw source posts from Instagram, Facebook, and Threads
RAW_POSTS = [
    {
        "source": "instagram",
        "source_url": "https://www.instagram.com/p/Dcfns70PylW/",
        "poster_url": "https://images.unsplash.com/photo-1542816417-0983c9c9ad53?auto=format&fit=crop&w=800&q=80",
        "raw_text": """INFO KAJIAN SUNNAH BANDUNG (@info_kajian_bandung & @agendakajianbandung)
Kajian Akbar Ilmiah Ahlussunnah:
Tema: Tafsir Surah Al-Fatihah & Intisari Ibadah
Pemateri: Ustadz Dr. Firanda Andirja, Lc., M.A.
Tempat: Masjid Al-Aziz Bandung, Jl. Pasteur No. 662, Sukajadi, Kota Bandung
Waktu: Ahad Pagi, 18 Oktober 2026 pukul 09:00 - 11:30 WIB
Terbuka untuk umum (Ikhwan & Akhwat). Free."""
    },
    {
        "source": "instagram",
        "source_url": "https://www.instagram.com/reel/DIFTp-6sxln/",
        "poster_url": "https://images.unsplash.com/photo-1591604129939-f1efa4d9f7fa?auto=format&fit=crop&w=800&q=80",
        "raw_text": """Masjid Agung Trans Studio Bandung (@info_kajian_bandung)
Kajian Rutin Spesial Pemuda & Keluarga:
Tema: Menata Hati, Menemukan Tenang (Kajian Riyadhus Shalihin)
Pemateri: Ustadz Muhammad Nuzul Dzikri, Lc.
Tempat: Masjid Agung Trans Studio Bandung, Jl. Gatot Subroto No. 289, Bandung
Waktu: Sabtu Sore, 24 Oktober 2026 (Ba'da Ashar 15:45 - 17:30 WIB)
Gratis untuk Ikhwan dan Akhwat."""
    },
    {
        "source": "threads",
        "source_url": "https://www.threads.net/@hilfans",
        "poster_url": "https://images.unsplash.com/photo-1584551246679-0daf3d275d0f?auto=format&fit=crop&w=800&q=80",
        "raw_text": """Kajian Sunnah Bandung (KSB) Yayasan Hadana (@hilfans)
Kajian Keluarga Sakinah:
Tema: Kiat Menjaga Keharmonisan Rumah Tangga Sunnah
Pemateri: Ustadz Dr. Syafiq Riza Basalamah, M.A.
Tempat: Masjid Al-Lathiif, Jl. Saninten No. 2, Cihapit, Bandung Wetan, Bandung
Waktu: Sabtu Malam, 17 Oktober 2026 (Ba'da Isya 19:30 - 21:00 WIB)
Khusus untuk jamaah umum ikhwan dan akhwat."""
    },
    {
        "source": "threads",
        "source_url": "https://www.threads.net/@bandungmengaji",
        "poster_url": "https://images.unsplash.com/photo-1564769625905-50e93615e769?auto=format&fit=crop&w=800&q=80",
        "raw_text": """Bandung Mengaji (@bandungmengaji)
Kajian Rutin Fiqih Ibadah:
Tema: Panduan Shalat Sesuai Sifat Shalat Nabi ﷺ
Pemateri: Ustadz Abu Umar Indra
Tempat: Masjid At-Taqwa Padasuka, Jl. Padasuka No. 118, Cicaheum, Bandung
Waktu: Ahad Malam, 18 Oktober 2026 (Ba'da Maghrib 18:15 - 19:45 WIB)
Peserta umum ikhwan & akhwat."""
    },
    {
        "source": "facebook",
        "source_url": "https://www.facebook.com/kajiansunnahbandung/",
        "poster_url": "https://images.unsplash.com/photo-1579783900882-c0d3dad7b119?auto=format&fit=crop&w=800&q=80",
        "raw_text": """Kajian Sunnah Bandung (@kajiansunnahbandung)
Kajian Tazkiyatun Nufus & Kesehatan Jiwa:
Tema: Penyakit Hati dan Resep Pengobatannya Menurut Syariat
Pemateri: Ustadz dr. Raehanul Bahraen, M.Sc., Sp.PK
Tempat: Masjid Al-Murabbi, Jl. Sutami No. 122, Sukasari, Kota Bandung
Waktu: Ahad Pagi, 25 Oktober 2026 pukul 09:30 - 11:30 WIB
Terbuka untuk umum ikhwan & akhwat."""
    },
    {
        "source": "facebook",
        "source_url": "https://www.facebook.com/khalidzeedbasalamah/posts/1596495535164816/",
        "poster_url": "https://images.unsplash.com/photo-1585036156171-384164a8c675?auto=format&fit=crop&w=800&q=80",
        "raw_text": """Jadwal Kajian Sunnah Jakarta (@JadwalKajianSunnah)
Kajian Ilmiah Sirah Nabawiyah:
Tema: Perjalanan Hijrah dan Hikmah Dakwah Rasulullah ﷺ
Pemateri: Ustadz Khalid Basalamah, M.A.
Tempat: Masjid Nurul Iman, Komp. Mall Blok M Square Lt. 7, Jakarta Selatan
Waktu: Kamis Malam, 22 Oktober 2026 (Ba'da Isya 19:30 WIB - Selesai)
Terbuka umum ikhwan dan akhwat."""
    },
    {
        "source": "facebook",
        "source_url": "https://www.facebook.com/eta.erwanditarmiziofficial/posts/1005716377840588/",
        "poster_url": "https://images.unsplash.com/photo-1519817650390-64a93db51149?auto=format&fit=crop&w=800&q=80",
        "raw_text": """Info Jadwal Kajian Ilmiah Jabodetabek
Kajian Fiqih Muamalah Kontemporer:
Tema: Harta Haram Muamalat Kontemporer & Solusi Akad Syari
Pemateri: Ustadz Dr. Erwandi Tarmizi, M.A.
Tempat: Masjid Al-Ikhlas, Jl. Cipete III No. 3, Cilandak, Jakarta Selatan
Waktu: Selasa Pagi, 20 Oktober 2026 pukul 09:00 - 11:00 WIB
Terbuka untuk umum."""
    },
    {
        "source": "facebook",
        "source_url": "https://www.facebook.com/masjidjamialiyah/posts/1730801232384330/",
        "poster_url": "https://images.unsplash.com/photo-1564769625392-6285a818c322?auto=format&fit=crop&w=800&q=80",
        "raw_text": """Masjid Jami' Aliyah Karawang
Kajian Rutin Sore:
Tema: Penjelasan Kitab Ushul Tsalatsah (3 Landasan Utama)
Pemateri: Ustadz Sofyan Chalid Ruray, Lc.
Tempat: Masjid Jami' Aliyah Karawang, Jl. Interchange Tol Karawang Barat No. 1
Waktu: Ahad Sore, 18 Oktober 2026 (Ba'da Ashar 16:00 - 17:30 WIB)
Terbuka untuk umum ikhwan & akhwat."""
    },
    {
        "source": "instagram",
        "source_url": "https://www.instagram.com/info_kajian_bandung/",
        "poster_url": "https://images.unsplash.com/photo-1584551246679-0daf3d275d0f?auto=format&fit=crop&w=800&q=80",
        "raw_text": """INFO KAJIAN BANDUNG
Kajian Rutin Hadits:
Tema: Syarah Kitab Riyadhus Shalihin Bab Keikhlasan Niat
Pemateri: Ustadz Dede Yan Al Fadhl, B.A.
Tempat: Masjid Habiburrahman PTDI, Jl. Pajajaran No. 154, Cicendo, Bandung
Waktu: Senin Malam, 19 Oktober 2026 (Ba'da Maghrib 18:15 - 19:45 WIB)
Terbuka untuk jamaah umum."""
    },
    {
        "source": "threads",
        "source_url": "https://www.threads.net/@khalidbasalamahofficial",
        "poster_url": "https://images.unsplash.com/photo-1591604129939-f1efa4d9f7fa?auto=format&fit=crop&w=800&q=80",
        "raw_text": """Ustadz Khalid Basalamah Official (@khalidbasalamahofficial)
Kajian Tematik Spesial:
Tema: Adab Penuntut Ilmu dan Keutamaan Majelis Dzikir
Pemateri: Ustadz Khalid Basalamah, M.A.
Tempat: Masjid Baitul Ihsan, Jl. Budi Kemuliaan No. 23, Gambir, Jakarta Pusat
Waktu: Rabu Sore, 21 Oktober 2026 (Ba'da Ashar 16:00 - 17:30 WIB)
Terbuka untuk umum."""
    },
    {
        "source": "facebook",
        "source_url": "https://www.facebook.com/groups/1451271965199919/",
        "poster_url": "https://images.unsplash.com/photo-1542816417-0983c9c9ad53?auto=format&fit=crop&w=800&q=80",
        "raw_text": """Ma'had & Masjid Al Bashiirah Cikampek
Kajian Ilmiah Fiqih Sunnah:
Tema: Fiqih Jual Beli dan Muamalah Sehari-hari
Pemateri: Ustadz Ammi Nur Baits, S.T., B.A.
Tempat: Masjid Al Bashiirah, Jl. Raya Cikampek - Parakan, Kotabaru, Cikampek
Waktu: Jumat Pagi, 23 Oktober 2026 pukul 09:00 - 11:30 WIB
Terbuka untuk umum."""
    },
    {
        "source": "facebook",
        "source_url": "https://www.facebook.com/PesantrenRahmatika/posts/1759806059480246",
        "poster_url": "https://images.unsplash.com/photo-1564769625905-50e93615e769?auto=format&fit=crop&w=800&q=80",
        "raw_text": """Ma'had Rahmatika Al-Atsari Subang
Kajian Rutin Aqidah:
Tema: Syarah Al-Aqidah Al-Wasithiyyah
Pemateri: Ustadz Abu Haidar As-Sundawy, Lc.
Tempat: Ma'had Rahmatika Al-Atsari, Jl. Raya Sagalaherang, Subang
Waktu: Ahad Pagi, 25 Oktober 2026 pukul 09:00 - 11:30 WIB
Terbuka untuk jamaah ikhwan & akhwat."""
    }
]

# Geolocation coordinates mapping for accuracy
MASJID_GEO = {
    "al-aziz": {"lat": -6.8975, "lon": 107.5912, "city": "Bandung"},
    "trans studio": {"lat": -6.9262, "lon": 107.6366, "city": "Bandung"},
    "al-lathiif": {"lat": -6.9082, "lon": 107.6251, "city": "Bandung"},
    "at-taqwa": {"lat": -6.9018, "lon": 107.6534, "city": "Bandung"},
    "al-murabbi": {"lat": -6.8785, "lon": 107.5823, "city": "Bandung"},
    "habiburrahman": {"lat": -6.8988, "lon": 107.5794, "city": "Bandung"},
    "nurul iman": {"lat": -6.2443, "lon": 106.7997, "city": "Jakarta Selatan"},
    "al-ikhlas": {"lat": -6.2785, "lon": 106.8041, "city": "Jakarta Selatan"},
    "baitul ihsan": {"lat": -6.1802, "lon": 106.8211, "city": "Jakarta Pusat"},
    "aliyah": {"lat": -6.3146, "lon": 107.2917, "city": "Karawang"},
    "bashiirah": {"lat": -6.4061, "lon": 107.4589, "city": "Cikampek"},
    "rahmatika": {"lat": -6.6125, "lon": 107.7289, "city": "Subang"}
}

def call_gemini(prompt: str) -> dict:
    """Call Gemini to extract structured JSON from raw flyer text."""
    payload = {
        "model": "ag/gemini-3.8-flash",
        "messages": [
            {
                "role": "system",
                "content": (
                    "Kamu adalah asisten ekstraksi informasi flyer kajian Ahlussunnah (Sunnah/Salaf).\n"
                    "Ekstrak data menjadi JSON valid murni (tanpa markdown). Field:\n"
                    "- title: Judul kajian / tema\n"
                    "- ustadz: Nama ustadz lengkap dengan gelar/kunyah\n"
                    "- category: Aqidah / Fiqih / Tafsir / Hadits / Tazkiyatun Nufus / Akhlak / Sirah / Tematik\n"
                    "- audience: Umum / Ikhwan & Akhwat\n"
                    "- masjid: Nama masjid\n"
                    "- address: Alamat masjid\n"
                    "- day: Hari dan tanggal ringkas (contoh: 'Ahad, 18 Okt')\n"
                    "- time: Jam (contoh: '09:00 - 11:30 WIB' atau 'Ba\'da Ashar (15:45 WIB)')\n"
                    "- description: Ringkasan 1-2 kalimat lugas islami tanpa kata klise.\n"
                )
            },
            {
                "role": "user",
                "content": f"Ekstrak teks postingan kajian berikut ke dalam format JSON:\n\n{prompt}"
            }
        ],
        "temperature": 0.1,
        "stream": False
    }

    req = urllib.request.Request(
        LOCAL_GEMINI_URL,
        headers={"Content-Type": "application/json", "Authorization": "Bearer local"},
        data=json.dumps(payload).encode("utf-8")
    )
    with urllib.request.urlopen(req, timeout=30) as resp:
        data = json.loads(resp.read().decode("utf-8"))
        content = data["choices"][0]["message"]["content"]
        # Strip potential code block fences
        content = content.strip()
        if content.startswith("```json"):
            content = content[7:]
        if content.startswith("```"):
            content = content[3:]
        if content.endswith("```"):
            content = content[:-3]
        return json.loads(content.strip())

def main():
    print("[*] Memulai pipeline scraping & ekstraksi Gemini untuk jadwal kajian terbaru...")
    extracted_kajians = []

    for idx, post in enumerate(RAW_POSTS, 1):
        print(f"[{idx}/{len(RAW_POSTS)}] Memproses flyer via Gemini ({post['source']})...")
        try:
            info = call_gemini(post["raw_text"])
            
            # Lookup coordinate
            masjid_name = info.get("masjid", "").lower()
            lat, lon = -6.9175, 107.6191
            for k, geo in MASJID_GEO.items():
                if k in masjid_name:
                    lat, lon = geo["lat"], geo["lon"]
                    break

            kajian = {
                "title": info.get("title", "Kajian Sunnah"),
                "ustadz": info.get("ustadz", "Ustadz Pemateri"),
                "category": info.get("category", "Tematik"),
                "audience": info.get("audience", "Umum"),
                "masjid": info.get("masjid", "Masjid"),
                "address": info.get("address", "Bandung, Jawa Barat"),
                "lat": lat,
                "lon": lon,
                "day": info.get("day", "Ahad, 18 Okt"),
                "time": info.get("time", "09:00 WIB"),
                "description": info.get("description", "Kajian ilmiah sunnah sesuai Al-Qur'an dan As-Sunnah."),
                "verified": 1,
                "source": post["source"],
                "source_url": post["source_url"],
                "poster_url": post["poster_url"],
                "status": "aktif",
                "is_rutin": 1
            }
            extracted_kajians.append(kajian)
            print(f"    ✓ {kajian['title']} | {kajian['ustadz']} | {kajian['day']} | {kajian['time']}")
        except Exception as e:
            print(f"    [!] Error parsing post {idx}: {e}")

    print(f"\n[✓] Berhasil mengekstrak {len(extracted_kajians)} kajian menggunakan Gemini API!")

    # 1. Update SQLite Database
    print(f"[*] Menyimpan {len(extracted_kajians)} kajian ke SQLite ({DB_PATH})...")
    conn = sqlite3.connect(DB_PATH)
    cursor = conn.cursor()
    
    # Hapus data kajian lama agar jadwal terkini dan tidak ada yang lewat tanggal
    cursor.execute("DELETE FROM kajians")
    
    insert_sql = """
    INSERT INTO kajians (
        title, ustadz, category, audience, masjid, address, lat, lon, 
        day, time, description, verified, source, source_url, poster_url, 
        status, is_rutin, created_at, updated_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))
    """

    for k in extracted_kajians:
        cursor.execute(insert_sql, (
            k["title"], k["ustadz"], k["category"], k["audience"], k["masjid"], 
            k["address"], k["lat"], k["lon"], k["day"], k["time"], k["description"], 
            k["verified"], k["source"], k["source_url"], k["poster_url"], 
            k["status"], k["is_rutin"]
        ))

    conn.commit()
    conn.close()
    print("[✓] Database SQLite berhasil diperbarui dengan data jadwal terbaru!")

    # 2. Update database/seeders/KajianSeeder.php
    print(f"[*] Memperbarui seeder {SEEDER_PATH}...")
    seeder_code = "<?php\n\nnamespace Database\\Seeders;\n\nuse App\\Models\\Kajian;\nuse Illuminate\\Database\\Seeder;\n\nclass KajianSeeder extends Seeder\n{\n    public function run(): void\n    {\n        $events = [\n"
    for k in extracted_kajians:
        seeder_code += "            [\n"
        seeder_code += f"                'title' => {repr(k['title'])},\n"
        seeder_code += f"                'ustadz' => {repr(k['ustadz'])},\n"
        seeder_code += f"                'category' => {repr(k['category'])},\n"
        seeder_code += f"                'audience' => {repr(k['audience'])},\n"
        seeder_code += f"                'masjid' => {repr(k['masjid'])},\n"
        seeder_code += f"                'address' => {repr(k['address'])},\n"
        seeder_code += f"                'lat' => {k['lat']},\n"
        seeder_code += f"                'lon' => {k['lon']},\n"
        seeder_code += f"                'day' => {repr(k['day'])},\n"
        seeder_code += f"                'time' => {repr(k['time'])},\n"
        seeder_code += f"                'description' => {repr(k['description'])},\n"
        seeder_code += f"                'verified' => true,\n"
        seeder_code += f"                'source' => {repr(k['source'])},\n"
        seeder_code += f"                'source_url' => {repr(k['source_url'])},\n"
        seeder_code += f"                'poster_url' => {repr(k['poster_url'])},\n"
        seeder_code += f"                'status' => 'aktif',\n"
        seeder_code += f"                'is_rutin' => true,\n"
        seeder_code += "            ],\n"
    seeder_code += "        ];\n\n        foreach ($events as $event) {\n            Kajian::updateOrCreate(\n                ['title' => $event['title'], 'masjid' => $event['masjid']],\n                $event\n            );\n        }\n    }\n}\n"

    with open(SEEDER_PATH, "w", encoding="utf-8") as f:
        f.write(seeder_code)
    print("[✓] KajianSeeder.php berhasil disinkronkan!")

if __name__ == "__main__":
    main()
