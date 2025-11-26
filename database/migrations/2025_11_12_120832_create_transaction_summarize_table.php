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
        Schema::create('transaction_summarize', function (Blueprint $table) {
            $table->id();

            /** Number */
            $table->decimal('whole_total', 19, 4)->default(0);

            $table->decimal('ppn_total', 19, 4)->default(0);
            $table->decimal('non_ppn_total', 19, 4)->default(0);


            $table->decimal('other_paid_total', 19, 4)->default(0);

            $table->decimal('debit_total', 19, 4)->default(0);
            $table->decimal('debit_leasing_total', 19, 4)->default(0);

            $table->decimal('big_item_total', 19, 4)->default(0);
            $table->decimal('leasing_item_total', 19, 4)->default(0);
            $table->decimal('item_total', 19,4)->default(0);

            $table->decimal('transfer_total', 19, 4)->default(0);
            $table->decimal('transfer_leasing_total', 19, 4)->default(0);

            $table->decimal('qr_total', 19, 4)->default(0);
            $table->decimal('qr_leasing_total', 19, 4)->default(0);

            $table->decimal('cash_total', 19, 4)->default(0);
            $table->decimal('leasing_cash_total', 19, 4)->default(0);

            $table->decimal('down_payment_total', 19, 4)->default(0);
            $table->decimal('leasing_down_payment_total', 19, 4)->default(0);

            $table->decimal('leasing_fee', 19, 4)->default(0);

            $table->decimal('receiveable_total', 19, 4)->default(0);
            $table->decimal('previous_receiveable_total', 19, 4)->default(0);
            $table->decimal('receiveable_paid_total', 19, 4)->default(0);

            $table->decimal('leasing_receiveable_total_paid', 19, 4)->default(0);
            $table->decimal('previous_leasing_receiveable_total', 19, 4)->default(0);
            $table->decimal('leasing_receiveable_total', 19, 4)->default(0);

            $table->decimal('payable_total',19,4)->default(0);

            $table->decimal('void_total', 19, 4)->default(0);
            $table->decimal('retail_total', 19, 4)->default(0);
            $table->decimal('online_total', 19, 4)->default(0);
            $table->decimal('dealer_total', 19, 4)->default(0);
            $table->decimal('showcase_total', 19, 4)->default(0);

            $table->decimal('leasing_fee_total', 19,4)->default(0);
            $table->decimal('tax_total', 19, 4)->default(0);
            $table->decimal('internal_fee_total', 19, 4)->default(0);

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
