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
        Schema::create('sms_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number');
            $table->enum('message_type', ['incoming', 'outgoing']);
            $table->text('content');
            $table->text('ai_response')->nullable();
            $table->boolean('processed')->default(false);
            $table->string('twilio_sid')->nullable();
            $table->integer('response_time_ms')->nullable(); // AI processing time
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            
            $table->index(['phone_number', 'created_at']);
            $table->index(['processed', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_conversations');
    }
};
