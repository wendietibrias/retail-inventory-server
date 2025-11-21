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
        Schema::create('sales_invoice_details_log', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sales_invoice_detail_id');
            $table->foreign('sales_invoice_detail_id')->references('id')->on('sales_invoice_details');
            $table->foreignId('actor_id');
            $table->foreign('actor_id')->references('id')->on('users');

            $table->string('action');

            $table->string('old_sales_invoice_detail');
            $table->string('new_sales_invoice_detail');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_invoice_details_log');
    }
};
