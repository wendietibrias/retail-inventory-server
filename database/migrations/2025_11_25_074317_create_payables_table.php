<?php

use App\Enums\PayableStatusEnum;
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
        Schema::create('payables', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique();
            $table->string('supplier_name');
            $table->string('supplier_phone');
            $table->text('supplier_address')->nullable();
            $table->text('description')->nullable();

            $table->enum('status', PayableStatusEnum::cases());

            $table->decimal('tax_amount',19,4)->default(0);
            $table->decimal('sub_total',19,4)->default(0);
            $table->decimal('grand_total',19,4)->default(0);

            $table->dateTimeTz('payable_date');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payables');
    }
};
