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
        Schema::create('cashier_shifts', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique();
            $table->text('description')->nullable();

            $table->foreignId('created_by_id');
            $table->foreign('created_by_id')->references('id')->on('users');

            /** Number */
            $table->decimal('whole_total_sales',19,4)->default(0);
            $table->decimal('total_cash_in_box',19,4)->default(0);
            $table->decimal('total_cash_drawer',19,4)->default(0);
            $table->decimal('total_difference',19,4)->default(0);
            $table->decimal('final_cash',19,4)->default(0);
            $table->softDeletes();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cashier_shifts');
    }
};
