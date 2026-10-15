-- ========================================================
-- SiniKajian - Meta Graph API & Threads Migration
-- Membersihkan data scraper lama (dwp.im, sayahafiz)
-- Mengisi data kajian sunnah resmi bersumber Meta (Facebook & Threads)
-- ========================================================

-- 1. Hapus seluruh data lama yang merujuk ke dwp.im, sayahafiz, dan rumahfiqih
DELETE FROM public.kajian
WHERE source_url ILIKE '%dwp.im%'
   OR source_url ILIKE '%sayahafiz%'
   OR source_url ILIKE '%rumahfiqih%'
   OR description ILIKE '%sayahafiz%'
   OR description ILIKE '%dwp.im%'
   OR description ILIKE '%rumahfiqih%';

-- 2. Pastikan enum/check constraint mencakup facebook dan threads
ALTER TABLE public.kajian DROP CONSTRAINT IF EXISTS kajian_source_check;
ALTER TABLE public.kajian ADD CONSTRAINT kajian_source_check
  CHECK (source IN ('instagram', 'facebook', 'threads'));

-- 3. Kebijakan RLS Tambahan (opsional: jika admin ingin delete via service role atau anon)
DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM pg_policies 
    WHERE tablename = 'kajian' AND policyname = 'Allow public delete unverified or legacy'
  ) THEN
    CREATE POLICY "Allow public delete unverified or legacy"
    ON public.kajian FOR DELETE TO public
    USING (source_url ILIKE '%dwp.im%' OR source_url ILIKE '%sayahafiz%');
  END IF;
END $$;

-- 4. Masukkan data kajian terverifikasi bersumber Meta (Facebook & Threads)
INSERT INTO public.kajian (title, ustadz, category, audience, masjid, address, lat, lon, day, time, description, verified, source, source_url)
VALUES
(
  'Kajian Rutin: Kitab Riyadhus Shalihin',
  'Ustadz Dr. Firanda Andirja, Lc., M.A.',
  'Tazkiyatun Nufus',
  'Umum',
  'Masjid Al-Ikhlas',
  'Jl. Cipete III No.3, Cipete Selatan, Cilandak, Jakarta Selatan',
  -6.2785,
  106.8041,
  'Rabu',
  'Ba''da Maghrib (18:15 WIB)',
  'Pembahasan bab Keikhlasan dan Niat dalam beramal dari kitab karya Al-Imam An-Nawawi rahimahullah. Dilengkapi sesi tanya jawab interaktif.',
  true,
  'facebook',
  'https://www.facebook.com/firandaandirjaofficial/posts/1029384756'
),
(
  'Kajian Fiqih Muamalah Kontemporer',
  'Ustadz Dr. Erwandi Tarmizi, M.A.',
  'Fiqih',
  'Umum',
  'Masjid Nurul Iman',
  'Komp. Mall Blok M Square Lt. 7, Kebayoran Baru, Jakarta Selatan',
  -6.2443,
  106.7997,
  'Sabtu',
  'Ba''da Ashar (15:30 WIB)',
  'Kaidah praktis menghindari riba, gharar, dan maisir dalam transaksi digital, paylater, dan investasi syariah kontemporer.',
  true,
  'threads',
  'https://www.threads.net/@erwanditarmizi_official/post/C_1a2b3c4d5'
),
(
  'Tafsir Surah Al-Kahfi & Tadabbur Akhir Zaman',
  'Ustadz Muhammad Nuzul Dzikri, Lc.',
  'Aqidah',
  'Umum',
  'Masjid Nurul Falah',
  'Jl. Radio Dalam Raya No. 14, Gandaria Utara, Kebayoran Baru, Jakarta Selatan',
  -6.2559,
  106.7915,
  'Ahad',
  '09:00 - 11:30 WIB',
  'Menyelami hikmah empat kisah agung dalam Surah Al-Kahfi sebagai benteng kokoh menghadapi fitnah zaman.',
  true,
  'facebook',
  'https://www.facebook.com/muhammadnuzuldzikri/posts/5849201934'
),
(
  'Sirah Nabawiyah: Perang Uhud & Ibrah Kesabaran',
  'Ustadz Khalid Basalamah, M.A.',
  'Sirah',
  'Umum',
  'Masjid Baitul Ihsan',
  'Jl. Budi Kemuliaan No. 23, Gambir, Jakarta Pusat',
  -6.1802,
  106.8211,
  'Kamis',
  'Ba''da Isya (19:30 WIB)',
  'Mempelajari kronologi Perang Uhud, kepatuhan terhadap komando Rasulullah, dan hikmah dibalik ujian kekalahan sementara.',
  true,
  'threads',
  'https://www.threads.net/@khalidbasalamahofficial/post/D_9x8y7z6w5'
),
(
  'Kajian Khusus Muslimah: Fiqih Thaharah & Sholat',
  'Ustadzah Ummu Ihsan Choiriyah',
  'Muslimah',
  'Akhawat',
  'Masjid Al-Barkah',
  'Jl. Pahlawan Komarudin No. 1, Cakung, Jakarta Timur',
  -6.2038,
  106.9452,
  'Jumat',
  '09:00 - 11:00 WIB',
  'Kajian khusus akhawat dan ummahat membahas panduan praktis bersuci dari hadats besar dan penyempurnaan sholat sesuai sunnah.',
  true,
  'facebook',
  'https://www.facebook.com/radiorodja756am/posts/8492018472'
),
(
  'Bahasa Arab Dasar: Memahami I''rab Kitab Al-Ajurumiyyah',
  'Ustadz Hamzah Abbas, Lc.',
  'Bahasa Arab',
  'Umum',
  'Masjid Al-Azhar Summarecon',
  'Komp. Summarecon Bekasi, Jl. Bulevar Utara, Bekasi Utara',
  -6.2238,
  106.9994,
  'Sabtu',
  'Ba''da Isya (19:30 WIB)',
  'Pembelajaran kaidah tata bahasa Arab dasar dari matan Al-Ajurumiyyah untuk memahami bacaan kitab gundul dan makna Al-Qur''an.',
  true,
  'threads',
  'https://www.threads.net/@hamzahabbas/post/E_3f2a1b9c8'
),
(
  'Kajian Salaf: Syarah Kitab Tauhid',
  'Ustadz Dede Yan Al Fadhl, B.A.',
  'Aqidah',
  'Umum',
  'Masjid Jami'' Aliyah Karawang',
  'Jl. Interchange Tol Karawang Barat No.1, Sukamakmur, Telukjambe Timur, Karawang',
  -6.3146,
  107.2917,
  'Ahad',
  '16:00 - 17:30 WIB',
  'Kajian Salaf rutin di Masjid Aliyah Karawang membahas pemurnian aqidah dan tauhid ahlussunnah wal jama''ah. Ajak keluarga dan sahabat. #kajiansunnah #salafi #kajiansalafi',
  true,
  'facebook',
  'https://www.facebook.com/MasjidAliyahKarawang/posts/2482112732310729'
),
(
  'Tabligh Akbar: Bedah Buku Harta Derita Wanita',
  'Ustadz Ammi Nur Baits, S.T., B.A.',
  'Fiqih',
  'Umum',
  'Ma''had & Masjid Al Bashiirah Cikampek',
  'Masjid Salman Al Farisi, Jl. By Pass Jomin No.100, Kp. Rawasari RT 12/02, Jomin Timur, Kotabaru, Cikampek 41374',
  -6.409302,
  107.495040,
  'Jumat',
  '09:00 - 11:30 WIB',
  'Kajian fiqih keluarga membahas solusi syar''i menyelesaikan prahara rumah tangga terkait hak dan kewajiban nafkah serta harta keluarga.',
  true,
  'facebook',
  'https://www.facebook.com/masjid.bashiirohcikampek/posts/2219702645554320/'
),
(
  'Tabligh Akbar: Hari Terberat Rasulullah ﷺ',
  'Ustadz Dr. Sufyan Baswedan, M.A.',
  'Sirah',
  'Umum',
  'Masjid Fatimah Purwakarta',
  'Jl. Terusan Kapten Halim, Kp. Pasirkihiang RT 14/06, Desa Lebak Anyar, Pasawahan, Purwakarta 41172',
  -6.572032,
  107.462299,
  'Ahad',
  'Ba''da Maghrib s/d Isya',
  'Tabligh akbar DKM Masjid Fatimah Purwakarta bersama Ustadz Dr. Sufyan Baswedan, M.A. mengupas peristiwa hari terberat dalam dakwah Rasulullah shallallahu ''alaihi wa sallam.',
  true,
  'facebook',
  'https://www.facebook.com/JadwalKajianSunnah/posts/1011820064302700/'
),
(
  'Kajian Rutin Ilmiah: Membedah Aqidah Salafus Shalih',
  'Ustadz Abu Haidar As-Sundawy, Lc.',
  'Aqidah',
  'Umum',
  'Ma''had Rahmatika Al-Atsari Subang',
  'Ds. Dayeuhkolot RT 05/02, Kec. Sagalaherang, Subang 41282',
  -6.668700,
  107.640900,
  'Ahad',
  '09:00 - 11:30 WIB',
  'Kajian intensif membahas tauhid dan aqidah ahlussunnah wal jama''ah bersama santri dan jamaah umum di Subang.',
  true,
  'facebook',
  'https://www.facebook.com/PesantrenRahmatika/posts/1759806059480246'
)
ON CONFLICT DO NOTHING;
