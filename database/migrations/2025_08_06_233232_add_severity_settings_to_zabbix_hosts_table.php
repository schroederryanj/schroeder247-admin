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
            $table->json('severity_settings')->nullable()->after('notification_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('zabbix_hosts', function (Blueprint $table) {
            $table->dropColumn('severity_settings');
        });
    }
};
