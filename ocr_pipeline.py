#!/usr/bin/env python3
"""
SiniKajian OCR Pipeline - Ekstraksi data kajian dari flyer via Vision API
Alur: URL postingan -> download gambar flyer -> OCR -> data terstruktur -> validasi -> DB
"""
import os, sys, json, re, base64, urllib.request, urllib.parse
from datetime import datetime

GEMINI_KEY = os.environ.get('GEMINI_KEY', '')
SUPABASE_URL = 'https://hhcopgprjdjvgfcuqdyf.supabase.co'
SR_KEY = os.environ.get('SR_KEY', '')

def get_og_image(post_url):
    """Ambil URL gambar via curl (urllib diblokir IG)"""
    import subprocess
    try:
        embed_url = post_url.rstrip('/') + '/embed/'
        result = subprocess.run(
            ['curl', '-sL', '--max-time', '20', embed_url,
             '-A', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)'],
            capture_output=True, text=True, timeout=25
        )
        html = result.stdout
        matches = re.findall(r'(scontent[^"]*\.jpg)', html)
        for m in matches:
            if 't51.82787-15' in m:
                return 'https://' + m.replace('&amp;', '&')
        if matches:
            return 'https://' + matches[0].replace('&amp;', '&')
    except Exception as e:
        print(f"  Gagal ambil gambar: {e}", file=sys.stderr)
    return None

def download_image(img_url, dest):
    """Download gambar via curl"""
    import subprocess
    try:
        result = subprocess.run(
            ['curl', '-sL', '--max-time', '30', '-o', dest, img_url,
             '-A', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'],
            capture_output=True, timeout=35
        )
        return os.path.exists(dest) and os.path.getsize(dest) > 1000
    except Exception as e:
        print(f"  Gagal download: {e}", file=sys.stderr)
        return False

def ocr_flyer(image_path):
    """OCR flyer via Gemma vision API"""
    with open(image_path, 'rb') as f:
        img_b64 = base64.b64encode(f.read()).decode()

    prompt = """Baca flyer/poster kajian Islam ini dengan SANGAT TELITI.

Ekstrak informasi berikut dalam format JSON (jika tidak ada, isi null):
{
  "judul": "judul atau tema utama kajian",
  "ustadz": "nama lengkap ustadz/pemateri (persis seperti di flyer)",
  "tanggal_spesifik": "tanggal acara dalam format YYYY-MM-DD (contoh: 2026-10-17). Jika flyer menyebut tanggal spesifik.",
  "hari": "nama hari (Ahad/Senin/Selasa/Rabu/Kamis/Jumat/Sabtu)",
  "pola_rutin": "jika tertulis pola berulang seperti 'setiap Senin', 'tiap Ahad pekan ke-2', dst. Jika tidak ada, null.",
  "waktu": "waktu acara persis seperti di flyer",
  "tempat": "nama masjid/tempat",
  "alamat": "alamat lengkap jika ada",
  "kontak": "nomor kontak jika ada",
  "is_onetime": true jika ada tanggal spesifik, false jika pola rutin tanpa tanggal spesifik
}

PENTING: Baca tanggal dengan sangat hati-hati. Jangan tebak."""

    url = f"https://generativelanguage.googleapis.com/v1beta/models/gemma-4-26b-a4b-it:generateContent?key={GEMINI_KEY}"
    payload = json.dumps({
        "contents": [{"parts": [
            {"text": prompt},
            {"inline_data": {"mime_type": "image/jpeg", "data": img_b64}}
        ]}]
    }).encode()

    req = urllib.request.Request(url, data=payload, headers={"Content-Type": "application/json"})
    resp = urllib.request.urlopen(req, timeout=90)
    result = json.load(resp)
    text = result['candidates'][0]['content']['parts'][0]['text']
    # Ekstrak JSON dari respon
    m = re.search(r'\{.*\}', text, re.S)
    if m:
        return json.loads(m.group(0))
    return None

def validate_and_insert(data, source_url, platform):
    """Validasi dan insert ke DB"""
    # Validasi wajib
    if not data.get('ustadz') or not data.get('tempat'):
        print(f"  SKIP: ustadz/tempat kosong", file=sys.stderr)
        return False

    # Tentukan day dan tanggal
    today = datetime.now().date()
    day = data.get('hari', '')
    event_date = data.get('tanggal_spesifik')

    # Jika one-time dan tanggal sudah lewat, skip
    if data.get('is_onetime') and event_date:
        try:
            ed = datetime.strptime(event_date, '%Y-%m-%d').date()
            if ed < today:
                print(f"  SKIP: tanggal {event_date} sudah lewat", file=sys.stderr)
                return False
        except:
            pass

    # Jika pola rutin tanpa tanggal spesifik, tetap masukkan (day-based)
    # Jika one-time future, masukkan dengan day dari tanggal tersebut

    record = {
        'title': data.get('judul') or f"Kajian {data.get('tempat')}",
        'ustadz': data['ustadz'],
        'day': day or '-',
        'time': data.get('waktu') or '-',
        'masjid': data['tempat'],
        'address': data.get('alamat') or '-',
        'lat': -6.2, 'lon': 106.8,  # default, perlu geocoding manual
        'category': 'Kajian Rutin' if data.get('pola_rutin') else 'Kajian Tematik',
        'audience': 'Umum',
        'source': platform,
        'source_url': source_url,
        'verified': True,
        'description': f"OCR dari flyer. Kontak: {data.get('kontak') or '-'}"
    }

    # Insert
    req = urllib.request.Request(
        f"{SUPABASE_URL}/rest/v1/kajian",
        data=json.dumps(record).encode(),
        headers={'apikey': SR_KEY, 'Authorization': f'Bearer {SR_KEY}',
                 'Content-Type': 'application/json', 'Prefer': 'return=representation'}
    )
    urllib.request.urlopen(req, timeout=30)
    print(f"  OK: {record['title'][:40]}")
    return True

if __name__ == '__main__':
    if len(sys.argv) < 3:
        print("Usage: ocr_pipeline.py <post_url> <platform>")
        sys.exit(1)
    post_url, platform = sys.argv[1], sys.argv[2]
    print(f"Proses: {post_url}")

    img_url = get_og_image(post_url)
    if not img_url:
        print("Gagal dapat URL gambar", file=sys.stderr)
        sys.exit(1)
    print(f"Gambar: {img_url[:60]}...")

    tmp = '/tmp/flyer_ocr.jpg'
    if not download_image(img_url, tmp):
        sys.exit(1)

    data = ocr_flyer(tmp)
    if not data:
        print("OCR gagal", file=sys.stderr)
        sys.exit(1)
    print(f"OCR: {json.dumps(data, indent=2, ensure_ascii=False)[:500]}")

    validate_and_insert(data, post_url, platform)
