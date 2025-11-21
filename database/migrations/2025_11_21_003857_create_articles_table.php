<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id('article_id');
            $table->string('judul', 150);
            $table->text('konten');
            $table->unsignedBigInteger('penulis_id');
            $table->string('kategori', 50)->nullable();
            $table->dateTime('tanggal')->default(now());

            // gambar artikel
            $table->string('gambar')->nullable();

            $table->timestamps();

            $table->foreign('penulis_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
