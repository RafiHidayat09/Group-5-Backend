<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Psychologist;
use Illuminate\Http\Request;

class PsychologistController extends Controller
{
    /**
     * Get all psychologists with filtering
     */
    public function index(Request $request)
    {
        try {
            // 1. Eager Loading Relasi 'user' (agar hemat query)
            $query = Psychologist::with('user')->where('is_available', true);

            // 2. Filter by status (online/offline)
            if ($request->has('status') && in_array($request->status, ['online', 'offline'])) {
                $query->where('status', $request->status);
            }

            // 3. Mapping Data (Flattening)
            // Menggabungkan data dari tabel 'psychologists' dan 'users'
            $psychologists = $query->get()->map(function($psy) {
                return [
                    // ID User digunakan untuk Chat/Konsultasi
                    'id' => $psy->user_id,
                    'psychologist_profile_id' => $psy->id,

                    // Data dari Tabel Users
                    'name' => $psy->user ? $psy->user->name : 'Tanpa Nama',
                    'avatar' => $psy->user && $psy->user->avatar
                    ? asset('storage/' . $psy->user->avatar)
                    : null,
                    'email' => $psy->user ? $psy->user->email : null,

                    // Data dari Tabel Psychologists
                    'specialization' => $psy->specialization,
                    'status' => $psy->status,
                    'rating' => $psy->rating,
                    'review_count' => $psy->review_count,
                    'fee' => $psy->fee,
                    'bio' => $psy->bio,
                    'education' => $psy->education,
                    'experience' => $psy->experience,
                    'specializations' => $psy->specializations,

                    'is_favorite' => false
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $psychologists,
                'count' => $psychologists->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data psikiater',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available psychologists (online only) for Chat Sidebar
     */
    public function available()
    {
        try {
            // Ambil yang statusnya 'online' dan available
            $psychologists = Psychologist::with('user')
                ->where('is_available', true)
                ->where('status', 'online') // Opsional: Jika ingin menampilkan offline juga di sidebar, hapus baris ini
                ->get()
                ->map(function($psy) {
                    return [
                        'id' => $psy->user_id, // User ID penting untuk chat
                        'name' => $psy->user->name,
                        'avatar' => $psy->user && $psy->user->avatar
                        ? asset('storage/' . $psy->user->avatar)
                        : null,
                        'specialization' => $psy->specialization,
                        'status' => $psy->status,
                        'rating' => $psy->rating,
                        'review_count' => $psy->review_count,
                        'is_favorite' => false,
                        'unseen_count' => 0,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $psychologists,
                'count' => $psychologists->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil psikiater tersedia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get psychologist detail by USER ID
     * PENTING: Parameter $id disini adalah USER ID, bukan Profile ID
     */
    public function show($id)
    {
        try {
            // Cari profil psikolog berdasarkan user_id
            $psy = Psychologist::with('user')->where('user_id', $id)->firstOrFail();

            $data = [
                'id' => $psy->user_id,
                'name' => $psy->user->name,
                'avatar' => $psy->user && $psy->user->avatar
                    ? asset('storage/' . $psy->user->avatar)
                    : null,
                'email' => $psy->user->email,

                'specialization' => $psy->specialization,
                'status' => $psy->status,
                'rating' => $psy->rating,
                'review_count' => $psy->review_count,
                'fee' => $psy->fee,
                'bio' => $psy->bio,
                'education' => $psy->education,
                'experience' => $psy->experience,
                'specializations' => $psy->specializations,
                'is_available' => $psy->is_available
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Psikiater tidak ditemukan',
                'error' => $e->getMessage()
            ], 404);
        }
    }
}
