<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        return response()->json([
            "success" => true,
            "data" => User::orderBy('created_at', 'desc')->get()
        ]);
    }

    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(["success" => false, "message" => "User tidak ditemukan"], 404);
        }

        $user->delete();

        return response()->json([
            "success" => true,
            "message" => "User berhasil dihapus"
        ]);
    }
}
