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
        Schema::table('monitors', function (Blueprint $table) {
            $table->json('tags')->nullable()->after('sms_notifications');
        });

        Schema::table('zabbix_hosts', function (Blueprint $table) {
            $table->json('tags')->nullable()->after('last_synced_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitors', function (Blueprint $table) {
            $table->dropColumn('tags');
        });

        Schema::table('zabbix_hosts', function (Blueprint $table) {
            $table->dropColumn('tags');
        });
    }
};
