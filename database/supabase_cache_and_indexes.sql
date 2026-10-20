-- ============================================================
-- MajelisKu — SQL pendamping perbaikan audit 2026-10-21
-- Jalankan di Supabase SQL Editor (proyek hhcopgprjdjvgfcuqdyf).
-- Semua pernyataan idempotent (IF NOT EXISTS) — aman dijalankan ulang.
-- ============================================================

-- 1) Cache bersama lintas instance Vercel (cache + rate limiter).
--    Dipakai driver cache 'supabase' (app/Cache/SupabaseStore.php).
CREATE TABLE IF NOT EXISTS app_cache (
    key TEXT PRIMARY KEY,
    value TEXT,
    expiration BIGINT
);

-- 2) Token reset sandi terpusat (sebelumnya di SQLite per-instance,
--    membuat tautan reset sering "tidak valid" secara acak).
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    email TEXT PRIMARY KEY,
    token TEXT NOT NULL,
    created_at TIMESTAMPTZ DEFAULT now()
);

-- 3) Cegah akun ganda: satu email = satu baris app_users.
--    (Kode sudah memakai upsert merge-duplicates; index ini yang
--    membuatnya benar-benar atomik.)
CREATE UNIQUE INDEX IF NOT EXISTS app_users_email_unique
    ON app_users (email);

-- 4) Cegah bookmark ganda dari double-tap / balapan request.
CREATE UNIQUE INDEX IF NOT EXISTS bookmarks_user_kajian_unique
    ON bookmarks (user_id, kajian_id);

-- 5) Cegah subscription push ganda per endpoint.
CREATE UNIQUE INDEX IF NOT EXISTS push_subscriptions_endpoint_unique
    ON push_subscriptions (endpoint);
