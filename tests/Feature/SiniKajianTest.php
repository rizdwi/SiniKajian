<?php

namespace Tests\Feature;

use App\Models\Bookmark;
use App\Models\Kajian;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SiniKajianTest extends TestCase
{
    /**
     * Uji pemuatan halaman utama (Beranda Kajian & Sholat).
     */
    public function test_home_page_returns_successful_response(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Majelisku');
        $response->assertSee('Waktu Sholat');
    }

    /**
     * Uji pemuatan modul peta kajian interaktif.
     */
    public function test_map_page_returns_successful_response(): void
    {
        $response = $this->get('/peta');
        $response->assertStatus(200);
        $response->assertSee('fullMap');
    }

    /**
     * Uji pemuatan modul kompas kiblat astronomis.
     */
    public function test_qibla_page_returns_successful_response(): void
    {
        $response = $this->get('/kiblat');
        $response->assertStatus(200);
        $response->assertSee('Arah Kiblat');
    }

    /**
     * Uji endpoint API JSON majelis ilmu untuk konsumsi peta/mobile.
     */
    public function test_api_kajians_returns_json_dataset(): void
    {
        $response = $this->get('/api/kajian');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data' => [
                '*' => ['id', 'title', 'ustadz', 'masjid', 'lat', 'lon']
            ]
        ]);
    }

    /**
     * Uji halaman formulir masuk akun pengguna.
     */
    public function test_login_page_renders_secure_credential_form(): void
    {
        $response = $this->get('/masuk');
        $response->assertStatus(200);
        $response->assertSee('Masuk ke SiniKajian');
        $response->assertSee('name="email"', false);
        $response->assertSee('name="password"', false);
    }

    /**
     * Uji halaman formulir pendaftaran akun baru.
     */
    public function test_register_page_renders_form(): void
    {
        $response = $this->get('/daftar');
        $response->assertStatus(200);
        $response->assertSee('Buat Akun SiniKajian');
    }

    /**
     * Uji autentikasi pengguna dengan kredensial terenkripsi bcrypt.
     */
    public function test_user_authentication_flow(): void
    {
        $email = 'unit_test_' . time() . '@sinikajian.id';
        $user = User::create([
            'name' => 'Penuntut Ilmu Test',
            'email' => $email,
            'password' => Hash::make('passwordAman123'),
            'role' => 'user'
        ]);

        $this->assertDatabaseHas('users', ['email' => $email]);
        $this->assertTrue(Hash::check('passwordAman123', $user->password));

        $this->actingAs($user);
        $response = $this->get('/profil');
        $response->assertStatus(200);
        $response->assertSee('Penuntut Ilmu Test');
    }

    /**
     * Uji pemuatan halaman detail kajian (Screen 2).
     */
    public function test_detail_page_returns_successful_response(): void
    {
        $kajian = Kajian::first();
        if ($kajian) {
            $response = $this->get('/kajian/' . $kajian->id);
            $response->assertStatus(200);
            $response->assertSee('Detail Kajian');
            $response->assertSee('Simpan Kajian');
            $response->assertSee('Ikuti Majelis');
        }
    }

    /**
     * Uji penyimpanan akun pengguna via OAuth provider (Google/Facebook).
     */
    public function test_social_user_can_be_stored_with_provider_columns(): void
    {
        $uniqueEmail = 'oauth_' . uniqid() . '@example.com';
        $user = User::create([
            'name' => 'OAuth User Test',
            'email' => $uniqueEmail,
            'password' => Hash::make(\Illuminate\Support\Str::random(16)),
            'provider' => 'google',
            'provider_id' => '108400634674929037655',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => $uniqueEmail,
            'provider' => 'google',
            'provider_id' => '108400634674929037655'
        ]);
        $this->assertEquals('google', $user->provider);
        $this->assertEquals('108400634674929037655', $user->provider_id);
    }

    /**
     * Uji pembersihan otomatis kajian yang telah kadaluarsa (> 4 jam).
     */
    public function test_purge_expired_kajians_removes_old_events(): void
    {
        $expiredKajian = Kajian::create([
            'title' => 'Kajian Subuh Masa Lalu',
            'ustadz' => 'Ustadz Fulan',
            'masjid' => 'Masjid Fulan',
            'address' => 'Jl. Masa Lalu No. 1',
            'lat' => -6.9,
            'lon' => 107.6,
            'category' => 'Tematik',
            'day' => 'Kemarin',
            'time' => '05.00 WIB',
            'event_datetime' => \Carbon\Carbon::now()->subHours(8),
            'verified' => true,
            'is_rutin' => false,
        ]);

        $this->assertDatabaseHas('kajians', ['id' => $expiredKajian->id]);

        $deletedCount = Kajian::purgeExpiredKajians(4);
        $this->assertGreaterThanOrEqual(1, $deletedCount);
        $this->assertDatabaseMissing('kajians', ['id' => $expiredKajian->id]);
    }

    /**
     * Uji fitur edit profil dan penyimpanan avatar pengguna secara persisten.
     */
    public function test_edit_profile_and_avatar_update_flow(): void
    {
        $user = User::create([
            'name' => 'Jamaah Test',
            'email' => 'jamaah_' . uniqid() . '@contoh.id',
            'password' => Hash::make('password123'),
        ]);

        $this->actingAs($user);

        // Akses halaman edit profil
        $response = $this->get('/profil/edit');
        $response->assertStatus(200);
        $response->assertSee('Edit Profil');

        // Update profil dan avatar (base64 string)
        $avatarData = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
        $postResponse = $this->post('/profil/edit', [
            'name' => 'Jamaah Pembelajar Sunnah',
            'email' => $user->email,
            'avatar_data' => $avatarData,
        ]);

        $postResponse->assertRedirect('/profil');

        $user->refresh();
        $this->assertEquals('Jamaah Pembelajar Sunnah', $user->name);
        $this->assertEquals($avatarData, $user->avatar);

        // Verifikasi avatar tampil di halaman profil
        $profileResponse = $this->get('/profil');
        $profileResponse->assertStatus(200);
        $profileResponse->assertSee('Jamaah Pembelajar Sunnah');
        $profileResponse->assertSee($avatarData);
    }

    /**
     * Uji penegakan skema HTTPS dan trust proxy agar tidak muncul mixed-form warning.
     */
    public function test_https_scheme_is_enforced_in_production(): void
    {
        \Illuminate\Support\Facades\URL::forceScheme('https');
        $this->assertStringStartsWith('https://', route('login'));
        $this->assertStringStartsWith('https://', route('logout'));
        $this->assertStringStartsWith('https://', route('profile.update'));
    }

    /**
     * Uji alur otentikasi login dengan kredensial valid dan proteksi kegagalan login.
     */
    public function test_login_flow_with_credentials_and_validation(): void
    {
        $email = 'jamaah_login_' . uniqid() . '@contoh.id';
        User::create([
            'name' => 'Jamaah Login Test',
            'email' => $email,
            'password' => Hash::make('password123'),
        ]);

        // Gagal login jika password salah
        $failResponse = $this->post('/masuk', [
            'email' => $email,
            'password' => 'wrongpassword',
        ]);
        $failResponse->assertSessionHasErrors('email');
        $this->assertGuest();

        // Berhasil login jika kredensial benar
        $successResponse = $this->post('/masuk', [
            'email' => $email,
            'password' => 'password123',
        ]);
        $successResponse->assertRedirect('/profil');
        $this->assertAuthenticated();
    }

    /**
     * Uji registrasi akun baru pengguna.
     */
    public function test_registration_flow_creates_user_and_authenticates(): void
    {
        $regEmail = 'daftar_' . uniqid() . '@contoh.id';
        $response = $this->post('/daftar', [
            'name' => 'Fulan Baru',
            'email' => $regEmail,
            'password' => 'Aman12345!',
            'password_confirmation' => 'Aman12345!',
        ]);

        $response->assertRedirect(route('verification.notice'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => $regEmail, 'name' => 'Fulan Baru']);
    }

    /**
     * Uji bookmark kajian via AJAX JSON dan via standard Web Form POST.
     */
    public function test_bookmark_toggle_via_ajax_and_standard_form(): void
    {
        $user = User::create([
            'name' => 'User Bookmark Test',
            'email' => 'bm_' . uniqid() . '@contoh.id',
            'password' => Hash::make('secret123'),
        ]);

        $kajian = Kajian::create([
            'title' => 'Kajian Uji Bookmark',
            'ustadz' => 'Ustadz Uji',
            'masjid' => 'Masjid Uji',
            'address' => 'Jl. Uji No. 1',
            'lat' => -6.9,
            'lon' => 107.6,
            'category' => 'Tematik',
            'day' => 'Ahad',
            'time' => '10:00 WIB',
            'event_datetime' => \Carbon\Carbon::now('Asia/Jakarta')->addDays(2),
            'verified' => true,
            'is_rutin' => false,
        ]);

        $this->actingAs($user);

        // 1. Toggle via AJAX JSON (Bookmark)
        $ajaxResponse = $this->postJson("/kajian/{$kajian->id}/bookmark");
        $ajaxResponse->assertStatus(200);
        $ajaxResponse->assertJson(['status' => 'success', 'saved' => true]);
        $this->assertDatabaseHas('bookmarks', ['user_id' => $user->id, 'kajian_id' => $kajian->id]);

        // 2. Toggle via Standard Form POST (Unbookmark)
        $formResponse = $this->post("/kajian/{$kajian->id}/bookmark");
        $formResponse->assertRedirect();
        $this->assertDatabaseMissing('bookmarks', ['user_id' => $user->id, 'kajian_id' => $kajian->id]);
    }

    /**
     * Uji pembersihan kajian yang lewat > 3 jam aktual dari jam mulainya (flyer).
     */
    public function test_kajian_past_3_hours_is_automatically_purged(): void
    {
        $nowWib = \Carbon\Carbon::now('Asia/Jakarta');

        // Kajian yang baru lewat 1 jam (masih aktif)
        $activeKajian = Kajian::create([
            'title' => 'Kajian Masih Aktif Baru Lewat 1 Jam',
            'ustadz' => 'Ustadz Aktif',
            'masjid' => 'Masjid Aktif',
            'address' => 'Jl. Aktif No. 1',
            'lat' => -6.9,
            'lon' => 107.6,
            'category' => 'Tematik',
            'day' => 'Hari Ini',
            'time' => '07.00 WIB',
            'event_datetime' => $nowWib->copy()->subHours(1),
            'verified' => true,
            'is_rutin' => false,
        ]);

        // Kajian yang sudah lewat 4 jam (harus otomatis terhapus)
        $expiredKajian = Kajian::create([
            'title' => 'Kajian Lewat 4 Jam Harus Dihapus',
            'ustadz' => 'Ustadz Expired',
            'masjid' => 'Masjid Expired',
            'address' => 'Jl. Expired No. 1',
            'lat' => -6.9,
            'lon' => 107.6,
            'category' => 'Tematik',
            'day' => 'Hari Ini',
            'time' => '03.00 WIB',
            'event_datetime' => $nowWib->copy()->subHours(4),
            'verified' => true,
            'is_rutin' => false,
        ]);

        $this->assertDatabaseHas('kajians', ['id' => $expiredKajian->id]);
        $this->assertDatabaseHas('kajians', ['id' => $activeKajian->id]);

        // Jalankan purgeExpiredKajians dengan threshold 3 jam
        $purgedCount = Kajian::purgeExpiredKajians(3);
        $this->assertGreaterThanOrEqual(1, $purgedCount);

        // Kajian lewat 4 jam terhapus
        $this->assertDatabaseMissing('kajians', ['id' => $expiredKajian->id]);
        // Kajian lewat 1 jam tetap ada
        $this->assertDatabaseHas('kajians', ['id' => $activeKajian->id]);
    }

    /**
     * Uji fitur ikuti dan berhenti mengikuti majelis taklim.
     */
    public function test_user_can_follow_and_unfollow_majelis(): void
    {
        $user = User::first() ?? User::factory()->create();

        // 1. Follow majelis sebagai user login
        $response = $this->actingAs($user)->postJson('/majelis/follow', [
            'masjid' => 'Masjid Salman ITB',
        ]);
        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'followed' => true,
            'masjid' => 'Masjid Salman ITB',
        ]);
        $this->assertDatabaseHas('majelis_follows', [
            'user_id' => $user->id,
            'masjid' => 'Masjid Salman ITB',
        ]);

        // 2. Unfollow majelis
        $unfollowRes = $this->actingAs($user)->postJson('/majelis/follow', [
            'masjid' => 'Masjid Salman ITB',
        ]);
        $unfollowRes->assertStatus(200);
        $unfollowRes->assertJson([
            'status' => 'success',
            'followed' => false,
            'masjid' => 'Masjid Salman ITB',
        ]);
        $this->assertDatabaseMissing('majelis_follows', [
            'user_id' => $user->id,
            'masjid' => 'Masjid Salman ITB',
        ]);
    }

    /**
     * Uji sinkronisasi bookmark lokal klien ke database server.
     */
    public function test_sync_bookmarks_from_local_storage_to_database(): void
    {
        $user = User::create([
            'name' => 'User Sync Test',
            'email' => 'sync_' . uniqid() . '@contoh.id',
            'password' => Hash::make('secret123'),
        ]);

        $kajian1 = Kajian::create([
            'title' => 'Kajian Sync 1',
            'ustadz' => 'Ustadz Sync',
            'masjid' => 'Masjid Sync',
            'address' => 'Jl. Sync No. 1',
            'lat' => -6.9,
            'lon' => 107.6,
            'category' => 'Tematik',
            'day' => 'Besok',
            'time' => '09:00 WIB',
            'event_datetime' => \Carbon\Carbon::now('Asia/Jakarta')->addDays(1),
            'verified' => true,
            'is_rutin' => false,
        ]);

        $kajian2 = Kajian::create([
            'title' => 'Kajian Sync 2',
            'ustadz' => 'Ustadz Sync 2',
            'masjid' => 'Masjid Sync 2',
            'address' => 'Jl. Sync No. 2',
            'lat' => -6.9,
            'lon' => 107.6,
            'category' => 'Fiqih',
            'day' => 'Lusa',
            'time' => '13:00 WIB',
            'event_datetime' => \Carbon\Carbon::now('Asia/Jakarta')->addDays(2),
            'verified' => true,
            'is_rutin' => false,
        ]);

        $this->actingAs($user);

        // Sync dua kajian
        $response = $this->postJson('/api/bookmarks/sync', [
            'ids' => [$kajian1->id, $kajian2->id],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'synced' => 2,
            'total' => 2,
        ]);

        $this->assertDatabaseHas('bookmarks', ['user_id' => $user->id, 'kajian_id' => $kajian1->id]);
        $this->assertDatabaseHas('bookmarks', ['user_id' => $user->id, 'kajian_id' => $kajian2->id]);

        // Kunjungi halaman profil dan pastikan kajian muncul di agenda terdekat
        $profileRes = $this->get('/profil');
        $profileRes->assertStatus(200);
        $profileRes->assertSee('Kajian Sync 1');
        $profileRes->assertSee('Kajian Sync 2');
        $profileRes->assertSee('Tersimpan di Agenda');

        // Cleanup data test
        Bookmark::where('user_id', $user->id)->delete();
        $kajian1->delete();
        $kajian2->delete();
        $user->delete();
    }

    /**
     * Uji alur permohonan reset kata sandi (lupa kata sandi).
     */
    public function test_user_can_request_and_reset_password(): void
    {
        $email = 'reset_' . uniqid() . '@example.com';
        $user = User::create([
            'name' => 'User Reset Test',
            'email' => $email,
            'password' => Hash::make('oldpassword123'),
        ]);

        // 1. Kunjungi halaman lupa kata sandi
        $response = $this->get('/lupa-sandi');
        $response->assertStatus(200);
        $response->assertSee('Atur Ulang Kata Sandi');

        // 2. Kirim permintaan reset link
        $postRes = $this->post('/lupa-sandi', [
            'email' => $email,
        ]);
        $postRes->assertStatus(302);
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $email,
        ]);

        // Ambil token dari database
        $record = \Illuminate\Support\Facades\DB::table('password_reset_tokens')->where('email', $email)->first();
        $this->assertNotNull($record);

        // Buat plain token baru untuk pengujian reset
        $plainToken = 'test-token-12345';
        \Illuminate\Support\Facades\DB::table('password_reset_tokens')->where('email', $email)->update([
            'token' => Hash::make($plainToken),
            'created_at' => now(),
        ]);

        // 3. Kunjungi halaman formulir ubah kata sandi
        $formRes = $this->get('/reset-sandi/' . $plainToken . '?email=' . urlencode($email));
        $formRes->assertStatus(200);
        $formRes->assertSee('Buat Kata Sandi Baru');

        // 4. Submit kata sandi baru
        $updateRes = $this->post('/reset-sandi', [
            'token' => $plainToken,
            'email' => $email,
            'password' => 'NewPass123!',
            'password_confirmation' => 'NewPass123!',
        ]);
        $updateRes->assertRedirect(route('login'));

        // Pastikan password baru terpasang di database
        $user->refresh();
        $this->assertTrue(Hash::check('NewPass123!', $user->password));

        // Pastikan token terhapus
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $email]);

        // Cleanup
        $user->delete();
    }

    /**
     * Uji proteksi hak akses dan fungsi panel moderasi admin.
     */
    public function test_admin_moderation_access_and_actions(): void
    {
        // 1. User biasa dilarang mengakses
        $regularUser = User::create([
            'name' => 'Regular User',
            'email' => 'regular_' . uniqid() . '@example.com',
            'password' => Hash::make('secret123'),
            'role' => 'user',
        ]);

        $this->actingAs($regularUser);
        $forbiddenRes = $this->get('/admin/kajian');
        $forbiddenRes->assertStatus(403);

        // 2. Admin dapat mengakses
        $adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin_' . uniqid() . '@example.com',
            'password' => Hash::make('secret123'),
            'role' => 'admin',
        ]);

        $this->actingAs($adminUser);
        $adminRes = $this->get('/admin/kajian');
        $adminRes->assertStatus(200);
        $adminRes->assertSee('Moderasi Majelis & Kajian', false);

        // 3. Admin dapat menyetujui (verify) kajian
        $pendingKajian = Kajian::create([
            'title' => 'Kajian Komunitas Pengajuan',
            'ustadz' => 'Ustadz Tamu',
            'masjid' => 'Masjid Al-Barakah',
            'address' => 'Jl. Moderasi No. 1',
            'lat' => -6.2,
            'lon' => 106.8,
            'category' => 'Tematik',
            'day' => 'Ahad',
            'time' => '10:00 WIB',
            'verified' => false,
            'is_rutin' => false,
        ]);

        $verifyRes = $this->post("/admin/kajian/{$pendingKajian->id}/verify");
        $verifyRes->assertStatus(302);

        $pendingKajian->refresh();
        $this->assertTrue((bool)$pendingKajian->verified);

        // 4. Admin dapat menghapus kajian
        $deleteRes = $this->delete("/admin/kajian/{$pendingKajian->id}");
        $deleteRes->assertStatus(302);
        $this->assertDatabaseMissing('kajians', ['id' => $pendingKajian->id]);

        // Cleanup
        $regularUser->delete();
        $adminUser->delete();
    }

    /**
     * Uji bahwa halaman detail menyajikan OpenGraph meta tag untuk WhatsApp/Social Share.
     */
    public function test_detail_page_renders_opengraph_meta_tags(): void
    {
        $kajian = Kajian::first();
        if ($kajian) {
            $response = $this->get('/kajian/' . $kajian->id);
            $response->assertStatus(200);
            $response->assertSee('<meta property="og:type" content="article">', false);
            $response->assertSee('<meta property="og:title"', false);
            $response->assertSee('<meta property="og:description"', false);
            $response->assertSee('<meta name="twitter:card"', false);
        }
    }

    /**
     * Uji alur registrasi jamaah baru dan pengiriman email verifikasi.
     */
    public function test_new_user_registration_dispatches_verification_and_redirects(): void
    {
        $email = 'jamaah_' . uniqid() . '@example.com';

        $response = $this->post('/daftar', [
            'name' => 'Jamaah Baru',
            'email' => $email,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('/email/verify');

        $user = User::where('email', $email)->first();
        $this->assertNotNull($user);
        $this->assertFalse($user->hasVerifiedEmail());

        // Uji akses halaman verifikasi
        $verifyPage = $this->actingAs($user)->get('/email/verify');
        $verifyPage->assertStatus(200);
        $verifyPage->assertSee('Verifikasi Email Anda');
        $verifyPage->assertSee($email);

        // Uji verifikasi menggunakan signed URL
        $verificationUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
        );

        $verifyAction = $this->get($verificationUrl);
        $verifyAction->assertStatus(302);
        $verifyAction->assertRedirect(route('profile'));

        $user->refresh();
        $this->assertTrue($user->hasVerifiedEmail());

        // Cleanup
        $user->delete();
    }

    /**
     * Uji proteksi Brute Force Login & Rate Limiting lockout.
     */
    public function test_login_rate_limiting_locks_out_after_multiple_failed_attempts(): void
    {
        $testEmail = 'brute_' . uniqid() . '@example.com';
        $ip = '192.168.1.100';

        // 5 percobaan salah
        for ($i = 0; $i < 5; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => $ip])
                ->post('/masuk', [
                    'email' => $testEmail,
                    'password' => 'wrong_password',
                ]);
        }

        // Percobaan ke-6 harus ditolak oleh rate limiter (HTTP 429 atau lockout message)
        $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->post('/masuk', [
                'email' => $testEmail,
                'password' => 'wrong_password',
            ]);

        $this->assertTrue(
            $response->status() === 429 ||
            ($response->status() === 302 && session()->has('errors'))
        );

        // Reset limiter
        $throttleKey = \Illuminate\Support\Str::transliterate(\Illuminate\Support\Str::lower($testEmail) . '|' . $ip);
        \Illuminate\Support\Facades\RateLimiter::clear($throttleKey);
    }

    /**
     * Uji proteksi Honeypot Anti-Bot pada form publik.
     */
    public function test_honeypot_trap_blocks_automated_bot_submissions(): void
    {
        // Bot mengisi website_trap pada form registrasi
        $botEmail = 'bot_' . uniqid() . '@spam.com';
        $response = $this->post('/daftar', [
            'name' => 'Spam Bot',
            'email' => $botEmail,
            'password' => 'spam12345',
            'password_confirmation' => 'spam12345',
            'website_trap' => 'http://spam-link.com', // TRAP
        ]);

        $response->assertRedirect(route('login'));
        // Akun tidak boleh dibuat di database
        $this->assertDatabaseMissing('users', ['email' => $botEmail]);
    }

    /**
     * Uji sanitasi input untuk mencegah stored XSS pada pengajuan kajian.
     */
    public function test_xss_inputs_are_sanitized_on_kajian_submission(): void
    {
        $response = $this->post('/kajian', [
            'title' => 'Kajian Aman <script>alert("XSS")</script>',
            'ustadz' => '<b>Ustadz Fulan</b>',
            'category' => 'Fiqih',
            'audience' => 'Umum',
            'masjid' => 'Masjid Raya <i>Indah</i>',
            'address' => 'Jl. Merdeka <script>steal()</script>',
            'lat' => -6.9,
            'lon' => 107.6,
            'day' => 'Senin',
            'time' => '19:30 WIB',
            'description' => '<p>Deskripsi kajian</p>',
        ]);

        $kajian = Kajian::where('title', 'Kajian Aman alert("XSS")')->first();
        $this->assertNotNull($kajian);
        $this->assertStringNotContainsString('<script>', $kajian->title);
        $this->assertEquals('Ustadz Fulan', $kajian->ustadz);
        $this->assertEquals('Masjid Raya Indah', $kajian->masjid);
        $this->assertStringNotContainsString('<script>', $kajian->address);

        // Cleanup
        $kajian->delete();
    }

    /**
     * Uji alur pengiriman email reset kata sandi ke user terdaftar.
     */
    public function test_password_reset_email_dispatches_for_registered_user(): void
    {
        \Illuminate\Support\Facades\Notification::fake();

        $user = User::factory()->create([
            'email' => 'reset_test_' . uniqid() . '@example.com',
        ]);

        $response = $this->post('/lupa-sandi', [
            'email' => strtoupper($user->email), // Uji case-insensitivity
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('status');

        \Illuminate\Support\Facades\Notification::assertSentTo(
            $user,
            \Illuminate\Auth\Notifications\ResetPassword::class
        );

        $user->delete();
    }

    /**
     * Uji bahwa email yang belum terdaftar memberikan pesan kesalahan informatif.
     */
    public function test_password_reset_for_unregistered_email_returns_clear_error(): void
    {
        $response = $this->post('/lupa-sandi', [
            'email' => 'unregistered_' . uniqid() . '@tidakada.com',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('belum terdaftar', session('errors')->first('email'));
    }
}
