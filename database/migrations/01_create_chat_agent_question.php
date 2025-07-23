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
        Schema::create('chat_question_templates', function (Blueprint $table) {
            $table->id();
            $table->string('question')->index();
            $table->json('embedding');
            $table->string('function_name')->nullable(); // Tên function tương ứng
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_question_templates');
    }
};
