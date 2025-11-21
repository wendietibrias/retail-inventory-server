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
        Schema::create('shift_transactions', function (Blueprint $table) {
            $table->id(); //laporan rekapan ngambil dari sini

            $table->text('description')->nullable();
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            
            /** Number */
            $table->decimal('paid_amount',19,4)->default(0);
            $table->decimal('other_paid_amount',19,4)->default(0);
            $table->decimal('down_payment_amount',19,4)->default(0);
            $table->decimal('tax_amount',19,4)->default(0);
            $table->decimal('admin_fee_amount',19,4)->default(0);

            $table->decimal('total_paid_amount',19,4)->default(0);
            
            /** Foreign key */
            $table->foreignId('cs_detail_id');
            $table->foreignId('leasing_id')->nullable();
            $table->foreign('leasing_id')->references('id')->on('leasings');
            $table->foreign('cs_detail_id')->references('id')->on('cashier_shift_details');
            $table->foreignId('created_by_id');
            $table->foreign('created_by_id')->references('id')->on('users');
            $table->foreignId('updated_by_id')->nullable();
            $table->foreign('updated_by_id')->references('id')->on('users');
            $table->foreign('pm_detail_id')->references('id')->on('payment_method_details');
            $table->foreignId('dpm_detail_id')->nullable();
            $table->foreign('dpm_detail_id')->references('id')->on('payment_method_details');
            $table->foreignId('opm_detail_id')->nullable();
            $table->foreign('opm_detail_id')->references('id')->on('payment_method_details');
            $table->foreignId('sales_invoice_id');
            $table->foreign('sales_invoice_id')->references('id')->on('sales_invoices');
            $table->softDeletes();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_transactions');
    }
};
