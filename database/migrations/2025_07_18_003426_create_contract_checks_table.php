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
        Schema::create('contract_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->onDelete('cascade');
            $table->date('date');
            $table->string('bank_name');
            $table->string('serial_number');
            $table->string('sayadi_number'); // شماره صیادی
            $table->string('in_name_of'); // در وجه
            $table->integer('amount'); // Add amount field
            $table->enum('status', ['draft', 'cashed', 'bounced', 'referred'])->default('draft'); // پیش نویس, پاس شده, برگشت خورده, ارجاع شده
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_checks');
    }
};
