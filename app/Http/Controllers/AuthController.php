<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * REGISTER
     * Semua pendaftar otomatis menjadi user biasa
     */
    public function register(Request $request)
    {
        // 1️⃣ Validasi input
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100|unique:users',
            'password' => 'required|min:8'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // 2️⃣ Buat user baru dengan default role 'user'
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => 'user' // default role
        ]);

        // 3️⃣ Buat token JWT untuk user baru
        $token = JWTAuth::fromUser($user);

        return response()->json([
            'success' => true,
            'message' => 'Registrasi berhasil!',
            'user' => $user,
            'token' => $token
        ], 201);
    }

    /**
     * LOGIN
     * Semua role bisa login
     */
    public function login(Request $request)
    {
        // 1️⃣ Validasi input
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // 2️⃣ Ambil kredensial
        $credentials = $request->only('email', 'password');

        // 3️⃣ Coba login
        if (!$token = auth()->guard('api')->attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah!'
            ], 401);
        }

        // 4️⃣ Ambil data user login
        $user = auth()->guard('api')->user();

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil!',
            'user' => $user,
            'token' => $token
        ], 200);
    }

    /**
     * LOGOUT
     */
    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json([
                'success' => true,
                'message' => 'Logout berhasil!'
            ], 200);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Logout gagal!'
            ], 500);
        }
    }

    /**
     * PROFILE (ambil data user dari token)
     */
    public function profile()
    {
        return response()->json([
            'success' => true,
            'user' => auth()->guard('api')->user()
        ], 200);
    }

    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * HANDLE GOOGLE CALLBACK
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            // Cari user berdasarkan email atau buat baru
            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'password' => Hash::make(Str::random(24)),
                    'google_id' => $googleUser->getId(),
                    'role' => 'user',
                    'email_verified_at' => now(),
                ]);
            } else {
                $user->update(['google_id' => $googleUser->getId()]);
            }

            $token = JWTAuth::fromUser($user);

            $frontendURL = env('FRONTEND_URL', 'http://localhost:5173');

            return redirect("{$frontendURL}/auth/callback?token={$token}&success=true&user=" . base64_encode(json_encode([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role
            ])));

        } catch (\Exception $e) {
            $frontendURL = env('FRONTEND_URL', 'http://localhost:5173');
            return redirect("{$frontendURL}/auth/callback?success=false&error=" . urlencode('Google login failed: ' . $e->getMessage()));
        }
    }
}
