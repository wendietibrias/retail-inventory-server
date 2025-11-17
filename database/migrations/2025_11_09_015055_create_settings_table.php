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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('tax_invoice_code');
            $table->string('no_tax_invoice_code');
            $table->dateTimeTz('morning_shift_time');
            $table->dateTimeTz('night_shift_time');
            $table->softDeletes();
            $table->integer('tax')->default(12); //based on government regulation and rules
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
