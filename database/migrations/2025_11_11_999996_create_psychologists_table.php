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
        Schema::create('psychologists', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            $table->string('no_str', 50);
            $table->string('specialization');
            $table->text('bio')->nullable();
            $table->string('education')->nullable();
            $table->string('experience')->nullable();
            $table->decimal('fee', 10, 2)->default(150000);
            $table->float('rating')->default(0);
            $table->integer('review_count')->default(0);
            $table->enum('status', ['online', 'offline'])->default('offline');

            $table->json('specializations')->nullable();
            $table->boolean('is_available')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('psychologists');
    }
};
