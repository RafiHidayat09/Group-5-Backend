<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function stats()
{
    // Hitung user berdasarkan role
    $roleCount = User::select('role', DB::raw('count(*) as total'))
        ->groupBy('role')
        ->get();

    // Hitung user bergabung per bulan
    $monthly = User::selectRaw("DATE_FORMAT(created_at, '%Y-%m') as bulan, COUNT(*) as total")
        ->groupBy('bulan')
        ->orderBy('bulan', 'ASC')
        ->get();

    return response()->json([
        'roles' => $roleCount,
        'monthly' => $monthly,
    ]);
}

}
