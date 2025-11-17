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
        Schema::create('transaction_summarize', function (Blueprint $table) {
            $table->id();

            /** Number */
            $table->decimal('whole_total_sales',19,4)->default(0);
            $table->decimal('ppn_total_sales',19,4)->default(0);
            $table->decimal('total_sales',19,4)->default(0);
            $table->decimal('retail_total_sales',19,4)->default(0);
            $table->decimal('debit_total_amount',19,4)->default(0);
            $table->decimal('transfer_total_amount',19,4)->default(0);
            $table->decimal('down_payment_total',19,4)->default(0);
            $table->decimal('leasing_down_payment_total',19,4)->default(0);
            $table->decimal('leasing_fee',19,4)->default(0);
            $table->decimal('receiveable_total',19,4)->default(0);
            $table->decimal('leasing_receiveable_total',19,4)->default(0);
            $table->decimal('online_total',19,4)->default(0);
            $table->decimal('dealer_total',19,4)->default(0);
            $table->decimal('showcase_total',19,4)->default(0);
            $table->decimal('receiveable_left',19,4)->default(0);
            $table->decimal('leasing_receiveable_left',19,4)->default(0);
            $table->decimal('tax_total',19,4)->default(0);
            $table->decimal('internal_fee_total',19,4)->default(0);

            /** Enums */

            /** foreign key */
            $table->foreignId('cashier_shift_id');
            $table->foreign('cashier_shift_id')->references('id')->on('cashier_shifts');
            $table->softDeletes();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_summarize');
    }
};
