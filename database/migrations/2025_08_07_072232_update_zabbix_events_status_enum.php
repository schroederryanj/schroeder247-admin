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
        Schema::table('zabbix_events', function (Blueprint $table) {
            // Update the status enum to include 'resolved'
            $table->enum('status', ['problem', 'ok', 'resolved'])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('zabbix_events', function (Blueprint $table) {
            // Revert back to original enum values
            $table->enum('status', ['problem', 'ok'])->change();
        });
    }
};
