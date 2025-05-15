<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('maintenances_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_service_id')->constrained('maintenances');
            $table->foreignId('performed_by')->constrained('users');
            $table->timestamp('performed_at');
            $table->boolean('sms_sent')->default(false);
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenances_logs');
    }
};
