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
        Schema::create('quiz_results', function (Blueprint $table) {
            $table->id();
        $table->unsignedBigInteger('user_id');

        $table->integer('stress')->default(0);
        $table->integer('kecemasan')->default(0);
        $table->integer('depresi')->default(0);
        $table->integer('burnout')->default(0);
        $table->integer('kualitas_tidur')->default(0);

        $table->json('ai_tips')->nullable();

        $table->timestamps();

        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_results');
    }
};
