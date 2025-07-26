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
            $table->boolean('sms_notifications')->default(false)->after('enabled');
            $table->string('notification_phone', 20)->nullable()->after('sms_notifications');
            $table->boolean('email_notifications')->default(false)->after('notification_phone');
            $table->string('notification_email')->nullable()->after('email_notifications');
            $table->integer('notification_threshold')->default(1)->after('notification_email');
            $table->timestamp('last_notification_sent')->nullable()->after('notification_threshold');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitors', function (Blueprint $table) {
            $table->dropColumn([
                'sms_notifications',
                'notification_phone',
                'email_notifications', 
                'notification_email',
                'notification_threshold',
                'last_notification_sent'
            ]);
        });
    }
};
