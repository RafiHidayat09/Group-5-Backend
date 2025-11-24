<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PsikologProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PsikologProfileController extends Controller
{
    // GET /psikiater
    public function index()
    {
        $data = User::where('role', 'psikiater')
            ->with('psikologProfile')
            ->get();

        return response()->json($data);
    }

    // POST /psikiater
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:120',
            'email' => 'required|email|unique:users,email',
            'password' => 'required',
            'no_str' => 'required|string|max:100',
            'spesialisasi' => 'required|string|max:150',
            'pengalaman' => 'nullable|string',
            'deskripsi' => 'nullable|string',
            'foto' => 'nullable|image|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // 1. Buat user psikiater
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => 'psikiater'
        ]);

        // 2. Upload foto jika ada
        $fotoName = null;
        if ($request->hasFile('foto')) {
            $fotoName = time() . '.' . $request->foto->extension();
            $request->foto->storeAs('psikolog', $fotoName, 'public');
        }

        // 3. Buat profil psikiater
        PsikologProfile::create([
            'psikolog_id' => $user->id,
            'no_str' => $request->no_str,
            'spesialisasi' => $request->spesialisasi,
            'pengalaman' => $request->pengalaman,
            'deskripsi' => $request->deskripsi,
            'foto' => $fotoName,
        ]);

        return response()->json(['message' => 'Psikiater berhasil dibuat'], 201);
    }

    // GET /psikiater/{id}
    public function show($id)
    {
        $data = User::where('id', $id)
            ->where('role', 'psikiater')
            ->with('psikologProfile')
            ->first();

        if (!$data) {
            return response()->json(['message' => 'Psikiater tidak ditemukan'], 404);
        }

        return response()->json($data);
    }

    // PUT /psikiater/{id}
    public function update(Request $request, $id)
    {
        $profile = PsikologProfile::where('psikolog_id', $id)->first();
        $user = User::where('id', $id)->where('role', 'psikiater')->first();

        if (!$profile || !$user) {
            return response()->json(['message' => 'Psikiater tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:120',
            'no_str' => 'required|string|max:100',
            'spesialisasi' => 'required|string|max:150',
            'pengalaman' => 'nullable|string',
            'deskripsi' => 'nullable|string',
            'foto' => 'nullable|image|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Update user
        $user->update([
            'name' => $request->name,
        ]);

        // Upload foto baru jika ada
        if ($request->hasFile('foto')) {
            // hapus foto lama
            if ($profile->foto && Storage::disk('public')->exists("psikolog/{$profile->foto}")) {
                Storage::disk('public')->delete("psikolog/{$profile->foto}");
            }

            $fotoName = time() . '.' . $request->foto->extension();
            $request->foto->storeAs('psikolog', $fotoName, 'public');
            $profile->foto = $fotoName;
        }

        // Update profil
        $profile->update([
            'no_str' => $request->no_str,
            'spesialisasi' => $request->spesialisasi,
            'pengalaman' => $request->pengalaman,
            'deskripsi' => $request->deskripsi,
            'foto' => $profile->foto,
        ]);

        return response()->json(['message' => 'Berhasil update psikiater']);
    }

    // DELETE /psikiater/{id}
    public function destroy($id)
    {
        $user = User::where('id', $id)->where('role', 'psikiater')->first();

        if (!$user) {
            return response()->json(['message' => 'Psikiater tidak ditemukan'], 404);
        }

        // Hapus profil otomatis karena FK cascade
        $user->delete();

        return response()->json(['message' => 'Psikiater berhasil dihapus']);

    }

    // GET /psikolog-profile (profil psikiater yang sedang login)
public function me()
{
    $user = auth()->user();

    // Ambil profil
    $profile = PsikologProfile::where('psikolog_id', $user->id)->first();

    // Jika profil belum dibuat (psikiater hasil create)
    if (!$profile) {
        return response()->json([
            "message" => "Profil belum dibuat",
            "user" => $user,
            "profile" => null
        ], 404);
    }

    return response()->json([
        "user" => $user,
        "profile" => $profile
    ]);
}
}

