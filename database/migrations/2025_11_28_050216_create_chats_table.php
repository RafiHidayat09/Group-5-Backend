<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chats', function (Blueprint $table) {
            $table->id();

            // Pengirim pesan (user / psikiater)
            $table->unsignedBigInteger('sender_id');

            // Penerima pesan (user / psikiater)
            $table->unsignedBigInteger('receiver_id');

            // Isi pesan
            $table->text('message');

            $table->timestamps();

            // Relasi ke tabel users
            $table->foreign('sender_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('receiver_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
};
