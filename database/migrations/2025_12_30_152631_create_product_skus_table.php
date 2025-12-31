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
        Schema::create('product_skus', function (Blueprint $table) {
            $table->id();

            $table->string('sku_number');
            $table->string('name');
            $table->string('color')->nullable();
            $table->string('size')->nullable();
            $table->string('photo_name')->nullable();
            $table->string('photo_url')->nullable();
            $table->string('unit');

            /** foreign key */
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreignId('product_id');

            $table->decimal('price', 19,2)->default(0);

            $table->text('description')->nullable();

            $table->foreign('created_by_id')->references('id')->on('users');
            $table->foreignId('created_by_id');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_skus');
    }
};
