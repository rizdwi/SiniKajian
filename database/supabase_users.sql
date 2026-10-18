-- Jalankan di Supabase SQL Editor
-- Tabel users untuk persistensi (SQLite Vercel ephemeral)

CREATE TABLE IF NOT EXISTS public.app_users (
  id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) UNIQUE NOT NULL,
  password VARCHAR(255),
  provider VARCHAR(50),
  provider_id VARCHAR(255),
  email_verified_at TIMESTAMPTZ,
  created_at TIMESTAMPTZ DEFAULT NOW(),
  updated_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_app_users_email ON public.app_users(email);
CREATE INDEX IF NOT EXISTS idx_app_users_provider ON public.app_users(provider, provider_id);

ALTER TABLE public.app_users ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS "service_role_all" ON public.app_users;
CREATE POLICY "service_role_all" ON public.app_users
  FOR ALL TO service_role USING (true) WITH CHECK (true);
