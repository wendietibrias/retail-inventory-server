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
        Schema::create('payment_method_details', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            $table->decimal('total_payment',19,4)->default(0);
            $table->decimal('admin_fee', 19,4)->default(0);

            /** foreign key */
            $table->foreignId('payment_method_id');
            $table->foreign('payment_method_id')->references('id')->on('payment_methods');
            $table->softDeletes();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_method_details');
    }
};
