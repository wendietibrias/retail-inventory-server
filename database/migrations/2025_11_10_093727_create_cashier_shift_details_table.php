<?php

use App\Enums\ShiftStatusEnum;
use App\Enums\ShiftTypeEnum;
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
        Schema::create('cashier_shift_details', function (Blueprint $table) {
            $table->id();

            /** Time */
            $table->dateTimeTz('shift_open_time')->nullable();
            $table->dateTimeTz('shift_close_time')->nullable();

            /** */
            $table->enum('type',ShiftTypeEnum::cases());
            $table->enum('status',ShiftStatusEnum::cases());

            /** Foreign key */
            $table->foreignId('cashier_shift_id');
            $table->foreign('cashier_shift_id')->references('id')->on('cashier_shifts');
            $table->foreignId('cashier_id')->nullable();
            $table->foreign('cashier_id')->references('id')->on('users');

            /** Number */
            $table->decimal('initial_cash_amount', 19,4)->default(0);
            $table->decimal('cash_in_box_amount',19,4)->default(0);            
            $table->decimal('cash_drawer_amount',19,4)->default(0);
            $table->decimal('difference_amount',19,4)->default(0);
            $table->decimal('final_cash',19,4)->default(0);
            $table->softDeletes();

            //ada serah terima  

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cashier_shift_details');
    }
};
