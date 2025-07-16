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
        Schema::table('equipments', function (Blueprint $table) {
            // Adds the new 'unit' enum column after 'stock_quantity'
            $table->enum('unit', ['-', 'عدد', 'متر', 'کلاف'])
                  ->after('stock_quantity')
                  ->default('-');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipments', function (Blueprint $table) {
            $table->dropColumn('unit');
        });
    }
};
