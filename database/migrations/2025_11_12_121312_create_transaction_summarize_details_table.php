<?php

use App\Enums\SalesInvoiceTypeEnum;
use App\Enums\ShiftTypeEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transaction_summarize_details', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->text('description')->nullable();

            /** Foreign key */
            $table->foreignId('ts_id');
            $table->foreign('ts_id')->references('id')->on('transaction_summarize');
            $table->foreignId('cs_detail_id')->nullable();
            $table->foreign('cs_detail_id')->references('id')->on('cashier_shift_details');

            /** Enum */
            $table->enum('invoice_type', SalesInvoiceTypeEnum::cases());
            $table->enum('shift_type', ShiftTypeEnum::cases());

            /** number */
            $table->decimal('whole_total', 19, 4)->default(0);

            $table->decimal('total_ppn', 19, 4)->default(0);
            $table->decimal('non_ppn_total', 19, 4)->default(0);
            $table->decimal('leasing_total', 19, 4)->default(0);
            $table->decimal('void_total', 19, 4)->default(0);

            $table->decimal('other_paid_total', 19, 4)->default(0);

            $table->decimal('big_item_total', 19, 4)->default(0);
            $table->decimal('leasing_item_total', 19, 4)->default(0);
            $table->decimal('item_total', 19, 4)->default(0);

            $table->decimal('down_payment_total', 19, 4)->default(0);
            $table->decimal('leasing_down_payment_total', 19, 4)->default(0);
            $table->decimal('previous_down_payment_total', 19, 4, )->default(0);
            $table->decimal('leasing_previous_down_payment_total', 19, 4)->default(0);

            $table->decimal('leasing_transfer_total', 19, 4, )->default(0);
            $table->decimal('transfer_total', 19, 4)->default(0);

            $table->decimal('debit_total', 19, 4)->default(0);
            $table->decimal('debit_leasing_total', 19, 4)->default(0);

            $table->decimal('qr_total', 19, 4)->default(0);
            $table->decimal('qr_leasing_total', 19, 4)->default(0);

            $table->decimal('cash_total', 19, 4)->default(0);
            $table->decimal('leasing_cash_total', 19, 4)->default(0);

            $table->decimal('receiveable_total', 19, 4)->default(0);
            $table->decimal('receiveable_paid', 19, 4)->default(0);
            $table->decimal('previous_receiveable', 19, 4)->default(0); // might take from previous receiveable if exists

            $table->decimal('leasing_receiveable_total', 19, 4)->default(0);
            $table->decimal('leasing_receiveable_paid', 19, 4)->default(0);
            $table->decimal('previous_leasing_receiveable', 19, 4)->default(0); // might take from previous receiveable if exists

            $table->decimal('payable_total', 19, 4)->default(0);

            $table->decimal('leasing_fee_total', 19, 4)->default(0);
            $table->decimal('internal_fee_total', 19, 4)->default(0);

            $table->decimal('tax_total', 19, 4)->default(0);

            $table->softDeletes();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_summarize_details');
    }
};
