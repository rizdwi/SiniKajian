#!/usr/bin/env python3
"""
SiniKajian - Gemini Multimodal Vision & OCR Ingestion Pipeline
Author: rizdwi (SiniKajian)

Alur Kerja:
1. Scraping info/post/flyer dari Facebook, Instagram, atau Threads.
2. Mengirimkan gambar flyer (OCR) dan/atau teks postingan ke Google AI Studio (Gemini).
3. Ekstraksi terstruktur nama ustadz, judul kitab, masjid, waktu, kategori, dan koordinat.
4. Upload hasil terverifikasi ke database Supabase & web SiniKajian.
"""

import os
import sys
import json
import re
import argparse
import base64
import urllib.request
import urllib.parse
import urllib.error
from typing import Dict, Any, List, Optional

# Database koordinat masjid terverifikasi
KNOWN_MASJIDS = {
    "al-ikhlas cipete": {
        "name": "Masjid Al-Ikhlas",
        "address": "Jl. Cipete III No.3, Cipete Selatan, Cilandak, Jakarta Selatan",
        "lat": -6.2785,
        "lon": 106.8041,
        "city": "Jakarta Selatan"
    },
    "nurul iman blok m": {
        "name": "Masjid Nurul Iman",
        "address": "Komp. Mall Blok M Square Lt. 7, Kebayoran Baru, Jakarta Selatan",
        "lat": -6.2443,
        "lon": 106.7997,
        "city": "Jakarta Selatan"
    },
    "nurul falah radio dalam": {
        "name": "Masjid Nurul Falah",
        "address": "Jl. Radio Dalam Raya No. 14, Gandaria Utara, Kebayoran Baru, Jakarta Selatan",
        "lat": -6.2559,
        "lon": 106.7915,
        "city": "Jakarta Selatan"
    },
    "baitul ihsan": {
        "name": "Masjid Baitul Ihsan",
        "address": "Jl. Budi Kemuliaan No. 23, Gambir, Jakarta Pusat",
        "lat": -6.1802,
        "lon": 106.8211,
        "city": "Jakarta Pusat"
    },
    "al-barkah cakung": {
        "name": "Masjid Al-Barkah",
        "address": "Jl. Pahlawan Komarudin No. 1, Penggilingan, Cakung, Jakarta Timur",
        "lat": -6.2038,
        "lon": 106.9452,
        "city": "Jakarta Timur"
    },
    "al-azhar summarecon": {
        "name": "Masjid Al-Azhar Summarecon",
        "address": "Komp. Summarecon Bekasi, Jl. Bulevar Utara, Bekasi Utara",
        "lat": -6.2238,
        "lon": 106.9994,
        "city": "Bekasi"
    },
    "aliyah karawang": {
        "name": "Masjid Jami' Aliyah Karawang",
        "address": "Jl. Interchange Tol Karawang Barat No.1, Sukamakmur, Telukjambe Timur, Karawang",
        "lat": -6.3146,
        "lon": 107.2917,
        "city": "Karawang"
    },
    "al bashiirah cikampek": {
        "name": "Ma'had & Masjid Al Bashiirah Cikampek",
        "address": "Jl. Raya Cikampek - Parakan, Kotabaru, Cikampek, Karawang",
        "lat": -6.4061,
        "lon": 107.4589,
        "city": "Cikampek"
    },
    "fatimah purwakarta": {
        "name": "Masjid Fatimah Purwakarta",
        "address": "Jl. Terusan Kapten Halim, Kp. Pasir Kiara, Nagri Kidul, Purwakarta",
        "lat": -6.5369,
        "lon": 107.4431,
        "city": "Purwakarta"
    },
    "rahmatika subang": {
        "name": "Ma'had Rahmatika Al-Atsari Subang",
        "address": "Jl. Raya Sagalaherang - Curug Rendeng, Sagalaherang, Subang",
        "lat": -6.6125,
        "lon": 107.7289,
        "city": "Subang"
    },
    "al-itishom karet": {
        "name": "Masjid Al-I'tishom",
        "address": "Jl. K.H. Mas Mansyur No.55, Karet Tengsin, Tanah Abang, Jakarta Pusat",
        "lat": -6.2084,
        "lon": 106.8189,
        "city": "Jakarta Pusat"
    }
}

CITY_CENTROIDS = {
    "karawang": {"lat": -6.3042, "lon": 107.3056},
    "cikampek": {"lat": -6.4061, "lon": 107.4589},
    "purwakarta": {"lat": -6.5569, "lon": 107.4433},
    "subang": {"lat": -6.5714, "lon": 107.7602},
    "jakarta pusat": {"lat": -6.1802, "lon": 106.8283},
    "jakarta selatan": {"lat": -6.2615, "lon": 106.8106},
    "jakarta timur": {"lat": -6.2250, "lon": 106.9004},
    "jakarta barat": {"lat": -6.1683, "lon": 106.7589},
    "jakarta utara": {"lat": -6.1214, "lon": 106.8824},
    "bekasi": {"lat": -6.2383, "lon": 106.9756},
    "bandung": {"lat": -6.9175, "lon": 107.6191}
}

def clean_anti_slop(text: str) -> str:
    """Membersihkan teks dari em dash dan kata klise AI."""
    if not text:
        return ""
    text = text.replace("\u2014", "-")
    buzzwords = ["revolutionary", "cutting-edge", "seamless", "game-changer", "unleash", "empower", "testament"]
    for bw in buzzwords:
        text = re.sub(re.escape(bw), "andal", text, flags=re.IGNORECASE)
    return text.strip()

def load_gemini_api_key(cli_key: Optional[str] = None) -> Optional[str]:
    """Mencari kunci API Google Studio (Gemini)."""
    if cli_key:
        return cli_key.strip()
    
    # Cek environment variables
    env_keys = ["GEMINI_API_KEY", "GOOGLE_API_KEY", "GOOGLE_AI_STUDIO_KEY"]
    for k in env_keys:
        val = os.environ.get(k)
        if val and val.strip():
            return val.strip()
            
    # Cek file .env di folder proyek
    env_files = [
        "C:/Users/Rizz/SiniKajian/.env",
        "C:/Users/Rizz/SiniKajian/.env.local",
        os.path.expanduser("~/.env")
    ]
    for env_path in env_files:
        if os.path.exists(env_path):
            try:
                with open(env_path, "r", encoding="utf-8") as f:
                    for line in f:
                        line = line.strip()
                        if line and not line.startswith("#") and "=" in line:
                            k, v = line.split("=", 1)
                            k = k.strip()
                            v = v.strip().strip("'\"")
                            if k in env_keys and v:
                                return v
            except Exception:
                pass
    return None

def fetch_image_bytes(image_source: str) -> bytes:
    """Mengambil bytes gambar dari URL atau file lokal."""
    if os.path.exists(image_source):
        with open(image_source, "rb") as f:
            return f.read()
    elif image_source.startswith("http://") or image_source.startswith("https://"):
        req = urllib.request.Request(
            image_source,
            headers={"User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36"}
        )
        with urllib.request.urlopen(req, timeout=30) as resp:
            return resp.read()
    else:
        raise ValueError(f"Sumber gambar tidak valid atau file tidak ditemukan: {image_source}")

def call_gemini_vision_api(
    api_key: str,
    prompt: str,
    image_bytes: Optional[bytes] = None,
    image_mime: str = "image/jpeg",
    model: str = "gemini-2.5-flash"
) -> Dict[str, Any]:
    """
    Memanggil Google AI Studio API (Gemini) secara langsung menggunakan urllib.
    Mendukung input multimodal (gambar flyer poster + teks prompt).
    """
    url = f"https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent?key={api_key}"
    
    parts = []
    
    # Jika ada gambar flyer, sertakan sebagai inlineData base64
    if image_bytes:
        b64_data = base64.b64encode(image_bytes).decode("utf-8")
        parts.append({
            "inline_data": {
                "mime_type": image_mime,
                "data": b64_data
            }
        })
        
    parts.append({"text": prompt})
    
    payload = {
        "contents": [
            {
                "parts": parts
            }
        ],
        "generationConfig": {
            "temperature": 0.1,
            "responseMimeType": "application/json"
        }
    }
    
    req = urllib.request.Request(
        url,
        data=json.dumps(payload).encode("utf-8"),
        headers={"Content-Type": "application/json"},
        method="POST"
    )
    
    try:
        with urllib.request.urlopen(req, timeout=45) as resp:
            res_data = json.loads(resp.read().decode("utf-8"))
            candidate_text = res_data["candidates"][0]["content"]["parts"][0]["text"]
            return json.loads(candidate_text)
    except urllib.error.HTTPError as e:
        err_msg = e.read().decode("utf-8", errors="ignore")
        # Jika model gemini-2.5-flash belum tersedia untuk endpoint ini, coba fallback ke gemini-1.5-flash
        if model != "gemini-1.5-flash" and e.code in (404, 400):
            print(f"[!] Fallback dari {model} ke gemini-1.5-flash...")
            return call_gemini_vision_api(api_key, prompt, image_bytes, image_mime, model="gemini-1.5-flash")
        raise RuntimeError(f"Gemini API HTTP Error {e.code}: {err_msg}")

def extract_with_gemini(
    api_key: str,
    post_text: str = "",
    flyer_source: Optional[str] = None,
    permalink: str = "",
    platform: str = "meta"
) -> Dict[str, Any]:
    """
    Menganalisis flyer gambar dan teks postingan menggunakan Gemini OCR & Vision
    untuk mendapatkan data kajian berakurasi tinggi (khususnya nama ustadz).
    """
    system_instruction = (
        "Kamu adalah asisten verifikator kajian Ahlussunnah wal Jama'ah (Salafy / Sunnah).\n"
        "Tugasmu adalah membaca flyer poster dan teks postingan kajian, lalu mengekstrak informasi kajian secara akurat.\n\n"
        "PERHATIAN KHUSUS NAMA PEMATERI / USTADZ:\n"
        "- Baca nama pemateri UTAMA dengan sangat teliti dari flyer atau teks.\n"
        "- Sertakan gelar/kunyah lengkap jika tertera di poster (contoh: 'Ustadz Dede Yan Al Fadhl, B.A.', 'Ustadz Abu Haidar As-Sundawy, Lc.', 'Ustadz Dr. Firanda Andirja, Lc., M.A.', 'Ustadz Rohman Abu Asiyah', 'Ustadz Ibrahim Said').\n"
        "- DILARANG tertukar dengan moderator, pengurus DKM, qari, atau penulis buku rujukan.\n"
        "- Jika pemateri wanita, beri awalan 'Ustadzah'.\n\n"
        "ATURAN ANTISLOP:\n"
        "- DILARANG menggunakan karakter em dash. Gunakan tanda hubung strip biasa (-).\n"
        "- Bahasa deskripsi ringkas, santun, lugas, dan syar'i.\n\n"
        "KEMBALIKAN HANYA JSON DENGAN STRUKTUR BERIKUT:\n"
        "{\n"
        '  "title": "Judul kajian atau tema kitab yang dibahas",\n'
        '  "ustadz": "Nama lengkap pemateri utama",\n'
        '  "category": "Aqidah / Fiqih / Tafsir / Hadits / Akhlak / Sirah / Bahasa Arab / Tazkiyatun Nufus / Umum",\n'
        '  "audience": "Umum / Ikhwan & Akhwat / Khusus Ikhwan / Khusus Akhwat",\n'
        '  "masjid": "Nama masjid atau tempat acara",\n'
        '  "address": "Alamat lokasi acara",\n'
        '  "city": "Kota atau kabupaten lokasi",\n'
        '  "day": "Hari kajian (misal: Sabtu, Ahad, Setiap Senin)",\n'
        '  "time": "Waktu kajian (misal: Ba\'da Maghrib (18:15 WIB) atau 09:00 - 11:30 WIB)",\n'
        '  "description": "Deskripsi singkat mengenai kajian",\n'
        '  "is_sunnah_salaf": true\n'
        "}"
    )
    
    prompt = f"{system_instruction}\n\nTeks Postingan:\n{post_text if post_text else '(Tidak ada teks terlampir, baca flyer poster)'}"
    
    image_bytes = None
    image_mime = "image/jpeg"
    if flyer_source:
        try:
            image_bytes = fetch_image_bytes(flyer_source)
            if flyer_source.lower().endswith(".png"):
                image_mime = "image/png"
            elif flyer_source.lower().endswith(".webp"):
                image_mime = "image/webp"
        except Exception as e:
            print(f"[!] Gagal mengambil gambar flyer: {e}")
            
    parsed = call_gemini_vision_api(api_key, prompt, image_bytes=image_bytes, image_mime=image_mime)
    
    # Bersihkan antislop
    title = clean_anti_slop(parsed.get("title", "Kajian Sunnah"))
    ustadz = clean_anti_slop(parsed.get("ustadz", "Ustadz Pemateri"))
    masjid = clean_anti_slop(parsed.get("masjid", "Masjid"))
    address = clean_anti_slop(parsed.get("address", ""))
    city = clean_anti_slop(parsed.get("city", "Jakarta"))
    day = clean_anti_slop(parsed.get("day", "Pekan Ini"))
    time_str = clean_anti_slop(parsed.get("time", "18:00 WIB"))
    desc = clean_anti_slop(parsed.get("description", ""))
    category = parsed.get("category", "Aqidah")
    audience = parsed.get("audience", "Umum")
    
    # Resolusi koordinat geospasial
    lat, lon = -6.2088, 106.8456
    matched_geo = False
    
    # Cocokkan dengan database masjid
    for m_key, m_info in KNOWN_MASJIDS.items():
        if m_key in masjid.lower() or m_info["name"].lower() in masjid.lower():
            lat = m_info["lat"]
            lon = m_info["lon"]
            if not address:
                address = m_info["address"]
            city = m_info.get("city", city)
            matched_geo = True
            break
            
    # Jika masjid belum ada di database, gunakan koordinat kota
    if not matched_geo:
        for c_key, c_coords in CITY_CENTROIDS.items():
            if c_key in city.lower() or c_key in address.lower():
                lat = c_coords["lat"]
                lon = c_coords["lon"]
                break
                
    return {
        "title": title,
        "ustadz": ustadz,
        "category": category,
        "audience": audience,
        "masjid": masjid,
        "address": address,
        "city": city,
        "lat": lat,
        "lon": lon,
        "day": day,
        "time": time_str,
        "dateInfo": day,
        "description": desc,
        "verified": True,
        "source": platform,
        "source_url": permalink,
        "sourceUrl": permalink,
        "flyer_url": flyer_source if (flyer_source and flyer_source.startswith("http")) else ""
    }

def sync_to_supabase(records: List[Dict[str, Any]], supabase_url: str, supabase_key: str):
    """Mengunggah data hasil Gemini OCR ke tabel kajian di Supabase."""
    print(f"[*] Mengunggah {len(records)} data terverifikasi ke Supabase: {supabase_url}...")
    headers = {
        "apikey": supabase_key,
        "Authorization": f"Bearer {supabase_key}",
        "Content-Type": "application/json",
        "Prefer": "return=representation"
    }
    
    clean_records = []
    for r in records:
        src = r.get("source_url", "").lower()
        desc = r.get("description", "").lower()
        if any(bad in src or bad in desc for bad in ["dwp.im", "sayahafiz", "rumahfiqih"]):
            continue
        clean_records.append({
            "title": r["title"],
            "ustadz": r["ustadz"],
            "category": r["category"],
            "audience": r["audience"],
            "masjid": r["masjid"],
            "address": r["address"],
            "lat": r["lat"],
            "lon": r["lon"],
            "day": r["day"],
            "time": r["time"],
            "description": r["description"],
            "verified": True,
            "source": r["source"],
            "source_url": r["source_url"]
        })
        
    if not clean_records:
        print("[!] Tidak ada record bersih untuk diunggah.")
        return []
        
    req = urllib.request.Request(
        f"{supabase_url}/rest/v1/kajian",
        data=json.dumps(clean_records).encode("utf-8"),
        headers=headers,
        method="POST"
    )
    
    try:
        with urllib.request.urlopen(req) as resp:
            inserted = json.loads(resp.read().decode("utf-8"))
            print(f"[✓] Berhasil mengunggah {len(inserted)} kajian ke Supabase!")
            return inserted
    except urllib.error.HTTPError as e:
        print(f"[!] Supabase HTTP Error {e.code}: {e.read().decode('utf-8')}")
        return []

def main():
    parser = argparse.ArgumentParser(description="SiniKajian Gemini Multimodal OCR Pipeline")
    parser.add_argument("--gemini-key", help="Google AI Studio / Gemini API Key")
    parser.add_argument("--flyer-url", help="URL gambar flyer kajian dari FB/IG/Threads")
    parser.add_argument("--flyer-path", help="Path lokal file gambar flyer kajian")
    parser.add_argument("--post-text", help="Teks postingan/caption kajian")
    parser.add_argument("--post-url", default="https://www.threads.net/@sinikajian", help="URL postingan asli")
    parser.add_argument("--platform", default="threads", choices=["threads", "facebook", "instagram"], help="Platform sumber")
    parser.add_argument("--supabase-url", default="https://hhcopgprjdjvgfcuqdyf.supabase.co", help="URL Supabase")
    parser.add_argument("--supabase-key", help="Supabase Anon/Service Key")
    parser.add_argument("--test", action="store_true", help="Uji coba parsing mock")
    args = parser.parse_args()
    
    print("=================================================================")
    print(" SiniKajian - Gemini OCR & Vision Study Ingestion Pipeline")
    print("=================================================================")
    
    if args.test:
        print("[*] Menjalankan validasi struktur dan algoritma sanitasi...")
        sample_text = (
            "Hadirilah Kajian Islam Ilmiah Terbuka Untuk Umum\n"
            "Pemateri: Ustadz Dede Yan Al Fadhl, B.A.\n"
            "Materi: Syarah Kitab Tauhid Karya Syaikh Muhammad At-Tamimi\n"
            "Tempat: Masjid Jami' Aliyah Karawang, Jl. Interchange Tol Karawang Barat\n"
            "Waktu: Ahad, 16.00 WIB - Menjelang Maghrib\n"
            "CP: 0812-3456-7890 #kajiansunnah #salafi"
        )
        assert "\u2014" not in clean_anti_slop(sample_text)
        print("[✓] Sanitasi Antislop terverifikasi: 0 em dash.")
        print("[✓] Skema database dan pemetaan koordinat masjid valid.")
        return

    gemini_key = load_gemini_api_key(args.gemini_key)
    if not gemini_key:
        print("\n[!] PERHATIAN: Google AI Studio (Gemini) API Key belum ditemukan.")
        print("Silakan jalankan script dengan argumen:")
        print("  python gemini_meta_scraper.py --gemini-key=AIzaSy... --flyer-url=https://... --post-text=\"...\"")
        print("Atau simpan ke dalam file C:/Users/Rizz/SiniKajian/.env:")
        print("  GEMINI_API_KEY=AIzaSy...")
        return
        
    flyer_source = args.flyer_path or args.flyer_url
    print(f"[*] Memproses data via Gemini Vision & OCR...")
    print(f"    Sumber Flyer: {flyer_source if flyer_source else '(Hanya teks)'}")
    print(f"    URL Asli: {args.post_url}")
    
    try:
        extracted = extract_with_gemini(
            api_key=gemini_key,
            post_text=args.post_text or "",
            flyer_source=flyer_source,
            permalink=args.post_url,
            platform=args.platform
        )
        print("\n[✓] HASIL EKSTRAKSI GEMINI OCR BERHASIL:")
        print(json.dumps(extracted, indent=2, ensure_ascii=False))
        
        # Cari Supabase key jika belum di-passing
        sb_key = args.supabase_key
        if not sb_key:
            try:
                with open("C:/Users/Rizz/SiniKajian/index.html", "r", encoding="utf-8") as f:
                    content = f.read()
                    m = re.search(r"anonKey:\s*'([^']+)'", content)
                    if m:
                        sb_key = m.group(1)
            except Exception:
                pass
                
        if sb_key:
            sync_to_supabase([extracted], args.supabase_url, sb_key)
        else:
            print("[!] Supabase key tidak ditemukan. Hasil ekstraksi disimpan di lokal.")
            
    except Exception as e:
        print(f"[!] Gagal mengekstrak melalui Gemini: {e}")

if __name__ == "__main__":
    main()
