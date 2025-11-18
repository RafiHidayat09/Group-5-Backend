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
        Schema::create('psikolog_profiles', function (Blueprint $table) {

            $table->unsignedBigInteger('psikolog_id')->primary(); // FK ke users
            $table->string('no_str', 50);
            $table->string('spesialisasi', 100);
            $table->text('pengalaman')->nullable();
            $table->text('deskripsi')->nullable();
            $table->string('foto', 255)->nullable();
            $table->timestamps();

            $table->foreign('psikolog_id')->references('id')->on('users')->onDelete('cascade');
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('psikolog_profiles');
    }
};
