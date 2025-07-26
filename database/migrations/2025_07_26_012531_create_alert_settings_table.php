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
        Schema::create('alert_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitor_id')->constrained()->onDelete('cascade');
            $table->enum('alert_type', ['sms', 'email', 'webhook']);
            $table->string('phone_number')->nullable();
            $table->string('email')->nullable();
            $table->string('webhook_url')->nullable();
            $table->integer('threshold_minutes')->default(5); // minutes before alerting
            $table->integer('cooldown_minutes')->default(5); // minutes between alerts
            $table->boolean('alert_on_recovery')->default(true);
            $table->boolean('enabled')->default(true);
            $table->timestamp('last_alert_sent_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alert_settings');
    }
};
