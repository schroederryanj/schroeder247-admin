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
        Schema::create('zabbix_hosts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('zabbix_host_id')->unique();
            $table->string('name');
            $table->string('host');
            $table->json('interfaces')->nullable();
            $table->string('status', 20)->default('unknown');
            $table->json('groups')->nullable();
            $table->boolean('monitored')->default(true);
            $table->boolean('sms_notifications')->default(false);
            $table->string('notification_phone')->nullable();
            $table->boolean('email_notifications')->default(false);
            $table->string('notification_email')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zabbix_hosts');
    }
};
