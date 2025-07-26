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
        Schema::create('monitor_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitor_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['up', 'down', 'warning']);
            $table->integer('response_time')->nullable(); // milliseconds
            $table->integer('status_code')->nullable();
            $table->text('error_message')->nullable();
            $table->text('ssl_info')->nullable(); // JSON data about SSL certificate
            $table->timestamp('checked_at');
            $table->timestamps();
            
            $table->index(['monitor_id', 'checked_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitor_results');
    }
};
