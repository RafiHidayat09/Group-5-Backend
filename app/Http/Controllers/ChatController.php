<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\User;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    // Kirim pesan
    public function send(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message' => 'required|string',
        ]);

        $chat = Chat::create([
            'sender_id' => $request->user()->id, // otomatis dari user login
            'receiver_id' => $request->receiver_id,
            'message' => $request->message,
        ]);

        return response()->json(['data' => $chat], 201);
    }

    // Ambil chat antara psikiater dan user
    public function room($userId)
    {
        $psikiaterId = auth()->id();

        $messages = Chat::where(function($q) use ($psikiaterId, $userId) {
            $q->where('sender_id', $psikiaterId)->where('receiver_id', $userId);
        })->orWhere(function($q) use ($psikiaterId, $userId) {
            $q->where('sender_id', $userId)->where('receiver_id', $psikiaterId);
        })->orderBy('created_at', 'asc')->get();

        return response()->json(['data' => $messages]);
    }

    // Ambil daftar user yang pernah chat dengan psikiater
    public function psikiaterUsers()
    {
        $psikiaterId = auth()->id();

        $userIds = Chat::where('sender_id', $psikiaterId)
            ->orWhere('receiver_id', $psikiaterId)
            ->get()
            ->flatMap(fn($chat) => [$chat->sender_id, $chat->receiver_id])
            ->unique()
            ->filter(fn($id) => $id != $psikiaterId);

        $users = User::whereIn('id', $userIds)->where('role', 'user')->get(['id', 'name', 'email']);

        return response()->json(['data' => $users]);
    }
}
