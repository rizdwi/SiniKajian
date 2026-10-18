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
            'password' => ['required', 'string', 'confirmed', Password::min(6)],
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
    public function profile(): View
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user) {
            // Override dengan data terbaru dari session (jika baru update) atau Supabase
            if (session()->has('profile_name')) {
                $user->name = session('profile_name');
                $user->email = session('profile_email', $user->email);
            }
            
            try {
                $svc = new \App\Services\SupabaseUserService();
                if ($svc->enabled()) {
                    $sbUser = $svc->findOrCreate(['name' => $user->name, 'email' => $user->email]);
                    if ($sbUser) {
                        if (!session()->has('profile_name')) {
                            $user->name = $sbUser['name'] ?? $user->name;
                            $user->email = $sbUser['email'] ?? $user->email;
                        }
                        if (!empty($sbUser['avatar'])) {
                            $user->avatar = $sbUser['avatar'];
                        }
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('Ambil profil Supabase gagal: ' . $e->getMessage());
            }

            $cutoff = \Carbon\Carbon::now('Asia/Jakarta')->subHours(3)->toDateTimeString();
            // Ambil kajian tersimpan dari Supabase / SQLite dengan filter cutoff aktif
            $allBookmarks = $this->getUserBookmarks($user->id);
            $bookmarkedKajians = $allBookmarks->filter(function ($k) use ($cutoff) {
                if (empty($k->event_datetime)) return true;
                return $k->event_datetime >= $cutoff;
            });

            // Ambil kehadiran dari Supabase (persisten)
            $attendances = $this->getUserAttendances($user->id);

            $submittedKajians = $user->submittedKajians()->latest()->get();

            $savedCount = $allBookmarks->count();
            $agendaCount = $attendances->count();

            $dbFollowCount = \App\Models\MajelisFollow::where('user_id', $user->id)->count();
            $sessionFollows = session()->get('followed_majelis', []);
            $bmMajelisCount = $user->bookmarkedKajians()->distinct('masjid')->count('masjid');
            $majelisCount = max($dbFollowCount, count($sessionFollows), $bmMajelisCount);
        } else {
            $bookmarkedKajians = collect();
            $attendances = collect();
            $submittedKajians = collect();
            $savedCount = 0;
            $agendaCount = 0;
            $majelisCount = 0;
        }

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
        /** @var User|null $user */
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login')->with('error', 'Silakan masuk terlebih dahulu.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'avatar' => 'nullable|image|max:5120',
            'avatar_data' => 'nullable|string',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;

        // Simpan avatar sebagai base64 data URI di SQLite agar persisten di serverless
        if ($request->filled('avatar_data') && str_starts_with($request->avatar_data, 'data:image')) {
            $user->avatar = $request->avatar_data;
        } elseif ($request->hasFile('avatar') && $request->file('avatar')->isValid()) {
            $file = $request->file('avatar');
            $mime = $file->getMimeType();
            $base64 = base64_encode(file_get_contents($file->getRealPath()));
            $user->avatar = 'data:' . $mime . ';base64,' . $base64;
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
                    $updatePayload = [
                        'name' => $request->name,
                        'email' => $request->email,
                    ];
                    if (!empty($user->avatar)) {
                        $updatePayload['avatar'] = $user->avatar;
                    }
                    $svc->update($sbUser['id'], $updatePayload);
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
                $res = \Illuminate\Support\Facades\Http::withHeaders([
                        'apikey' => $supabaseKey,
                        'Authorization' => 'Bearer ' . $supabaseKey,
                    ])
                    ->timeout(10)
                    ->get("{$supabaseUrl}/rest/v1/bookmarks", [
                        'user_id' => "eq.{$userId}",
                        'select' => 'kajian_id',
                    ]);
                $rows = $res->json();
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

    /**
     * Ambil attendance user dari Supabase (persisten) dengan fallback lokal.
     */
    private function getUserAttendances(int $userId)
    {
        $supabaseUrl = config('services.supabase.url');
        $supabaseKey = config('services.supabase.service_key');

        if ($supabaseUrl && $supabaseKey) {
            try {
                $res = \Illuminate\Support\Facades\Http::withHeaders([
                        'apikey' => $supabaseKey,
                        'Authorization' => 'Bearer ' . $supabaseKey,
                    ])
                    ->timeout(10)
                    ->get("{$supabaseUrl}/rest/v1/attendances", [
                        'user_id' => "eq.{$userId}",
                        'select' => 'kajian_id,attendance_date',
                        'order' => 'attendance_date.desc',
                    ]);
                $rows = $res->json();
                $ids = array_column($rows ?? [], 'kajian_id');
                if (!empty($ids)) {
                    $kajians = \App\Models\Kajian::whereIn('id', $ids)->get()->keyBy('id');
                    // Kembalikan sebagai collection dengan info tanggal
                    return collect($rows)->map(function ($r) use ($kajians) {
                        $k = $kajians[$r['kajian_id']] ?? null;
                        if ($k) $k->attendance_date = $r['attendance_date'];
                        return $k;
                    })->filter();
                }
                return collect();
            } catch (\Exception $e) {
                \Log::warning('Supabase get attendances gagal: ' . $e->getMessage());
            }
        }

        $user = User::find($userId);
        return $user ? $user->attendances()->with('kajian')->latest()->take(10)->get() : collect();
    }
}
