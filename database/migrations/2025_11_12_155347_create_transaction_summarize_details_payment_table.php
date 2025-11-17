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
        Schema::create('transaction_summarize_details_payment', function (Blueprint $table) {
            $table->id();

            /** number */
            $table->decimal('total_payment',19,4)->default(0);
            $table->decimal('total_admin_fee',19,4)->default(0);
            $table->decimal('total_tax',19,4)->default(0);

            $table->decimal('total_credit_payment',19,4)->default(0);
            $table->decimal('total_credit_walkin',19,4)->default(0);


            /** foreign key */
            $table->foreignId('tsd_id');
            $table->foreign('tsd_id')->references('id')->on('transaction_summarize_details');
            $table->foreignId('payment_type_id');
            $table->foreign('payment_type_id')->references('id')->on('payment_method_details');
            $table->softDeletes();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_summarize_details_payment');
    }
};
