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
        Schema::create('ticket_fare_summaries', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->nullable();
            $table->bigInteger('ticket_id')->nullable();

            $table->enum('pax_type', ['Adult', 'Child', 'Infant'])->nullable();
            $table->decimal('unit_price', 20,2)->nullable()->default(0);
            $table->integer('pax_count')->nullable()->default(0);
            $table->decimal('total', 20,2)->nullable()->default(0);
            $table->decimal('subtotal', 20,2)->nullable()->default(0);
            $table->decimal('discount', 20,2)->nullable()->default(0);
            $table->decimal('grandtotal', 20,2)->nullable()->default(0)->comment('per ticket');

            $table->bigInteger('created_by')->nullable();
            $table->bigInteger('updated_by')->nullable();
            $table->bigInteger('deleted_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_fare_summaries');
    }
};
