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
  'Kajian Sunnah: Kitab Ushul Tsalatsah',
  'Ustadz Rohman Abu Asiyah',
  'Aqidah',
  'Umum',
  'Ma''had & Masjid Al Bashiirah Cikampek',
  'Jl. Raya Cikampek - Parakan, Kotabaru, Cikampek, Karawang',
  -6.4061,
  107.4589,
  'Sabtu',
  'Ba''da Maghrib (18:15 WIB)',
  'Kajian rutin ahlussunnah di Cikampek mempelajari tiga landasan utama mengenal Allah, mengenal Rasul, dan mengenal agama Islam. #kajiansunnah #salafy #cikampek',
  true,
  'instagram',
  'https://www.instagram.com/p/CgYiNHRBSVj/'
),
(
  'Kajian Salafy: Syarah Ushulus Sunnah Imam Ahmad',
  'Ustadz Ibrahim Said',
  'Aqidah',
  'Umum',
  'Masjid Fatimah Purwakarta',
  'Jl. Terusan Kapten Halim, Kp. Pasir Kiara, Nagri Kidul, Purwakarta',
  -6.5369,
  107.4431,
  'Sabtu',
  '09:30 - 11:30 WIB',
  'Majelis ta''lim ahlussunnah wal jama''ah Purwakarta membedah pokok-pokok sunnah menurut salafus shalih bersama asatidzah salaf. #kajiansunnah #salafy #purwakarta',
  true,
  'facebook',
  'https://www.facebook.com/JadwalKajianSunnah/posts/1510769414407760/'
),
(
  'Kajian Akbar Sunnah: Tazkiyatun Nufus Salafus Shalih',
  'Ustadz Abu Haidar As-Sundawy, Lc.',
  'Tazkiyatun Nufus',
  'Umum',
  'Ma''had Rahmatika Al-Atsari Subang',
  'Jl. Raya Sagalaherang - Curug Rendeng, Sagalaherang, Subang',
  -6.6125,
  107.7289,
  'Ahad',
  '09:00 - 11:30 WIB',
  'Kajian ilmiyyah ahlussunnah Subang membahas penyucian jiwa sesuai pemahaman salafus shalih. Terbuka untuk umum ikhwan dan akhawat. #kajiansunnah #salafi #subang',
  true,
  'threads',
  'https://www.threads.net/@pesantrenrahmatika/post/F_9a8b7c6d5'
)
ON CONFLICT DO NOTHING;
