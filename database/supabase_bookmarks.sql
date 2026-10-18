-- Jalankan di Supabase SQL Editor
-- Tabel untuk bookmark kajian per user (persisten, tidak hilang seperti SQLite Vercel)

CREATE TABLE IF NOT EXISTS public.bookmarks (
  id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  user_id BIGINT NOT NULL,
  kajian_id BIGINT NOT NULL,
  created_at TIMESTAMPTZ DEFAULT NOW(),
  UNIQUE(user_id, kajian_id)
);

-- Index untuk query cepat
CREATE INDEX IF NOT EXISTS idx_bookmarks_user ON public.bookmarks(user_id);
CREATE INDEX IF NOT EXISTS idx_bookmarks_kajian ON public.bookmarks(kajian_id);

-- RLS: izinkan service_role akses penuh
ALTER TABLE public.bookmarks ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS "service_role_all" ON public.bookmarks;
CREATE POLICY "service_role_all" ON public.bookmarks
  FOR ALL TO service_role USING (true) WITH CHECK (true);
