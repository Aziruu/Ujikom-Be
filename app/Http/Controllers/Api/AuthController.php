<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Teacher;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // Login Function Admin
    /**
     * @brief Proses autentikasi untuk level Administrator.
     * @param \Illuminate\Http\Request $request (email, password).
     * @return \Illuminate\Http\JsonResponse Berisi token akses dan data user.
     */
    public function loginAdmin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Email atau Password salah'], 401);
        }

        $token = $user->createToken('admin_token')->plainTextToken;

        // ⬇️ INI YANG KURANG! HARUS RETURN TOKEN!
        return response()->json([
            'message' => 'Login Sukses!',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]
        ]);
    }

    /**
     * @brief Proses autentikasi untuk level Guru (menggunakan Email atau NIP).
     * @param \Illuminate\Http\Request $request (login, password).
     * @return \Illuminate\Http\JsonResponse Berisi token akses dan data guru.
     */
    public function loginGuru(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required',
        ]);

        $teacher = Teacher::where('email', $request->login)
            ->orWhere('nip', $request->login)
            ->first();

        if (!$teacher || !Hash::check($request->password, $teacher->password)) {
            return response()->json(['message' => 'Email atau Password salah'], 401);
        }

        $token = $teacher->createToken('guru_token')->plainTextToken;

        return response()->json([
            'message' => 'Login Sukses!',
            'token' => $token,
            'user' => $teacher
        ]);
    }

    /**
     * @brief Menghapus current access token (Logout).
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        // ⬇️ FIX TYPO: AccsessToken → AccessToken
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logout berhasil']);
    }
}
