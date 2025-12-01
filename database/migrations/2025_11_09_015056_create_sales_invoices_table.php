<?php

use App\Enums\SalesInvoicePriceTypeEnum;
use App\Enums\SalesInvoiceStatusEnum;
use App\Enums\SalesInvoiceTypeEnum;
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
        Schema::create('sales_invoices', function (Blueprint $table) {
            $table->id();
            
            $table->string('code')->index()->unique(); 
            $table->string('other_code')->unique();
            $table->string('file_path')->nullable();

            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->text('address')->nullable();
            $table->string('warehouse')->nullable();
            $table->string('sales_person_name')->nullable();
            $table->text('description')->nullable();
            $table->text('receiveable_approval_note')->nullable();
            $table->text('void_note')->nullable();
            
            $table->dateTimeTz('date');

            /** foreign key */
            $table->foreignId('created_by_id');
            $table->foreign('created_by_id')->references('id')->on('users');
            $table->foreignId('updated_by_id')->nullable();
            $table->foreign('updated_by_id')->references('id')->on('users');
            $table->foreignId('void_by_id')->nullable();
            $table->foreign('void_by_id')->references('id')->on('users');


            $table->boolean('is_in_paid')->default(false);

            /** enums */    
            $table->enum('status',SalesInvoiceStatusEnum::cases());
            $table->enum('type', SalesInvoiceTypeEnum::cases());
            $table->enum('price_type', SalesInvoicePriceTypeEnum::cases())->nullable();

            /** Number */
            $table->decimal('sub_total',19,4)->default(0);
            $table->decimal('discount',19,4)->default(0);
            $table->decimal('grand_total',19,4)->default(0);
            $table->integer('tax')->default(0);
            $table->decimal('tax_value',19,4)->default(0);
            $table->decimal('other_fee',19,4)->default(0);

            $table->decimal('paid_amount',19,4)->default(0);
            $table->decimal('grand_total_left',19,4)->default(0);

            $table->softDeletes();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_invoices');
    }
};
