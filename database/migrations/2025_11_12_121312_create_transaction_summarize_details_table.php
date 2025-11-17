<?php

use App\Enums\SalesInvoicePriceTypeEnum;
use App\Enums\SalesInvoiceTypeEnum;
use App\Enums\ShiftTypeEnum;
use App\Enums\TransactionSummarizeTypeEnum;
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
        Schema::create('transaction_summarize_details', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->text('description')->nullable();

            /** Foreign key */
            $table->foreignId('transaction_summarize_id');
            $table->foreign('transaction_summarize_id')->references('id')->on('transaction_summarize');

            /** Enum */
            $table->enum('invoice_type', SalesInvoiceTypeEnum::cases());
            $table->enum('shift_type', ShiftTypeEnum::cases());

            /** number */
            $table->decimal('total_sales',19,4)->default(0);
            $table->decimal('total_ppn_sales',19,4)->default(0);
            $table->decimal('leasing_total',19,4)->default(0);
            $table->decimal('acc_total',19,4)->default(0);

            $table->decimal('down_payment_total',19,4)->default(0);
            $table->decimal('leasing_down_payment_total',19,4)->default(0);
            $table->decimal('previous_down_payment_total',19,4,)->default(0);
            $table->decimal('leasing_previous_down_payment_total',19,4)->default(0);

            $table->decimal('leasing_transfer_total',19,4,)->default(0);
            $table->decimal('transfer_total',19,4)->default(0);

            $table->decimal('debit_total',19,4)->default(0);
            $table->decimal('debit_leasing_total',19,4)->default(0);
            $table->decimal('qr_total',19,4)->default(0);
            $table->decimal('qr_leasing_total',19,4)->default(0);

            $table->decimal('current_receiveable_total',19,4)->default(0);
            $table->decimal('current_receiveable_paid',19,4)->default(0);
            $table->decimal('previous_receiveable',19,4)->default(0); // might take from previous receiveable if exists
            $table->decimal('previous_receiveable_paid',19,4)->default(0);
            $table->decimal('current_leasing_receiveable_total',19,4)->default(0);
            $table->decimal('current_leasing_receiveable_paid',19,4)->default(0);
            $table->decimal('previous_leasing_receiveable',19,4)->default(0); // might take from previous receiveable if exists
            $table->decimal('previous_leasing_receiveable_paid',19,4)->default(0);
            
            $table->decimal('internal_fee_total',19,4)->default(0);
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
