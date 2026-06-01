<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;    
use Illuminate\Support\Str;             
use Illuminate\Support\Facades\Password; 
use Illuminate\Auth\Events\PasswordReset; 

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'nama'     => 'required|string',
            'username' => 'required|string|unique:users,username',
            'email'    => 'required|email|unique:users,email',
            'no_hp'    => 'required|string', 
            'password' => 'required|min:8',
        ]);

        $user = User::create([
            'nama'     => $request->nama, 
            'username' => $request->username,
            'email'    => $request->email,
            'no_hp'    => $request->no_hp, 
            'password' => Hash::make($request->password),
            'status'   => 'aktif', 
            'role'     => 'user',   
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
            return response()->json(['message' => 'Email atau password salah'], 401);
        }

        if ($user->status === 'nonaktif') {
            return response()->json(['message' => 'Akun kamu telah dinonaktifkan'], 403);
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
        $user = $request->user();

        // Validasi data input dari React frontend
        $request->validate([
            'nama'          => 'required|string|max:255',
            'username'      => 'required|string|max:255|unique:users,username,' . $user->id,
            'tanggal_lahir' => 'nullable|string',
            'kota'          => 'nullable|string',
            'jenis_kelamin' => 'nullable|string',
            'avatar'        => 'nullable|string', 
        ]);

        // Mapping manual satu per satu agar kebal dari error ketidakcocokan camelCase/snake_case
        $user->nama = $request->nama;
        $user->username = $request->username;
        $user->tanggal_lahir = $request->tanggal_lahir ?: null;
        $user->kota = $request->kota ?: null;
        $user->jenis_kelamin = $request->jenis_kelamin ?: 'Laki-laki';

        // Jika user melakukan upload avatar baru (Base64 string), masukkan ke database
        if ($request->has('avatar')) {
            $user->avatar = $request->avatar;
        }

        // Simpan langsung perubahan ke tabel MySQL menggunakan method save()
        $user->save();

        return response()->json([
            'message' => 'Profil berhasil diupdate',
            'user'    => $user,
        ]);
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'email'         => 'required|email|unique:users,email,' . $user->id,
            'no_hp'         => 'nullable|string',
            'password_lama' => 'nullable|string',
            'password'      => 'nullable|min:8', 
        ]);

        $user->email = $request->email;
        $user->no_hp = $request->no_hp;

        if ($request->filled('password')) {
            if (!$request->password_lama || !Hash::check($request->password_lama, $user->password)) {
                return response()->json([
                    'message' => 'Password lama tidak cocok atau wajib diisi.'
                ], 422);
            }
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return response()->json([
            'message' => 'Pengaturan keamanan berhasil diperbarui',
            'user'    => $user,
        ]);
    }

    public function googleLogin(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $googleToken = $request->token;
        $response = Http::get("https://www.googleapis.com/oauth2/v3/userinfo?access_token={$googleToken}");

        if ($response->failed()) {
            return response()->json(['message' => 'Token Google tidak valid'], 401);
        }

        $googleUser = $response->json();
        $googleId = $googleUser['sub'] ?? $googleUser['id'] ?? null;
        $email = $googleUser['email'] ?? null;
        $nama = $googleUser['name'] ?? null;

        if (!$email) {
            return response()->json(['message' => 'Gagal mendapatkan email dari Google'], 400);
        }

        $existingUser = User::where('email', $email)->first();
        $usernameFromEmail = explode('@', $email)[0] . Str::random(4);

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'nama'      => $nama,
                'username'  => $existingUser && $existingUser->username ? $existingUser->username : $usernameFromEmail,
                'google_id' => $googleId,
                'password'  => $existingUser ? $existingUser->password : Hash::make(Str::random(16)),
                'role'      => $existingUser ? $existingUser->role : 'user',
                'status'    => $existingUser ? $existingUser->status : 'aktif',
            ]
        );

        if ($user->status === 'nonaktif') {
            return response()->json(['message' => 'Akun kamu telah dinonaktifkan'], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil via Google',
            'token'   => $token,
            'user'    => $user,
        ]);
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['message' => 'Link reset password berhasil dikirim ke email kamu.'], 200);
        }
        return response()->json(['message' => 'Email tidak terdaftar.'], 400);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill(['password' => Hash::make($password)])->setRememberToken(Str::random(60));
                $user->save();
                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Password kamu berhasil diperbarui.'], 200);
        }
        return response()->json(['message' => 'Link reset tidak valid.'], 400);
    }
}