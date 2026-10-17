-- Jalankan di Supabase SQL Editor
-- Tabel attendances untuk kehadiran kajian (persisten)

CREATE TABLE IF NOT EXISTS public.attendances (
  id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  user_id BIGINT NOT NULL,
  kajian_id BIGINT NOT NULL,
  attendance_date DATE NOT NULL DEFAULT CURRENT_DATE,
  created_at TIMESTAMPTZ DEFAULT NOW(),
  UNIQUE(user_id, kajian_id, attendance_date)
);

CREATE INDEX IF NOT EXISTS idx_attendances_user ON public.attendances(user_id);

ALTER TABLE public.attendances ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS "service_role_all" ON public.attendances;
CREATE POLICY "service_role_all" ON public.attendances
  FOR ALL TO service_role USING (true) WITH CHECK (true);
