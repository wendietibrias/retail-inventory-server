<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_adjustment_details', function (Blueprint $table) {
            $table->id();
            
            $table->integer('qty')->default(0);

            $table->foreignId('product_sku_id');
            $table->foreign('product_sku_id')->references('id')->on('product_skus');

            $table->foreignId('stock_adjustment_id');
            $table->foreign('stock_adjustment_id')->references('id')->on('stock_adjustments');


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_adjustment_details');
    }
};
