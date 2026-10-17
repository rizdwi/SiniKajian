<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Tampilkan formulir masuk akun.
     */
    public function showLogin(): View
    {
        return view('auth.login');
    }

    /**
     * Proses autentikasi masuk akun dengan proteksi kredensial.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            return redirect()->intended(route('profile'))
                ->with('success', 'Selamat datang kembali di SiniKajian.');
        }

        throw ValidationException::withMessages([
            'email' => 'Alamat email atau kata sandi yang Anda masukkan tidak sesuai.',
        ]);
    }

    /**
     * Tampilkan formulir pendaftaran akun baru.
     */
    public function showRegister(): View
    {
        return view('auth.register');
    }

    /**
     * Daftarkan akun baru jamaah dengan kata sandi terenkripsi (bcrypt).
     */
    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => [
                'required', 'string', 'confirmed',
                Password::min(8)
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        Auth::login($user);

        // Kirim email verifikasi
        $user->sendEmailVerificationNotification();

        $request->session()->regenerate();

        return redirect()->route('verification.notice')
            ->with('success', 'Akun berhasil dibuat. Cek email untuk verifikasi.');
    }

    /**
     * Tampilkan dashboard profil jamaah & ringkasan akun.
     */
    public function profile(): \Illuminate\Http\RedirectResponse|View
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Silakan login dulu.');
        }
        /** @var User $user */
        $user = Auth::user();

        // Override dengan data terbaru dari session (jika baru update) atau Supabase
        if (session()->has('profile_name')) {
            $user->name = session('profile_name');
            $user->email = session('profile_email', $user->email);
        } else {
            try {
                $svc = new \App\Services\SupabaseUserService();
                if ($svc->enabled()) {
                    $sbUser = $svc->findOrCreate(['name' => $user->name, 'email' => $user->email]);
                    if ($sbUser) {
                        $user->name = $sbUser['name'] ?? $user->name;
                        $user->email = $sbUser['email'] ?? $user->email;
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('Ambil profil Supabase gagal: ' . $e->getMessage());
            }
        }

        // Ambil kajian tersimpan dari Supabase (persisten)
        $bookmarkedKajians = $this->getUserBookmarks($user->id);

        $attendances = $user->attendances()
            ->with('kajian')
            ->latest()
            ->take(10)
            ->get();

        $submittedKajians = $user->submittedKajians()->latest()->get();

        $savedCount = $bookmarkedKajians->count();
        $agendaCount = $attendances->count();
        $majelisCount = $user->bookmarkedKajians()->distinct('masjid')->count('masjid');

        return view('auth.profile', [
            'user' => $user,
            'savedKajians' => $bookmarkedKajians,
            'bookmarkedKajians' => $bookmarkedKajians,
            'attendances' => $attendances,
            'submittedKajians' => $submittedKajians,
            'savedCount' => $savedCount,
            'agendaCount' => $agendaCount,
            'majelisCount' => $majelisCount,
        ]);
    }

    /**
     * Tampilkan formulir edit profil (Screen Edit Profil).
     */
    public function editProfile(): View
    {
        return view('auth.edit', [
            'user' => Auth::user(),
        ]);
    }

    /**
     * Perbarui data profil dan simpan foto avatar secara persisten.
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'avatar' => 'nullable|image|max:3072',
            'avatar_data' => 'nullable|string',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;

        // Simpan avatar sebagai base64 string di SQLite agar tidak hilang pada lingkungan serverless/container
        if ($request->hasFile('avatar') && $request->file('avatar')->isValid()) {
            $file = $request->file('avatar');
            $mime = $file->getMimeType();
            $base64 = base64_encode(file_get_contents($file->getRealPath()));
            $user->avatar = 'data:' . $mime . ';base64,' . $base64;
        } elseif ($request->filled('avatar_data') && str_starts_with($request->avatar_data, 'data:image')) {
            $user->avatar = $request->avatar_data;
        }

        $user->save();

        // Sync ke Supabase agar persisten (SQLite Vercel ephemeral)
        try {
            $svc = new \App\Services\SupabaseUserService();
            if ($svc->enabled()) {
                // Cari user di Supabase by email lama, update atau buat baru
                $svc->findOrCreate([
                    'name' => $user->name,
                    'email' => $user->email,
                    'provider' => $user->provider,
                    'provider_id' => $user->provider_id,
                ]);
                // Update data terbaru
                $sbUser = $svc->findOrCreate(['name' => $user->name, 'email' => $user->email]);
                if ($sbUser && isset($sbUser['id'])) {
                    $svc->update($sbUser['id'], [
                        'name' => $request->name,
                        'email' => $request->email,
                    ]);
                }
            }
        } catch (\Exception $e) {
            \Log::warning('Sync profil ke Supabase gagal: ' . $e->getMessage());
        }

        // Simpan juga di session agar langsung terlihat
        session(['profile_name' => $request->name, 'profile_email' => $request->email]);

        return redirect()->route('profile')->with('success', 'Profil dan foto berhasil diperbarui!');
    }

    /**
     * Keluar dari sesi akun dan amankan kredensial.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->flush();

        return redirect()->route('kajian.index');
    }

    /**
     * Ambil bookmark user dari Supabase (persisten) dengan fallback lokal.
     */
    private function getUserBookmarks(int $userId)
    {
        $supabaseUrl = config('services.supabase.url');
        $supabaseKey = config('services.supabase.service_key');

        if ($supabaseUrl && $supabaseKey) {
            try {
                $client = new \GuzzleHttp\Client(['timeout' => 10]);
                $res = $client->get("{$supabaseUrl}/rest/v1/bookmarks", [
                    'headers' => [
                        'apikey' => $supabaseKey,
                        'Authorization' => 'Bearer ' . $supabaseKey,
                    ],
                    'query' => ['user_id' => "eq.{$userId}", 'select' => 'kajian_id'],
                ]);
                $rows = json_decode($res->getBody(), true);
                $ids = array_column($rows ?? [], 'kajian_id');
                if (!empty($ids)) {
                    return \App\Models\Kajian::whereIn('id', $ids)->get();
                }
                return collect();
            } catch (\Exception $e) {
                \Log::warning('Supabase get bookmarks gagal: ' . $e->getMessage());
            }
        }

        $user = User::find($userId);
        return $user ? $user->bookmarkedKajians()->get() : collect();
    }
}
