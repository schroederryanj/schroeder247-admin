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
        Schema::create('monitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('url');
            $table->enum('type', ['http', 'https', 'ping', 'port']);
            $table->integer('check_interval')->default(300); // seconds
            $table->integer('expected_status_code')->default(200);
            $table->integer('timeout')->default(30); // seconds
            $table->integer('port')->nullable();
            $table->text('expected_content')->nullable();
            $table->boolean('ssl_check')->default(false);
            $table->boolean('enabled')->default(true);
            $table->timestamp('last_checked_at')->nullable();
            $table->string('current_status', 20)->nullable()->default('unknown');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitors');
    }
};
