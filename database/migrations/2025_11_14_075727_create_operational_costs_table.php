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
        Schema::create('operational_costs', function (Blueprint $table) {
            $table->id();

            $table->dateTimeTz('date');

            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('cost_fee',19,4)->default(0);

            $table->foreignId('cashier_shift_detail_id');
            $table->foreign('cashier_shift_detail_id')->references('id')->on('cashier_shift_details');

            $table->foreignId('created_by_id');
            $table->foreign('created_by_id')->references('id')->on('users');
            $table->foreignId('updated_by_id');
            $table->foreign('updated_by_id')->references('id')->on('users');
            $table->softDeletes();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operational_costs');
    }
};
