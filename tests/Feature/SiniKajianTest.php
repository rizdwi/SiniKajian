<?php

namespace Tests\Feature;

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
            $response->assertSee('Saya ingin hadir');
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
}
