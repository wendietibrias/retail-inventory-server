<?php

use App\Enums\SalesInvoiceDetailProductTypeEnum;
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
        Schema::create('sales_invoice_details', function (Blueprint $table) {
            $table->id();

            $table->string('product_code')->nullable();
            $table->string('product_name')->nullable();
            $table->text('description')->nullable();
            $table->enum('product_type',SalesInvoiceDetailProductTypeEnum::cases());

            /** foreign key */
            $table->foreignId('sales_invoice_id');
            $table->foreign('sales_invoice_id')->references('id')->on('sales_invoices');


            /** Number */
            $table->integer('qty')->default(0);
            $table->decimal('product_price',19,4)->default(0);
            $table->decimal('sub_total',19,4)->default(0);
            $table->decimal('discount',19,4)->default(0);
            $table->softDeletes();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_invoice_details');
    }
};
