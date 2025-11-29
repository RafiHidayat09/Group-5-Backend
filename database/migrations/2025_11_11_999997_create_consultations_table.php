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
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('psychologist_id')->constrained('users')->onDelete('cascade'); // Reference ke users
            $table->enum('status', ['pending', 'active', 'ended', 'cancelled'])->default('pending');
            $table->decimal('fee', 10, 2);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->integer('rating')->nullable();
            $table->text('review')->nullable();
            $table->boolean('rated')->default(false);
            $table->timestamps();

            // Add indexes for better performance
            $table->index(['user_id', 'psychologist_id']);
            $table->index('status');
            $table->index('rated');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultations');
    }
};
