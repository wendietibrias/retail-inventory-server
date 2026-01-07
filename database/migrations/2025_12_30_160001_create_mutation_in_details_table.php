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
        Schema::create('mutation_in_details', function (Blueprint $table) {
            $table->id();

            $table->integer('qty')->default(0);

            $table->foreignId('product_sku_id');
            $table->foreign('product_sku_id')->references('id')->on('product_skus');

            $table->foreignId('mutation_in_id');
            $table->foreign('mutation_in_id')->references('id')->on('mutation_in');

            $table->softDeletes();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mutation_in_details');
    }
};
