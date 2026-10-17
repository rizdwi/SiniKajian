#!/usr/bin/env python3
"""
SiniKajian - Scraper kajian per daerah dari akun-akun salafiyyah.

Membaca akun_target.json (region -> halaman Facebook + keyword Threads),
menarik postingan tiap akun, mengekstrak jadwal kajian, memberi tag daerah,
lalu mencetak ringkasan. Default DRY-RUN: tidak ada yang dikirim ke Supabase
kecuali flag --sync diberikan.

Token TIDAK ditulis di file ini. Berikan lewat environment:
    FB_TOKEN       = Facebook User/Page Access Token (Meta Developer App)
    THREADS_TOKEN  = Threads API Access Token
atau argumen --fb-token / --threads-token (sekali pakai, jangan commit).

BATASAN JUJUR (terverifikasi dari dokumentasi Meta, Okt 2026):
- Postingan halaman Facebook MILIK ORANG LAIN (Rodja, dsb) HANYA bisa dibaca
  kalau aplikasi Meta sudah lolos App Review untuk fitur
  "Page Public Content Access". Tanpa itu, API menolak / mengembalikan kosong.
- Threads keyword_search HANYA mengembalikan postingan publik kalau aplikasi
  sudah lolos App Review untuk permission "threads_keyword_search".
  Tanpa itu, yang kembali cuma postingan akun sendiri.
- Yang langsung jalan hari ini tanpa review: halaman yang kamu kelola
  (pakai Page token-nya), postingan Threads akunmu sendiri, dan
  OCR flyer via gemini_meta_scraper.py dari gambar yang kamu teruskan manual.

Script ini melaporkan apa adanya: akun yang gagal resolve, keyword yang
mengembalikan nol, dan error persis dari API — supaya jelas bedanya
"tidak ada postingan" vs "token belum punya izin".
"""

import argparse
import json
import os
import sys
import urllib.error
from datetime import datetime, timezone

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from sync_meta import (
    fetch_facebook_page_posts,
    fetch_threads_keywords,
    extract_kajian_metadata,
    sync_to_supabase,
)

CONFIG_PATH = os.path.join(os.path.dirname(os.path.abspath(__file__)), "akun_target.json")
SUPABASE_URL = "https://hhcopgprjdjvgfcuqdyf.supabase.co"


def interpret_meta_error(e: Exception) -> str:
    """Terjemahkan error Meta API ke bahasa manusia."""
    body = ""
    if isinstance(e, urllib.error.HTTPError):
        try:
            body = e.read().decode("utf-8", "replace")[:500]
        except Exception:
            pass
    low = body.lower()
    if "page public content access" in low or '"code":10' in low or '"code": 10' in low:
        return ("DITOLAK Meta: aplikasi belum punya 'Page Public Content Access'. "
                "Butuh App Review di Meta Developer App sebelum bisa baca halaman orang lain. " + body)
    if "threads_keyword_search" in low or "not approved" in low:
        return ("DITOLAK Meta: permission 'threads_keyword_search' belum di-approve. "
                "Tanpa App Review, endpoint ini cuma mengembalikan postingan akun sendiri. " + body)
    if e.__class__.__name__ == "HTTPError":
        return f"HTTP {e.code}: {body}"
    return f"{e.__class__.__name__}: {e}"


def scrape_facebook(pages, token, limit, gemini_key=None):
    out, report = [], []
    for p in pages:
        label, username, region = p["label"], p["username"], p["region"]
        try:
            items = fetch_facebook_page_posts(username, token, limit=limit)
            n = 0
            for item in items:
                text = item.get("message", "") or ""
                if not text.strip():
                    continue
                permalink = item.get("permalink_url") or f"https://www.facebook.com/{item.get('id')}"
                rec = extract_kajian_metadata(text, permalink=permalink, platform="facebook",
                                              created_time=item.get("created_time", ""))
                rec["region"] = region
                rec["akun"] = label
                out.append(rec)
                n += 1
            report.append({"akun": label, "username": username, "region": region,
                           "platform": "facebook", "status": "ok", "postingan_diproses": n,
                           "note": "0 postingan bisa berarti halaman tidak memposting jadwal / token kurang izin"})
        except Exception as e:
            report.append({"akun": label, "username": username, "region": region,
                           "platform": "facebook", "status": "gagal",
                           "error": interpret_meta_error(e)})
    return out, report


def scrape_threads(keywords_by_region, token, limit):
    out, report = [], []
    for region, keywords in keywords_by_region:
        for kw in keywords:
            try:
                items = fetch_threads_keywords(token, keyword=kw, limit=limit)
                n = 0
                for item in items:
                    text = item.get("text", "") or ""
                    if not text.strip():
                        continue
                    permalink = item.get("permalink") or f"https://www.threads.net/t/{item.get('id')}"
                    rec = extract_kajian_metadata(text, permalink=permalink, platform="threads",
                                                  created_time=item.get("timestamp", ""))
                    rec["region"] = region
                    rec["akun"] = f"pencarian: {kw}"
                    out.append(rec)
                    n += 1
                note = ("Jika yang kembali hanya postingan akunmu sendiri, berarti "
                        "'threads_keyword_search' belum lolos App Review.")
                report.append({"keyword": kw, "region": region, "platform": "threads",
                               "status": "ok", "postingan_diproses": n, "note": note})
            except Exception as e:
                report.append({"keyword": kw, "region": region, "platform": "threads",
                               "status": "gagal", "error": interpret_meta_error(e)})
    return out, report


def main():
    ap = argparse.ArgumentParser(description="Scraper kajian per daerah SiniKajian (default dry-run)")
    ap.add_argument("--fb-token", default=os.environ.get("FB_TOKEN"), help="Facebook Access Token")
    ap.add_argument("--threads-token", default=os.environ.get("THREADS_TOKEN"), help="Threads Access Token")
    ap.add_argument("--supabase-key", default=os.environ.get("SUPABASE_KEY"), help="Supabase key (hanya untuk --sync)")
    ap.add_argument("--limit", type=int, default=10, help="Maks postingan per akun/keyword")
    ap.add_argument("--sync", action="store_true", help="Kirim hasil ke Supabase (default: dry-run, hanya tampilkan)")
    ap.add_argument("--config", default=CONFIG_PATH, help="Path akun_target.json")
    ap.add_argument("--out", default="", help="Simpan laporan JSON ke file ini")
    args = ap.parse_args()

    if not args.fb_token and not args.threads_token:
        print("[!] Butuh token: set FB_TOKEN / THREADS_TOKEN di environment, atau --fb-token / --threads-token.")
        print("    Tanpa token tidak ada yang bisa ditarik — menolak jalan (tidak ada data karangan).")
        sys.exit(2)

    with open(args.config, encoding="utf-8") as f:
        cfg = json.load(f)

    collected, reports = [], []
    if args.fb_token:
        pages = [{"label": p["label"], "username": p["username"], "region": r["region"]}
                 for r in cfg["regions"] for p in r.get("fb_pages", [])]
        recs, rep = scrape_facebook(pages, args.fb_token, args.limit)
        collected += recs
        reports += rep
    if args.threads_token:
        kw = [(r["region"], r.get("threads_keywords", [])) for r in cfg["regions"]]
        recs, rep = scrape_threads(kw, args.threads_token, args.limit)
        collected += recs
        reports += rep

    # Dedup berdasarkan source_url
    seen, unique = set(), []
    for r in collected:
        u = r.get("source_url", "")
        if u and u not in seen:
            seen.add(u)
            unique.append(r)

    now_utc = datetime.now(timezone.utc).strftime("%Y-%m-%d %H:%M")
    print("=" * 64)
    print(f" HASIL SCRAPE PER DAERAH — {now_utc} UTC")
    print("=" * 64)
    ok = [x for x in reports if x["status"] == "ok"]
    fail = [x for x in reports if x["status"] == "gagal"]
    print(f"[*] {len(ok)} sumber OK, {len(fail)} sumber gagal, {len(unique)} kajian unik terkumpul.\n")
    for x in reports:
        who = x.get("akun") or x.get("keyword")
        if x["status"] == "ok":
            print(f"  [OK]    {who} ({x['region']}) — {x['postingan_diproses']} postingan diproses")
        else:
            print(f"  [GAGAL] {who} ({x['region']}) — {x['error'][:220]}")
    print()
    for i, r in enumerate(unique, 1):
        print(f"  {i}. [{r.get('region')}] {r.get('title')} | {r.get('ustadz')} | {r.get('masjid')} | {r.get('source_url')}")

    if args.out:
        with open(args.out, "w", encoding="utf-8") as f:
            json.dump({"reports": reports, "kajian": unique}, f, ensure_ascii=False, indent=2)
        print(f"\n[*] Laporan tersimpan: {args.out}")

    if not args.sync:
        print("\n[*] Dry-run selesai — TIDAK ada yang dikirim ke Supabase. Ulangi dengan --sync untuk sinkron.")
        return
    if not args.supabase_key:
        print("[!] --sync butuh --supabase-key (atau env SUPABASE_KEY).")
        sys.exit(2)
    inserted = sync_to_supabase(unique, SUPABASE_URL, args.supabase_key)
    print(f"[*] {len(inserted)} kajian tersinkron ke Supabase.")


if __name__ == "__main__":
    main()
