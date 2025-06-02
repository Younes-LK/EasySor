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
        Schema::create('maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('customer_address_id')->constrained('customer_addresses');
            $table->foreignId('assigned_to')->constrained('users')->nullable();
            $table->date('start_date');
            $table->integer('duration_in_months');
            $table->integer('monthly_price');
            $table->integer('total_price');
            $table->integer('paid_amount')->default(0);
            $table->integer('completed_count')->default(0);
            $table->date('last_completed_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenances');
    }
};
