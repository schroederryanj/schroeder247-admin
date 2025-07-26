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
        Schema::create('ai_contexts', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number')->unique();
            $table->json('context_data')->nullable(); // Conversation history, preferences, etc.
            $table->json('preferences')->nullable(); // User-specific settings
            $table->timestamp('last_interaction');
            $table->integer('total_messages')->default(0);
            $table->boolean('is_admin')->default(false); // Admin users get more capabilities
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_contexts');
    }
};
