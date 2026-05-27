<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;    // Untuk hit API Google
use Illuminate\Support\Str;             // Untuk generate password random
use Illuminate\Support\Facades\Password; // <-- TAMBAHAN BARU untuk Broker Reset Password
use Illuminate\Auth\Events\PasswordReset; // <-- TAMBAHAN BARU untuk trigger event password

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'nama'     => 'required|string',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:8',
        ]);

        $user = User::create([
            'nama'     => $request->nama,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registrasi berhasil',
            'token'   => $token,
            'user'    => $user,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Email atau password salah'
            ], 401);
        }

        if ($user->status === 'nonaktif') {
            return response()->json([
                'message' => 'Akun kamu telah dinonaktifkan'
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil',
            'token'   => $token,
            'user'    => $user,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logout berhasil']);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'nama' => 'required|string',
        ]);

        $user = $request->user();
        $user->update(['nama' => $request->nama]);

        return response()->json([
            'message' => 'Profil berhasil diupdate',
            'user'    => $user,
        ]);
    }

    // ===== GOOGLE OAUTH LOGIN (SUDAH FIX DATABASE SINKRON) =====
    public function googleLogin(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $googleToken = $request->token;

        // 1. Verifikasi token ke API Google
        $response = Http::get("https://www.googleapis.com/oauth2/v3/userinfo?access_token={$googleToken}");

        if ($response->failed()) {
            return response()->json([
                'message' => 'Token Google tidak valid atau sudah kedaluwarsa'
            ], 401);
        }

        $googleUser = $response->json();
        
        // Mengantisipasi format ID unik dari Google (bisa berupa 'sub' atau 'id')
        $googleId = $googleUser['sub'] ?? $googleUser['id'] ?? null;
        $email = $googleUser['email'] ?? null;
        $nama = $googleUser['name'] ?? null;

        if (!$email) {
            return response()->json(['message' => 'Gagal mendapatkan email dari Google'], 400);
        }

        // Ambil data user yang sudah ada saat ini (jika ada) untuk pengecekan password/role
        $existingUser = User::where('email', $email)->first();

        // 2. Gunakan updateOrCreate agar data 'google_id' tersimpan atau terupdate di phpMyAdmin
        $user = User::updateOrCreate(
            ['email' => $email], // Kunci pencarian berdasarkan email
            [
                'nama'      => $nama,
                'google_id' => $googleId, // <--- Menjamin kolom google_id di database terisi angka unik
                // Jika user lama sudah punya password, biarkan password lamanya. Jika user baru, beri string acak.
                'password'  => $existingUser ? $existingUser->password : Hash::make(Str::random(24)), 
                'role'      => $existingUser ? $existingUser->role : 'user', // Default role 'user'
                'status'    => $existingUser ? $existingUser->status : 'aktif' // Default status 'aktif'
            ]
        );

        // 3. Keamanan Tambahan: Cek apakah user Google ini dinonaktifkan oleh admin
        if ($user->status === 'nonaktif') {
            return response()->json([
                'message' => 'Akun kamu telah dinonaktifkan'
            ], 403);
        }

        // 4. Buat token akses via Sanctum agar React bisa membaca status login
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil via Google',
            'token'   => $token,
            'user'    => [
                'id'    => $user->id,
                'nama'  => $user->nama,
                'email' => $user->email,
                'role'  => $user->role
            ],
        ]);
    }

    // ===== FITUR BARU: FORGOT & RESET PASSWORD LOGIC =====

    /**
     * Menangani pengiriman token reset link ke email pengguna
     */
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email'    => 'Format email tidak valid.'
        ]);

        // Broker Laravel otomatis membuat token acak dan mengirimkan email bawaannya
        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => 'Link reset password berhasil dikirim ke email kamu.'
            ], 200);
        }

        return response()->json([
            'message' => 'Email tidak terdaftar di sistem kami.'
        ], 400);
    }

    /**
     * Memproses penggantian password lama dengan password baru di database
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => 'required|min:8|confirmed',
        ], [
            'password.required'  => 'Password baru wajib diisi.',
            'password.min'       => 'Password minimal berisi 8 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.'
        ]);

        // Eksekusi perubahan data password pada row database user terkait
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Password kamu berhasil diperbarui. Silakan login kembali.'
            ], 200);
        }

        return response()->json([
            'message' => 'Link reset tidak valid atau sudah kedaluwarsa.'
        ], 400);
    }
}