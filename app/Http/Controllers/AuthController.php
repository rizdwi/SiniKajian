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
        // 1. Honeypot check (silent drop bagi bot)
        if ($request->filled('website_trap')) {
            return back()->withErrors(['email' => 'Percobaan masuk tidak valid.'])->onlyInput('email');
        }

        // 1b. Cloudflare Turnstile verification (jika dikonfigurasi)
        $turnstileSecret = config('services.turnstile.secret_key');
        if ($turnstileSecret) {
            $token = $request->input('cf-turnstile-response');
            if (!$token || !$this->verifyTurnstile($token, $turnstileSecret, $request->ip())) {
                return back()->withErrors(['email' => 'Verifikasi keamanan gagal. Silakan coba lagi.'])->onlyInput('email');
            }
        }

        // 2. Proteksi Brute Force & Credential Stuffing
        $throttleKey = \Illuminate\Support\Str::transliterate(\Illuminate\Support\Str::lower((string) $request->input('email')) . '|' . $request->ip());
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($throttleKey);
            return back()->withErrors([
                'email' => "Terlalu banyak percobaan masuk yang salah. Demi keamanan, akun terkunci sementara. Silakan coba lagi dalam {$seconds} detik.",
            ])->onlyInput('email');
        }

        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            \Illuminate\Support\Facades\RateLimiter::clear($throttleKey);
            $request->session()->regenerate();

            return redirect()->intended(route('profile'))
                ->with('success', 'Selamat datang kembali, ' . Auth::user()->name . '!');
        }

        // Fallback: Autentikasi via Supabase jika instance Vercel ter-reset
        $sbSvc = new \App\Services\SupabaseUserService();
        if ($sbSvc->enabled()) {
            $sbUser = $sbSvc->findByEmail($credentials['email']);
            if ($sbUser && !empty($sbUser['password']) && \Illuminate\Support\Facades\Hash::check($credentials['password'], $sbUser['password'])) {
                $user = User::updateOrCreate(
                    ['email' => strtolower($sbUser['email'])],
                    [
                        'name' => $sbUser['name'] ?? 'Jamaah',
                        'password' => $sbUser['password'],
                        'avatar' => $sbUser['avatar'] ?? null,
                        'email_verified_at' => $sbUser['email_verified_at'] ?? now(),
                    ]
                );
                Auth::login($user, $remember);
                \Illuminate\Support\Facades\RateLimiter::clear($throttleKey);
                $request->session()->regenerate();

                return redirect()->intended(route('profile'))
                    ->with('success', 'Selamat datang kembali, ' . $user->name . '!');
            }
        }

        \Illuminate\Support\Facades\RateLimiter::hit($throttleKey, 60);

        return back()->withErrors([
            'email' => 'Kombinasi email dan kata sandi tidak cocok.',
        ])->onlyInput('email');
    }

    /**
     * Tampilkan formulir permintaan reset kata sandi.
     */
    public function showForgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Buat dan kirim token/tautan reset kata sandi.
     */
    public function sendResetLink(Request $request): RedirectResponse
    {
        // 1. Honeypot check (bot trap)
        if ($request->filled('website_trap')) {
            return back()->with('status', 'Tautan reset kata sandi telah diproses.');
        }

        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = strtolower(trim((string) $validated['email']));

        // 2. Rate Limiting anti-email bombing
        $ipKey = 'forgot-pw-ip:' . $request->ip();
        $emailKey = 'forgot-pw-email:' . $email;

        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($ipKey, 5) || \Illuminate\Support\Facades\RateLimiter::tooManyAttempts($emailKey, 5)) {
            $seconds = max(
                \Illuminate\Support\Facades\RateLimiter::availableIn($ipKey),
                \Illuminate\Support\Facades\RateLimiter::availableIn($emailKey)
            );
            return back()->withErrors([
                'email' => "Permintaan reset sandi terlalu sering. Demi keamanan, silakan tunggu {$seconds} detik sebelum mencoba kembali.",
            ])->onlyInput('email');
        }

        \Illuminate\Support\Facades\RateLimiter::hit($ipKey, 600); // 10 menit
        \Illuminate\Support\Facades\RateLimiter::hit($emailKey, 120); // 2 menit

        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
        if (!$user) {
            $sbSvc = new \App\Services\SupabaseUserService();
            if ($sbSvc->enabled()) {
                $sbUser = $sbSvc->findByEmail($email);
                if ($sbUser) {
                    $user = User::create([
                        'name' => $sbUser['name'] ?? 'Jamaah',
                        'email' => strtolower($sbUser['email']),
                        'password' => $sbUser['password'] ?? \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(16)),
                        'avatar' => $sbUser['avatar'] ?? null,
                        'email_verified_at' => $sbUser['email_verified_at'] ?? now(),
                    ]);
                }
            }
        }

        if (!$user) {
            return back()->withErrors([
                'email' => 'Alamat email (' . $email . ') belum terdaftar di Majelisku. Silakan periksa kembali atau buat akun baru terlebih dahulu.',
            ])->onlyInput('email');
        }

        $plainToken = \Illuminate\Support\Str::random(60);
        \Illuminate\Support\Facades\DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => \Illuminate\Support\Facades\Hash::make($plainToken),
                'created_at' => now(),
            ]
        );

        try {
            $user->sendPasswordResetNotification($plainToken);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Gagal mengirim email reset sandi ke {$email}: " . $e->getMessage());
            return back()->withErrors([
                'email' => 'Gagal mengirimkan email reset kata sandi. Silakan coba lagi beberapa saat.',
            ])->onlyInput('email');
        }

        return back()->with('status', 'Tautan untuk mengatur ulang kata sandi telah berhasil dikirimkan ke kotak masuk email ' . $email . '. Silakan periksa kotak masuk atau folder spam Anda.');
    }

    /**
     * Tampilkan formulir pembaruan kata sandi baru.
     */
    public function showResetPassword(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    /**
     * Proses pembaruan kata sandi baru.
     */
    public function resetPassword(Request $request): RedirectResponse
    {
        // 1. Honeypot check
        if ($request->filled('website_trap')) {
            return redirect()->route('login');
        }

        // 2. Rate limiting reset attempt
        $key = 'reset-pw-ip:' . $request->ip();
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($key);
            return back()->withErrors(['email' => "Terlalu banyak percobaan reset kata sandi. Silakan tunggu {$seconds} detik."]);
        }

        $validated = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->numbers()->symbols()],
        ]);

        $email = strtolower(trim((string) $validated['email']));

        \Illuminate\Support\Facades\RateLimiter::hit($key, 900);

        $record = \Illuminate\Support\Facades\DB::table('password_reset_tokens')
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if (!$record || !\Illuminate\Support\Facades\Hash::check($request->token, $record->token)) {
            return back()->withErrors(['email' => 'Token reset kata sandi tidak valid atau telah kadaluarsa.']);
        }

        $createdAt = \Carbon\Carbon::parse($record->created_at);
        if ($createdAt->addMinutes(60)->isPast()) {
            \Illuminate\Support\Facades\DB::table('password_reset_tokens')->whereRaw('LOWER(email) = ?', [$email])->delete();
            return back()->withErrors(['email' => 'Token reset kata sandi telah kadaluarsa. Silakan ajukan ulang.']);
        }

        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
        if ($user) {
            $user->password = \Illuminate\Support\Facades\Hash::make($request->password);
            $user->save();

            // Sinkronisasi ke Supabase jika aktif
            $svc = new \App\Services\SupabaseUserService();
            $svc->updatePassword($email, $request->password);
        }

        \Illuminate\Support\Facades\DB::table('password_reset_tokens')->whereRaw('LOWER(email) = ?', [$email])->delete();

        return redirect()->route('login')->with('status', 'Kata sandi Anda berhasil diperbarui. Silakan masuk.');
    }

    /**
     * Tampilkan formulir pendaftaran akun jamaah baru.
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
        // 1. Honeypot check
        if ($request->filled('website_trap')) {
            return redirect()->route('login');
        }

        // 2. Cloudflare Turnstile verification (jika dikonfigurasi)
        $turnstileSecret = config('services.turnstile.secret_key');
        if ($turnstileSecret) {
            $token = $request->input('cf-turnstile-response');
            if (!$token || !$this->verifyTurnstile($token, $turnstileSecret, $request->ip())) {
                return back()->withErrors([
                    'email' => 'Verifikasi keamanan gagal. Silakan coba lagi.',
                ])->withInput();
            }
        }

        // 3. Rate limiting pendaftaran per IP
        $regKey = 'reg-ip:' . $request->ip();
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($regKey, 3)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($regKey);
            return back()->withErrors([
                'email' => "Terlalu banyak pendaftaran dari perangkat Anda. Silakan coba lagi dalam {$seconds} detik.",
            ])->withInput();
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->numbers()->symbols()],
        ]);

        \Illuminate\Support\Facades\RateLimiter::hit($regKey, 600); // 10 menit decay

        $user = User::create([
            'name' => strip_tags(trim($validated['name'])),
            'email' => strtolower(trim($validated['email'])),
            'password' => Hash::make($validated['password']),
        ]);

        // Sinkronisasi otomatis ke Supabase agar persisten lintas restart Vercel
        try {
            $sbSvc = new \App\Services\SupabaseUserService();
            if ($sbSvc->enabled()) {
                $sbSvc->findOrCreate([
                    'name' => $user->name,
                    'email' => $user->email,
                    'password' => $user->password,
                ]);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Supabase sync user register gagal: ' . $e->getMessage());
        }

        Auth::login($user);

        // Kirim email verifikasi
        try {
            $user->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Gagal mengirim email verifikasi ke ' . $user->email . ': ' . $e->getMessage());
        }

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
            // Ambil kajian tersimpan dari Supabase (gunakan Supabase user ID, bukan local ID)
            $supabaseUserId = isset($sbUser) && isset($sbUser['id']) ? $sbUser['id'] : null;
            $allBookmarks = $supabaseUserId ? $this->getUserBookmarksBySupabaseId($supabaseUserId) : collect();
            $bookmarkedKajians = $allBookmarks->filter(function ($k) use ($cutoff) {
                if (empty($k->event_datetime)) return true;
                return $k->event_datetime >= $cutoff;
            });

            // Ambil kehadiran dari Supabase (gunakan Supabase user ID)
            $attendances = $supabaseUserId ? $this->getUserAttendancesBySupabaseId($supabaseUserId) : collect();

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

        // Ustadz paling sering muncul (top 8 by jumlah kajian aktif)
        $topUstadz = [];
        try {
            $svc = new \App\Services\SupabaseKajianService();
            if ($svc->isConfigured()) {
                $kajians = $svc->getActiveKajians();
                $counts = [];
                foreach ($kajians as $k) {
                    $u = trim($k['ustadz'] ?? '');
                    if ($u !== '') $counts[$u] = ($counts[$u] ?? 0) + 1;
                }
                arsort($counts);
                $topUstadz = array_slice(array_keys($counts), 0, 8);
            }
        } catch (\Exception $e) {
            \Log::warning('Ambil top ustadz gagal: ' . $e->getMessage());
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
            'topUstadz' => $topUstadz,
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

        // Upload avatar ke Supabase Storage (bucket avatars)
        $avatarUrl = null;
        if ($request->filled('avatar_data') && str_starts_with($request->avatar_data, 'data:image')) {
            $avatarUrl = $this->uploadAvatarToStorage($request->input('avatar_data'), $user->id);
        } elseif ($request->hasFile('avatar') && $request->file('avatar')->isValid()) {
            $file = $request->file('avatar');
            $dataUri = 'data:' . $file->getMimeType() . ';base64,' . base64_encode(file_get_contents($file->getRealPath()));
            $avatarUrl = $this->uploadAvatarToStorage($dataUri, $user->id);
        }
        if ($avatarUrl) {
            $user->avatar = $avatarUrl;
        }

        $user->save();

        // Sync ke Supabase agar persisten (SQLite Vercel ephemeral)
        try {
            $svc = new \App\Services\SupabaseUserService();
            if ($svc->enabled()) {
                $sbUser = $svc->findOrCreate([
                    'name' => $user->name,
                    'email' => $user->email,
                    'provider' => $user->provider,
                    'provider_id' => $user->provider_id,
                ]);
                if ($sbUser && isset($sbUser['id'])) {
                    $updatePayload = [
                        'name' => $request->name,
                        'email' => $request->email,
                    ];
                    if (!empty($user->avatar)) {
                        $updatePayload['avatar'] = $user->avatar;
                    }
                    $ok = $svc->update($sbUser['id'], $updatePayload);
                    if (!$ok) {
                        \Log::warning('Sync avatar ke Supabase gagal untuk user ' . $user->email);
                    }
                } else {
                    \Log::warning('findOrCreate Supabase return null untuk ' . $user->email);
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
        $request->session()->invalidate();
        $request->session()->regenerateToken();

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
     * Ambil bookmarks via Supabase user ID (tanpa fallback SQLite).
     */
    private function getUserBookmarksBySupabaseId(int $supabaseUserId)
    {
        $supabaseUrl = config('services.supabase.url');
        $supabaseKey = config('services.supabase.service_key');

        if (!$supabaseUrl || !$supabaseKey) return collect();

        try {
            $res = \Illuminate\Support\Facades\Http::withHeaders([
                    'apikey' => $supabaseKey,
                    'Authorization' => 'Bearer ' . $supabaseKey,
                ])
                ->timeout(10)
                ->get("{$supabaseUrl}/rest/v1/bookmarks", [
                    'user_id' => "eq.{$supabaseUserId}",
                    'select' => 'kajian_id',
                ]);
            $rows = $res->json();
            $ids = array_column($rows ?? [], 'kajian_id');
            if (!empty($ids)) {
                return \App\Models\Kajian::whereIn('id', $ids)->get();
            }
        } catch (\Exception $e) {
            \Log::warning('Supabase get bookmarks gagal: ' . $e->getMessage());
        }
        return collect();
    }

    /**
     * Ambil attendances via Supabase user ID (tanpa fallback SQLite).
     */
    private function getUserAttendancesBySupabaseId(int $supabaseUserId)
    {
        $supabaseUrl = config('services.supabase.url');
        $supabaseKey = config('services.supabase.service_key');

        if (!$supabaseUrl || !$supabaseKey) return collect();

        try {
            $res = \Illuminate\Support\Facades\Http::withHeaders([
                    'apikey' => $supabaseKey,
                    'Authorization' => 'Bearer ' . $supabaseKey,
                ])
                ->timeout(10)
                ->get("{$supabaseUrl}/rest/v1/attendances", [
                    'user_id' => "eq.{$supabaseUserId}",
                    'select' => 'kajian_id',
                ]);
            $rows = $res->json();
            $ids = array_column($rows ?? [], 'kajian_id');
            if (!empty($ids)) {
                return \App\Models\Kajian::whereIn('id', $ids)->get();
            }
        } catch (\Exception $e) {
            \Log::warning('Supabase get attendances gagal: ' . $e->getMessage());
        }
        return collect();
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

    /**
     * Verifikasi Cloudflare Turnstile token.
     */
    protected function verifyTurnstile(string $token, string $secret, string $ip): bool
    {
        try {
            $res = \Illuminate\Support\Facades\Http::asForm()
                ->timeout(10)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => $secret,
                    'response' => $token,
                    'remoteip' => $ip,
                ]);
            $data = $res->json();
            return $data['success'] ?? false;
        } catch (\Exception $e) {
            \Log::warning('Turnstile verification gagal: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Upload avatar ke Supabase Storage bucket avatars.
     * Return public URL atau null jika gagal.
     */
    private function uploadAvatarToStorage(string $dataUri, $userId): ?string
    {
        try {
            // Parse data URI
            if (!preg_match('/^data:(image\/\w+);base64,(.+)$/', $dataUri, $m)) {
                return null;
            }
            $mime = $m[1];
            $data = base64_decode($m[2]);
            if (!$data) return null;

            $ext = match($mime) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                'image/gif' => 'gif',
                default => 'jpg',
            };

            $svc = new \App\Services\SupabaseUserService();
            if (!$svc->enabled()) return null;

            $filename = "avatar_{$userId}_" . time() . ".{$ext}";

            $url = $svc->getSupabaseUrl();
            $key = $svc->getServiceKey();

            $res = \Illuminate\Support\Facades\Http::withHeaders([
                    'apikey' => $key,
                    'Authorization' => 'Bearer ' . $key,
                    'Content-Type' => $mime,
                ])
                ->timeout(30)
                ->withBody($data, $mime)
                ->post("{$url}/storage/v1/object/avatars/{$filename}");

            if ($res->successful()) {
                return "{$url}/storage/v1/object/public/avatars/{$filename}";
            }

            \Log::warning('Upload avatar gagal: ' . $res->body());
            return null;
        } catch (\Exception $e) {
            \Log::warning('Upload avatar exception: ' . $e->getMessage());
            return null;
        }
    }
}
