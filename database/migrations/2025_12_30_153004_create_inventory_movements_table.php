<?php

use App\Enums\StockMovementOriginEnum;
use App\Enums\StockMovementTypeEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();

            $table->foreign('product_sku_id')->references('id')->on('product_skus');
            $table->foreignId('product_sku_id');

            $table->integer('before_qty')->default(0);
            $table->integer('after_qty')->default(0);
            $table->integer('usage_qty')->default(0);

            $table->string('reference');
            $table->enum('type', StockMovementTypeEnum::cases());
            $table->enum('origin',StockMovementOriginEnum::cases());

            $table->foreignId('warehouse_id');
            $table->foreign('warehouse_id')->references('id')->on('warehouses');

            $table->softDeletes();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
