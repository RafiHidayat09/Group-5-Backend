<?php

use App\Models\QuizResult;
use Illuminate\Http\Request;

class QuizResultController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'stress' => 'required',
            'kecemasan' => 'required',
            'depresi' => 'required',
            'burnout' => 'required',
            'kualitas_tidur' => 'required',
            'ai_tips' => 'nullable|array'
        ]);

        $data['user_id'] = auth()->id();

        $result = QuizResult::create($data);

        return response()->json([
            'message' => 'Quiz berhasil disimpan',
            'data' => $result
        ]);
    }

    // psikiater melihat semua assesment user
    public function index()
    {
        return QuizResult::with('user')->latest()->get();
    }

    public function userResults()
    {
        try {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'User tidak ditemukan'], 401);
        }

        $results = QuizResult::where('user_id', $user->id)
            ->latest()
            ->get();

        return response()->json($results);
    } catch (\Throwable $e) {
        return response()->json([
            'message' => 'Terjadi error di server',
            'error' => $e->getMessage()
        ], 500);
    }
    }
}
