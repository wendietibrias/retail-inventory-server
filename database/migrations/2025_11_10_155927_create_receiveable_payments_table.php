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
        Schema::create('receiveable_payments', function (Blueprint $table) {
            $table->id();

            $table->text('description')->nullable();
            $table->dateTimeTz('paid_date')->nullable();

            $table->decimal('paid_amount',19,4)->default(0);
            
            /** foreign key */
            $table->foreignId('receiveable_id');
            $table->foreign('receiveable_id')->references('id')->on('receiveables');
            $table->foreignId('pm_detail_id');
            $table->foreign('pm_detail_id')->references('id')->on('payment_method_details');
            $table->foreignId('created_by_id');
            $table->foreign('created_by_id')->references('id')->on('users');
            $table->foreignId('updated_by_id')->nullable();
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
        Schema::dropIfExists('receiveable_payments');
    }
};
