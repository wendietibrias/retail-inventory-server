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
        Schema::create('mutation_in', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique();
            $table->dateTIme('date');

            $table->foreign(columns: 'from_warehouse_id')->references('id')->on('warehouses');
            $table->foreignId('from_warehouse_id');

            $table->foreign('to_warehouse_id')->references('id')->on('warehouses');
            $table->foreignId('to_warehouse_id');

            $table->text('description')->nullable();


            $table->foreign('updated_by_id')->references('id')->on('users');
            $table->foreignId('updated_by_id')->nullable();

            $table->foreign('approve_by_id')->references('id')->on('users');
            $table->foreignId('approve_by_id')->nullable();

            $table->foreign('reject_by_id')->references('id')->on('users');
            $table->foreignId('reject_by_id')->nullable();


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mutation_in');
    }
};
