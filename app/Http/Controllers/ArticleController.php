<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ArticleController extends Controller
{
    // GET ALL
    public function index()
    {
        return response()->json([
            "success" => true,
            "message" => "Get All Articles",
            "data" => Article::with('penulis')->latest()->get()
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

        // handle upload gambar
        $gambarName = null;
        if ($request->hasFile('gambar')) {
            $gambarName = time() . '.' . $request->gambar->extension();
            $request->gambar->storeAs('public/articles', $gambarName);
        }

        $article = Article::create([
            'judul' => $request->judul,
            'konten' => $request->konten,
            'kategori' => $request->kategori,
            'penulis_id' => $request->penulis_id,
            'tanggal' => now(),
            'gambar' => $gambarName
        ]);

        return response()->json(['success'=>true,'data'=>$article], 201);
    }

    // DETAIL
    public function show(string $id)
    {
        $article = Article::with('penulis')->find($id);

        if (!$article) {
            return response()->json(['success' => false, 'message' => 'Not Found'], 404);
        }

        return response()->json(['success'=>true, 'data'=>$article], 200);
    }

    // UPDATE
    public function update(Request $request, string $id)
    {
        $article = Article::find($id);

        if (!$article) {
            return response()->json(['success'=>false,'message'=>'Not Found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'judul' => 'required|string|max:150',
            'konten' => 'required|string',
            'penulis_id' => 'required|exists:users,id',
            'kategori' => 'nullable|string|max:50',
            'gambar' => 'nullable|image|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json(['success'=>false,'message'=>$validator->errors()], 422);
        }

        // upload gambar baru kalau ada
        if ($request->hasFile('gambar')) {
            $gambarName = time() . '.' . $request->gambar->extension();
            $request->gambar->storeAs('public/articles', $gambarName);
            $article->gambar = $gambarName;
        }

        $article->update($request->only(['judul','konten','penulis_id','kategori']));

        $article->save();

        return response()->json(['success'=>true,'data'=>$article], 200);
    }

    // DELETE
    public function destroy($id)
    {
        $article = Article::find($id);

        if (!$article) {
            return response()->json(['success'=>false,'message'=>'Not Found'], 404);
        }

        $article->delete();

        return response()->json(['success'=>true,'message'=>'Deleted'], 200);
    }
}
