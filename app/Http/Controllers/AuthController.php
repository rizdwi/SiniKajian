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

        $bookmarkedKajians = $user->bookmarkedKajians()->get();
        $attendances = $user->attendances()->with('kajian')->latest()->take(10)->get();
        $submittedKajians = $user->submittedKajians()->latest()->get();

        return view('auth.profile', [
            'user' => $user,
            'savedKajians' => $bookmarkedKajians,
            'bookmarkedKajians' => $bookmarkedKajians,
            'attendances' => $attendances,
            'submittedKajians' => $submittedKajians,
        ]);
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
