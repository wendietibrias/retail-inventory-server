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
        Schema::create('outbound_details', function (Blueprint $table) {
            $table->id();

            $table->foreign('product_sku_id')->references('id')->on('product_skus');
            $table->foreignId('product_sku_id');

            $table->foreignId('outbound_id');
            $table->foreign('outbound_id')->references('id')->on('outbounds');

            $table->integer('qty')->default(0);
            $table->decimal('price', 19, 2)->default(0);
            $table->decimal('discount_flat', 19, 2)->default(0);
            $table->decimal('sub_total', 19, 2)->default(0);
            $table->softDeletes();


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outbound_details');
    }
};
