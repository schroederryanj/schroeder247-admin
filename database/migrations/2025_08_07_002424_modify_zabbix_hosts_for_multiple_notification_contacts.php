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
        Schema::table('zabbix_hosts', function (Blueprint $table) {
            // Add multiple notification contact fields
            $table->json('notification_phones')->nullable()->after('notification_phone');
            $table->json('notification_emails')->nullable()->after('notification_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('zabbix_hosts', function (Blueprint $table) {
            $table->dropColumn(['notification_phones', 'notification_emails']);
        });
    }
};
