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
        Schema::create('zabbix_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zabbix_host_id')->constrained()->onDelete('cascade');
            $table->string('zabbix_event_id')->unique();
            $table->string('trigger_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('severity', ['not_classified', 'information', 'warning', 'average', 'high', 'disaster']);
            $table->enum('status', ['problem', 'ok']);
            $table->enum('value', ['ok', 'problem']);
            $table->timestamp('event_time');
            $table->timestamp('recovery_time')->nullable();
            $table->boolean('acknowledged')->default(false);
            $table->json('raw_data')->nullable();
            $table->boolean('notification_sent')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zabbix_events');
    }
};
