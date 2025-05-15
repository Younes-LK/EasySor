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
        Schema::create('repair_equipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_id')->constrained('repairs');
            $table->foreignId('equipment_id')->constrained('equipments');
            $table->integer('quantity');
            $table->integer('unit_price');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repair_equipments');
    }
};
