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
        Schema::create('ticket_flights', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->nullable();
            $table->bigInteger('ticket_id')->nullable();
            $table->bigInteger('parent_id')->nullable();

            $table->boolean('is_transit')->nullable()->default(0);
            $table->longText('total_transit_time')->nullable();
            $table->bigInteger('airline_id')->nullable();
            $table->longText('flight_number')->nullable();
            $table->longText('leaving_from')->nullable();
            $table->longText('going_to')->nullable();
            $table->dateTime('departure_date_time')->nullable();
            $table->dateTime('arrival_date_time')->nullable();
            $table->longText('total_fly_time')->nullable();

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
        Schema::dropIfExists('ticket_flights');
    }
};
