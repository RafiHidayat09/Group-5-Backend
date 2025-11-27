<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ArticleController extends Controller
{
    // GET ALL
    public function index()
    {
        // Mengambil artikel beserta data penulisnya
        $articles = Article::with('penulis')->latest()->get();

        if ($articles->isEmpty()) {
            return response()->json([
                "success" => true,
                "message" => "No Articles Found",
                "data" => []
            ], 200);
        }

        // Tambahkan URL gambar lengkap
        $articles->transform(function ($article) {
            $article->gambar_url = $article->gambar
                ? asset('storage/articles/' . $article->gambar)
                : null;
            return $article;
        });

        return response()->json([
            "success" => true,
            "message" => "Get All Articles",
            "data" => $articles
        ], 200);
    }

    // CREATE
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'judul' => 'required|string|max:150',
            'konten' => 'required|string',
            'penulis_id' => 'required|exists:users,id',
            'kategori' => 'nullable|string|max:50',
            'gambar' => 'nullable|image|mimes:jpeg,jpg,png|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()
            ], 422);
        }

        $gambarName = null;
        if ($request->hasFile('gambar')) {
            $image = $request->file('gambar');
            // Simpan ke folder storage/app/public/articles
            $image->store('articles', 'public');
            $gambarName = $image->hashName();
        }

        $article = Article::create([
            'judul' => $request->judul,
            'konten' => $request->konten,
            'kategori' => $request->kategori,
            'penulis_id' => $request->penulis_id,
            'tanggal' => now(),
            'gambar' => $gambarName
        ]);

        $article->gambar_url = $gambarName ? asset('storage/articles/' . $gambarName) : null;

        return response()->json([
            'success' => true,
            'message' => 'Article created successfully!',
            'data' => $article
        ], 201);
    }

    // SHOW DETAIL
    public function show(string $id)
    {
        $article = Article::with('penulis')->find($id);

        if (!$article) {
            return response()->json([
                'success' => false,
                'message' => 'Article Not Found'
            ], 404);
        }

        $article->gambar_url = $article->gambar
            ? asset('storage/articles/' . $article->gambar)
            : null;

        return response()->json([
            'success' => true,
            'data' => $article
        ], 200);
    }

    // UPDATE
    public function update(Request $request, string $id)
    {
        $article = Article::find($id);

        if (!$article) {
            return response()->json([
                'success' => false,
                'message' => 'Article Not Found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'judul' => 'required|string|max:150',
            'konten' => 'required|string',
            'penulis_id' => 'required|exists:users,id',
            'kategori' => 'nullable|string|max:50',
            'gambar' => 'nullable|image|mimes:jpeg,jpg,png|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()
            ], 422);
        }

        $data = $request->only(['judul', 'konten', 'kategori', 'penulis_id']);

        // Upload gambar baru & hapus lama jika ada
        if ($request->hasFile('gambar')) {
            $image = $request->file('gambar');
            $image->store('articles', 'public');

            if ($article->gambar) {
                // Hapus gambar lama agar storage tidak penuh
                Storage::disk('public')->delete('articles/' . $article->gambar);
            }

            $data['gambar'] = $image->hashName();
        }

        $article->update($data);
        $article->gambar_url = $article->gambar ? asset('storage/articles/' . $article->gambar) : null;

        return response()->json([
            'success' => true,
            'message' => 'Article updated successfully!',
            'data' => $article
        ], 200);
    }

    // DELETE
    public function destroy(string $id)
    {
        $article = Article::find($id);

        if (!$article) {
            return response()->json([
                'success' => false,
                'message' => 'Article Not Found'
            ], 404);
        }

        if ($article->gambar) {
            Storage::disk('public')->delete('articles/' . $article->gambar);
        }

        $article->delete();

        return response()->json([
            'success' => true,
            'message' => 'Article deleted successfully!'
        ], 200);
    }
}
