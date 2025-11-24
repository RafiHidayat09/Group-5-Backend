<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateChatifyMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ch_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('consultation_id')->constrained()->onDelete('cascade');
            $table->bigInteger('from_id');
            $table->string('from_type'); // 'user' or 'psychologist'
            $table->bigInteger('to_id');
            $table->string('to_type'); // 'user' or 'psychologist'
            $table->string('body', 5000)->nullable();
            $table->string('attachment')->nullable();
            $table->boolean('seen')->default(false);
            $table->timestamp('seen_at')->nullable();
            $table->timestamps();

            // Index for better performance
            $table->index(['consultation_id']);
            $table->index(['from_id', 'from_type']);
            $table->index(['to_id', 'to_type']);
            $table->index(['seen']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ch_messages');
    }
}
