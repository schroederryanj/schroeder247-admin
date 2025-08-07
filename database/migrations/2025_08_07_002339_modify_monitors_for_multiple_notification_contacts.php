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
            // Replace single notification fields with arrays
            $table->json('notification_phones')->nullable()->after('notification_phone');
            $table->json('notification_emails')->nullable()->after('notification_email');
            
            // Keep old fields for now to migrate data
            // Will remove in down() method
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitors', function (Blueprint $table) {
            $table->dropColumn(['notification_phones', 'notification_emails']);
        });
    }
};
