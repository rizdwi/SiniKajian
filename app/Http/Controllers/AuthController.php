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
            $cutoff = \Carbon\Carbon::now('Asia/Jakarta')->subHours(3);
            $bookmarkedKajians = $user->bookmarkedKajians()
                ->where('verified', true)
                ->where('event_datetime', '>=', $cutoff)
                ->orderBy('event_datetime', 'asc')
                ->get();

            $attendances = $user->attendances()
                ->with('kajian')
                ->latest()
                ->take(10)
                ->get();

            $submittedKajians = $user->submittedKajians()->latest()->get();

            $savedCount = $bookmarkedKajians->count();
            $agendaCount = $attendances->count();
            $majelisCount = $user->bookmarkedKajians()->distinct('masjid')->count('masjid');
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
}
