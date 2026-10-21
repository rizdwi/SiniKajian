-- Jalankan di Supabase SQL Editor
-- Tabel feedback untuk box saran & masukan pengguna (perintah user 2026-10-21)

CREATE TABLE IF NOT EXISTS public.feedback (
  id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  category TEXT NOT NULL DEFAULT 'Lainnya',
  name TEXT,
  contact TEXT,
  message TEXT NOT NULL,
  page TEXT,
  is_read BOOLEAN NOT NULL DEFAULT FALSE,
  created_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_feedback_created ON public.feedback(created_at DESC);

ALTER TABLE public.feedback ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS "service_role_all" ON public.feedback;
CREATE POLICY "service_role_all" ON public.feedback
  FOR ALL TO service_role USING (true) WITH CHECK (true);
