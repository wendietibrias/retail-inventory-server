<?php

use App\Enums\ReceiveableEnum;
use App\Enums\ReceiveableStatusEnum;
use App\Enums\ReceiveableTypeEnum;
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
        Schema::create('receiveables', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique(); 
            $table->text('description')->nullable();
            $table->dateTimeTz('due_date')->nullable();
            $table->dateTimeTz('paid_in_full_date')->nullable();

            $table->enum('type', ReceiveableTypeEnum::cases());

            /** foreign key */
            $table->foreignId('sales_invoice_id');
            $table->foreign('sales_invoice_id')->references('id')->on('sales_invoices');
            $table->foreignId('approved_by_id');
            $table->foreign('approved_by_id')->references('id')->on('users');
            $table->foreignId('created_by_id');
            $table->foreign('created_by_id')->references('id')->on('users');
            $table->foreignId('updated_by_id')->nullable();
            $table->foreign('updated_by_id')->references('id')->on('users');
            $table->foreignId('cashier_id')->nullable();
            $table->foreign('cashier_id')->references('id')->on('users');

            /** enum */
            $table->enum('status',ReceiveableStatusEnum::cases());

            /** number */
            $table->decimal('receiveable_total', 19,4)->default(0);
            $table->decimal('paid_receiveable',19,4)->default(0);
            $table->decimal('receiveable_left',19,4)->default(0);
            $table->softDeletes();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receiveables');
    }
};
