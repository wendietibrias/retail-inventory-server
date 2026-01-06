<?php

use App\Enums\MutationInStatusEnum;
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

            $table->enum('status', MutationInStatusEnum::cases())->default(MutationInStatusEnum::DIBUAT);

            $table->foreign('mutation_out_id')->references('id')->on('mutation_outs');
            $table->foreignId('mutation_out_id');

            $table->foreign(columns: 'from_warehouse_id')->references('id')->on('warehouses');
            $table->foreignId('from_warehouse_id');

            $table->foreign('to_warehouse_id')->references('id')->on('warehouses');
            $table->foreignId('to_warehouse_id');

            $table->foreign('created_by_id')->references('id')->on('users');
            $table->foreignId('created_by_id');

            $table->foreign('approve_by_id')->references('id')->on('users');
            $table->foreignId('approve_by_id')->nullable();

            $table->foreign('reject_by_id')->references('id')->on('users');
            $table->foreignId('reject_by_id')->nullable();


            $table->text('description')->nullable();
            $table->softDeletes();

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
