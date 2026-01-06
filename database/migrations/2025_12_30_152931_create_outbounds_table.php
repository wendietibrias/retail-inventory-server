<?php

use App\Enums\OutboundStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('outbounds', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique();
            $table->dateTime('date');
            $table->enum('status', OutboundStatusEnum::cases())->default(OutboundStatusEnum::DIBUAT);

            $table->foreign('customer_id')->references('id')->on('customers');
            $table->foreignId('customer_id');
            $table->foreignId('warehouse_id');
            $table->foreign('warehouse_id')->references('id')->on('warehouses');


            $table->text('description')->nullable();

            $table->decimal('grand_total', 19, 2)->default(0);

            $table->foreign('created_by_id')->references('id')->on('users');
            $table->foreignId('created_by_id');
            
            $table->foreign('approve_by_id')->references('id')->on('users');
            $table->foreignId('approve_by_id')->nullable();
            
            $table->foreign('reject_by_id')->references('id')->on('users');
            $table->foreignId('reject_by_id')->nullable();
            

            $table->softDeletes();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outbounds');
    }
};
